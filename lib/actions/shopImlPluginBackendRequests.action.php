<?php

/**
 * @author wa-plugins.ru <support@wa-plugins.ru>
 * @link http://wa-plugins.ru/
 */
class shopImlPluginBackendRequestsAction extends waViewAction {

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(shopImlPlugin::$plugin_id);
        try {
            $iml = new shopIml('https://request.imlogistic.ru', $settings['login'], $settings['password']);
            $files = $iml->getFilesList();
            $this->view->assign('files', $files);
        } catch (waException $ex) {
            $this->view->assign('error', $ex->getMessage());
        }
    }

}
