<?php

class shopImlPluginBackendDialogAction extends waViewAction {

    public function execute() {
        $config_path = wa()->getConfig()->getConfigPath('plugins/iml/config.php', true, 'shop');
        if (file_exists($config_path)) {
            $pickup_points = include $config_path;
        } else {
            $pickup_points = include wa()->getAppPath('plugins/iml/lib/config/config.php', 'shop');
        }
        $this->view->assign('pickup_points', $pickup_points);
    }

}
