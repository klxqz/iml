<?php

class shopImlPluginSettingsAction extends waViewAction {

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(shopImlPlugin::$plugin_id);

        $config_path = wa()->getConfig()->getConfigPath('plugins/iml/config.php', true, 'shop');
        if (file_exists($config_path)) {
            $pickup_points = include $config_path;
        } else {
            $pickup_points = include wa()->getAppPath('plugins/iml/lib/config/config.php', 'shop');
        }

        $this->view->assign('settings', $settings);
        $this->view->assign('departure', shopImlPlugin::$list003);
        $this->view->assign('pickup_points', $pickup_points);
    }

}
