<?php

class shopImlPlugin extends shopPlugin {

    public static $plugin_id = array('shop', 'iml');
    public static $services = array(
        '24' => 'Безналичный Расчет',
        '24КО' => 'Кассовое Обслуживание',
        '24НАЛ' => 'Наличный Расчет',
        'В24' => 'Возврат Безналичный',
        'ЗАБОР' => 'Забор Товара',
        'С24' => 'Самовывоз Безналичный Расчет',
        'С24КО' => 'Самовывоз Кассовое Обслуживание',
        'С24НАЛ' => 'Самовывоз Наличный Расчет',
        'СВ24' => 'Самовывоз Возврат Безналичный',
        'Э24' => 'Экспресс Безналичный Расчет',
        'Э24КО' => 'Экспресс Кассовое Обслуживание',
        'Э24НАЛ' => 'Экспресс Наличный расчет',
        'ЭЗАБОР' => 'Экспресс Забор Товара',
    );
    public static $pickup_services = array(
        'С24',
        'С24КО',
        'С24НАЛ',
    );

    /*
      public static $list003 = array(
      'БРЯНСК',
      'ВЛАДИМИР',
      'ВОЛОГДА',
      'ЕКАТЕРИНБУРГ',
      'ИВАНОВО',
      'КАЛУГА',
      'КОСТРОМА',
      'МОСКВА',
      'НИЖНИЙ НОВГОРОД',
      'ОРЕЛ',
      'РЯЗАНЬ',
      'САНКТ-ПЕТЕРБУРГ',
      'ТВЕРЬ',
      'ТУЛА',
      'ТЮМЕНЬ',
      'ЧЕЛЯБИНСК',
      'ЯРОСЛАВЛЬ',
      'РОСТОВ-НА-ДОНУ',
      );
     */

    public function backendMenu($param) {
        if ($this->getSettings('status')) {
            $html = '<li ' . (waRequest::get('plugin') == $this->id ? 'class="selected"' : 'class="no-tab"') . '>
                        <a href="?plugin=iml">Заявки IML</a>
                    </li>';
            return array('core_li' => $html);
        }
    }

    public function backendOrders() {
        if ($this->getSettings('status')) {
            $view = wa()->getView();
            $template_path = wa()->getAppPath('plugins/iml/templates/BackendOrders.html', 'shop');
            $html = $view->fetch($template_path);
            return array('sidebar_section' => $html);
        }
    }

    public function backendOrder($order) {

        if ($this->getSettings('status')) {
            $settings = $this->getSettings();
            $view = wa()->getView();
            $view->assign('order', $order);
            $iml_params = array();
            if (!empty($order['iml'])) {
                $iml_params = json_decode($order['iml'], true);
            }

            if (empty($iml_params['departure'])) {
                $iml_params['departure'] = $settings['departure'];
            }

            $destination = '';
            if (!empty($order['params']['shipping_address.city'])) {
                $destination = mb_strtoupper($order['params']['shipping_address.city']);
            }

            $config_path = wa()->getConfig()->getConfigPath('plugins/iml/shipping_cities.php', true, 'shop');
            if (file_exists($config_path)) {
                $shipping_cities = include $config_path;
            } else {
                $shipping_cities = include wa()->getAppPath('plugins/iml/lib/config/shipping_cities.php', 'shop');
            }

            if (empty($iml_params['destination']) && in_array($destination, $shipping_cities)) {
                $iml_params['destination'] = $destination;
            }
            if (empty($iml_params['line']) && !empty($order['params']['shipping_address.street'])) {
                $iml_params['line'] = $order['params']['shipping_address.street'];
            }
            if (empty($iml_params['city']) && !empty($order['params']['shipping_address.city'])) {
                $iml_params['city'] = $order['params']['shipping_address.city'];
            }
            if (empty($iml_params['postCode']) && !empty($order['params']['shipping_address.zip'])) {
                $iml_params['postCode'] = $order['params']['shipping_address.zip'];
            }

            //Самовывоз
            $is_pickup = false;
            if (empty($iml_params['deliveryPoint'])) {
                $iml_params['deliveryPoint'] = $this->defineDeliveryPoint($order);
            }
            if (!empty($iml_params['service']) && in_array($iml_params['service'], self::$pickup_services)) {
                $is_pickup = true;
            }


            $view->assign('shipping_cities', $shipping_cities);
            $view->assign('is_pickup', $is_pickup);
            $view->assign('settings', $settings);
            $view->assign('iml_params', $iml_params);
            $view->assign('services', self::$services);
            $view->assign('pickup_services', self::$pickup_services);
            $template_path = wa()->getAppPath('plugins/iml/templates/BackendOrder.html', 'shop');
            $html = $view->fetch($template_path);
            return array('action_link' => $html);
        }
    }

    protected function defineDeliveryPoint($order) {
        $iml_params = array();
        if (!empty($order['iml'])) {
            $iml_params = json_decode($order['iml'], true);
        }
        $destination = '';
        if (!empty($order['params']['shipping_address.city'])) {
            $destination = mb_strtoupper($order['params']['shipping_address.city']);
        }
        
        $config_path = wa()->getConfig()->getConfigPath('plugins/iml/shipping_cities.php', true, 'shop');
        if (file_exists($config_path)) {
            $shipping_cities = include $config_path;
        } else {
            $shipping_cities = include wa()->getAppPath('plugins/iml/lib/config/shipping_cities.php', 'shop');
        }

        if (empty($iml_params['destination']) && in_array($destination, $shipping_cities)) {
            $iml_params['destination'] = $destination;
        } else {
            $iml_params['destination'] = '';
        }
        if (empty($iml_params['line']) && !empty($order['params']['shipping_address.street'])) {
            $iml_params['line'] = $order['params']['shipping_address.street'];
        } else {
            $iml_params['line'] = '';
        }
        if (empty($iml_params['postCode']) && !empty($order['params']['shipping_address.zip'])) {
            $iml_params['postCode'] = $order['params']['shipping_address.zip'];
        } else {
            $iml_params['postCode'] = '';
        }

        $config_path = wa()->getConfig()->getConfigPath('plugins/iml/config.php', true, 'shop');
        if (file_exists($config_path)) {
            $pickup_points = include $config_path;
        } else {
            $pickup_points = include wa()->getAppPath('plugins/iml/lib/config/config.php', 'shop');
        }
        foreach ($pickup_points as $pickup_point) {
            if ($pickup_point[2] == $iml_params['line'] && $pickup_point[3] == $iml_params['postCode'] && $iml_params['destination'] == mb_strtoupper($pickup_point[4])) {
                return $pickup_point[0];
            }
        }
        return false;
    }

}
