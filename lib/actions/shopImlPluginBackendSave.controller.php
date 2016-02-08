<?php

class shopImlPluginBackendSaveController extends waJsonController {

    public function execute() {
        try {
            $app_settings_model = new waAppSettingsModel();
            $shop_iml = waRequest::post('shop_iml', array());

            foreach ($shop_iml as $key => $value) {
                $app_settings_model->set(shopImlPlugin::$plugin_id, $key, $value);
            }

            $pickup_points = waRequest::post('pickup_points', array());
            if (is_array($pickup_points)) {
                foreach ($pickup_points as $index => $pickup_point) {
                    if (empty($pickup_point[0])) {
                        unset($pickup_points[$index]);
                    }
                }
                unset($pickup_point);
                $pickup_points = array_values($pickup_points);
            }
            $config_path = wa()->getConfig()->getConfigPath('plugins/iml/config.php', true, 'shop');
            waUtils::varExportToFile($pickup_points, $config_path);

            $reset_pickup_points = waRequest::post('reset_pickup_points');

            if ($reset_pickup_points) {
                @unlink($config_path);
            }
            
            $shipping_cities = waRequest::post('shipping_cities', array());
            if (is_array($shipping_cities)) {
                foreach ($shipping_cities as $index => $shipping_city) {
                    if (empty($shipping_city)) {
                        unset($shipping_cities[$index]);
                    }
                }
                unset($shipping_city);
                $shipping_cities = array_values($shipping_cities);
                sort($shipping_cities);
            }
            $config_path = wa()->getConfig()->getConfigPath('plugins/iml/shipping_cities.php', true, 'shop');

            waUtils::varExportToFile($shipping_cities, $config_path);

            $reset_shipping_cities = waRequest::post('reset_shipping_cities');

            if ($reset_shipping_cities) {
                @unlink($config_path);
            }

            $this->response['message'] = "Сохранено";
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
