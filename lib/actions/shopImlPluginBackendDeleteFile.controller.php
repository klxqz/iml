<?php

class shopImlPluginBackendDeleteFileController extends waJsonController {

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(shopImlPlugin::$plugin_id);

        $filename = waRequest::post('file');
        try {
            $iml = new shopIml('https://request.iml.ru', $settings['login'], $settings['password']);
            $request = $iml->deleteFile($filename);
        } catch (waException $ex) {
            $this->errors[] = $ex->getMessage();
        }
    }

}
