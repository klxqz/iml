<?php

class shopImlPluginBackendSendOrdersController extends waJsonController {

    private $encoding = 'UTF-8'; //windows-1251
    private $dom;

    public function execute() {
        try {
            $order_ids = waRequest::post('order_ids', array());
            if (empty($order_ids)) {
                throw new waException('Нет выбранных заказов для отправки');
            }

            foreach ($order_ids as $order_id) {
                $this->createXml($order_id);
            }
            $this->response['message'] = 'Успешно';
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    protected function checkRequiredField($order) {
        if (empty($order['iml'])) {
            throw new waException('Введите параметры доставки IML для заказа ' . shopHelper::encodeOrderId($order['id']));
        }
        $iml_params = json_decode($order['iml'], true);

        if (empty($iml_params['service'])) {
            throw new waException('Не указан тип услуги для заказа ' . shopHelper::encodeOrderId($order['id']));
        }
    }

    protected function createXml($order_id) {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(shopImlPlugin::$plugin_id);
        $order_model = new shopOrderModel();
        $order = $order_model->getOrder($order_id);

        $this->checkRequiredField($order);

        $total = $this->getTotal($order['items']);
        $shipping_discount = $order['shipping'] - $order['discount'];
        $iml_params = json_decode($order['iml'], true);



        if (!class_exists('DOMDocument')) {
            throw new waException('PHP extension DOM required');
        }
        $this->dom = new DOMDocument("1.0", $this->encoding);
        $this->dom->encoding = $this->encoding;
        $this->dom->formatOutput = true;
        $xml = <<<XML
<?xml version="1.0" encoding="{$this->encoding}"?>
<DeliveryRequest xmlns="http://www.imlogistic.ru/schema/request/v1"></DeliveryRequest>
XML;
        $this->dom->loadXML($xml);

        $message = array(
            'sender' => $settings['login'],
            'recipient' => 'IM-Logistics',
            'issue' => date('Y-m-d\TH:i:s'),
            'reference' => 'order_' . str_replace('#', '', shopHelper::encodeOrderId($order_id)),
            'version' => '1.0',
            'test' => $settings['test_mode'],
        );
        $message_xml = $this->dom->createElement("Message");
        $this->arrayToXml($message_xml, $message);


        $this->dom->lastChild->appendChild($message_xml);

        $order_xml = $this->dom->createElement("Order");
        $this->addDomValue($order_xml, 'number', shopHelper::encodeOrderId($order_id));
        $this->addDomValue($order_xml, 'action', 'CREATE');

        $condition_xml = $this->dom->createElement("Condition");
        $this->addDomValue($condition_xml, 'service', $iml_params['service']);
        $this->addDomValue($condition_xml, 'comment', $iml_params['comment']);

        $delivery_xml = $this->dom->createElement("Delivery");
        $delivery = array(
            'issue' => $iml_params['issue'],
            'timeFrom' => $iml_params['timeFrom'],
            'timeTo' => $iml_params['timeTo'],
        );
        $this->arrayToXml($delivery_xml, $delivery);

        $condition_xml->appendChild($delivery_xml);
        $order_xml->appendChild($condition_xml);

        $region_xml = $this->dom->createElement("Region");

        $region = array(
            'departure' => $iml_params['departure'],
            'destination' => $iml_params['destination'],
        );
        $this->arrayToXml($region_xml, $region);
        $order_xml->appendChild($region_xml);

        $consignee_xml = $this->dom->createElement("Consignee");
        $address_xml = $this->dom->createElement("Address");
        $address = array(
            'line' => $iml_params['line'],
            'city' => $iml_params['city'],
            'postCode' => $iml_params['postCode'],
        );
        $this->arrayToXml($address_xml, $address);
        $consignee_xml->appendChild($address_xml);
        $representative_person_xml = $this->dom->createElement("RepresentativePerson");
        $this->addDomValue($representative_person_xml, 'name', $order['contact']['name']);
        $communication_xml = $this->dom->createElement("Communication");
        $this->addDomValue($communication_xml, 'telephone1', $order['contact']['phone']);
        $this->addDomValue($communication_xml, 'telephone2', '');
        $this->addDomValue($communication_xml, 'telephone3', '');
        $representative_person_xml->appendChild($communication_xml);
        $consignee_xml->appendChild($representative_person_xml);
        $order_xml->appendChild($consignee_xml);

        $self_delivery_xml = $this->dom->createElement("SelfDelivery");
        if (!empty($iml_params['deliveryPoint'])) {
            $this->addDomValue($self_delivery_xml, 'deliveryPoint', $iml_params['deliveryPoint']);
        }
        $order_xml->appendChild($self_delivery_xml);

        $goods_measure_xml = $this->dom->createElement("GoodsMeasure");
        $goods_measure = array(
            'weight' => $this->getTotalWeight($order['items']),
            'volume' => null,
            'amount' => 0,
            'statisticalValue' => $total,
        );
        if (!in_array($iml_params['service'], array('24', 'В24', 'С24', 'СВ24', 'Э24'))) {
            $goods_measure['amount'] = $total + $shipping_discount;
        }

        $this->arrayToXml($goods_measure_xml, $goods_measure);
        $order_xml->appendChild($goods_measure_xml);

        $goods_items_xml = $this->dom->createElement("GoodsItems");

        $shipping_discount_item = floor($shipping_discount / count($order['items']) * 100) / 100;

        $shipping_discount_last_item = round($shipping_discount - $shipping_discount_item * count($order['items']), 2);

        $iteration = 1;
        foreach ($order['items'] as $_item) {
            $item = array(
                'productNo' => $_item['sku_id'] . '-' . ($_item['sku_code'] ? $_item['sku_code'] : '0'),
                'productName' => $_item['name'],
                'productVariant' => '',
                'productBarCode' => $this->getBarCode(),
                'couponCode' => null,
                'discount' => null,
                'weightLine' => $this->getProductWeight($_item['product_id']),
                'amountLine' => 0,
                'statisticalValueLine' => $_item['price'] * $_item['quantity'],
            );
            if (!in_array($iml_params['service'], array('24', 'В24', 'С24', 'СВ24', 'Э24'))) {
                $item['amountLine'] = $_item['price'] * $_item['quantity'] + $shipping_discount_item;
                if ($iteration == count($order['items'])) {
                    $item['amountLine'] += $shipping_discount_last_item;
                }
            }
            $item_xml = $this->dom->createElement("Item");
            $this->arrayToXml($item_xml, $item);
            $goods_items_xml->appendChild($item_xml);
            $iteration++;
        }
        /*
          if ($order['shipping'] > 0) {
          $item = array(
          'productNo' => 'shipping',
          'productName' => 'Доставка',
          'productVariant' => '',
          'productBarCode' => $this->getBarCode(),
          'couponCode' => null,
          'discount' => null,
          'weightLine' => null,
          'amountLine' => $order['shipping'],
          'statisticalValueLine' => $order['shipping'],
          );
          $item_xml = $this->dom->createElement("Item");
          $this->arrayToXml($item_xml, $item);
          $goods_items_xml->appendChild($item_xml);
          }
         * 
         */


        $order_xml->appendChild($goods_items_xml);

        $this->dom->lastChild->appendChild($order_xml);

        $name = 'order_' . str_replace('#', '', shopHelper::encodeOrderId($order_id)) . '.xml';
        $path = wa()->getTempPath('plugins/iml/', 'shop') . $name;
        $this->dom->save($path);

        $iml = new shopIml('https://request.iml.ru', $settings['login'], $settings['password']);
        $iml->sendFile($path);
    }

    private function getTotal($items) {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    private function getTotalWeight($items) {
        $total = 0;
        foreach ($items as $item) {
            $total += $this->getProductWeight($item['product_id']);
        }
        return $total;
    }

    private function getProductWeight($product_id) {
        $product = new shopProduct($product_id);
        if (!empty($product->features['weight'])) {
            $weight = $product->features['weight'];
            if ($weight instanceof shopDimensionValue) {
                $units = $weight->units;
                if (!empty($units['kg'])) {
                    return $weight->convert('kg');
                }
            }
        }
        return null;
    }

    private function ean13_checksum($barcode) {
        $barcode = str_pad($barcode, 12, '0', STR_PAD_LEFT);
        $sum = 0;
        for ($i = (strlen($barcode) - 1); $i >= 0; $i--) {
            $sum += (($i % 2) * 2 + 1 ) * $barcode[$i];
        }
        if ($sum % 10) {
            return (10 - ($sum % 10));
        } else {
            return 0;
        }
    }

    private function getBarCode() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(shopImlPlugin::$plugin_id);

        if (!empty($settings['barcode_counter'])) {
            $counter = $settings['barcode_counter'];
        } else {
            $counter = 0;
        }
        $counter++;
        $app_settings_model->set(shopImlPlugin::$plugin_id, 'barcode_counter', $counter);


        $bar_code = array(
            $settings['barcode_prifix'],
            str_pad($counter, 7, '0', STR_PAD_LEFT),
            $settings['barcode_place_num'],
        );
        $bar_code[] = $this->ean13_checksum(implode('', $bar_code));
        return implode('', $bar_code);
    }

    private function arrayToXml(&$dom, $array) {
        foreach ($array as $field => $value) {
            $this->addDomValue($dom, $field, $value);
        }
    }

    private function addDomValue(&$dom, $field, $value, $is_attribute = false) {
        if (is_array($value)) {
            reset($value);
            if (!preg_match('@^\d+$@', key($value))) {
                $element = $this->dom->createElement($field, trim(ifset($value['value'])));
                unset($value['value']);

                foreach ($value as $attribute => $attribute_value) {
                    $element->setAttribute($attribute, $attribute_value);
                }
                $dom->appendChild($element);
            } else {
                foreach ($value as $value_item) {
                    $dom->appendChild($this->dom->createElement($field, trim($value_item)));
                }
            }
        } elseif (!$is_attribute) {
            $child = $this->dom->createElement($field, trim($value));
            $dom->appendChild($child);
        } else {
            $dom->setAttribute($field, trim($value));
        }
    }

}
