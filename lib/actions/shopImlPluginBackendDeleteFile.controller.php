<?php

class shopImlPluginBackendDeleteFileController extends waJsonController {

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(shopImlPlugin::$plugin_id);

        $filename = waRequest::post('file');
        $iml = new shopIml('https://request.imlogistic.ru', $settings['login'], $settings['password']);
        $request = $iml->deleteFile($filename);
    }

}
