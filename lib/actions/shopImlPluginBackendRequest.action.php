<?php

/**
 * @author wa-plugins.ru <support@wa-plugins.ru>
 * @link http://wa-plugins.ru/
 */
class shopImlPluginBackendRequestAction extends waViewAction {

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(shopImlPlugin::$plugin_id);

        $filename = waRequest::get('file');
        try {
            $iml = new shopIml('https://request.iml.ru', $settings['login'], $settings['password']);
            $request = $iml->getFile($filename);
            $this->view->assign('request', $request);
        } catch (waException $ex) {
            $this->view->assign('error', $ex->getMessage());
        }
    }

}
