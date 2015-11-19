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
        $iml = new shopIml('https://request.imlogistic.ru', $settings['login'], $settings['password']);
        $request = $iml->getFile($filename);

        $this->view->assign(array(
            'filename' => $filename,
            'request' => $request,
        ));
    }

}