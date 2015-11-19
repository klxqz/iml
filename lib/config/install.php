<?php

$plugin_id = array('shop', 'iml');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'status', '1');
$app_settings_model->set($plugin_id, 'login', '');
$app_settings_model->set($plugin_id, 'password', '');
$app_settings_model->set($plugin_id, 'test_mode', '0');
$app_settings_model->set($plugin_id, 'barcode_prifix', '0000');
$app_settings_model->set($plugin_id, 'barcode_counter', '1');
$app_settings_model->set($plugin_id, 'barcode_place_num', '1');
$app_settings_model->set($plugin_id, 'departure', 'МОСКВА');


$model = new waModel();
try {
    $sql = 'SELECT `iml` FROM `shop_order` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = 'ALTER TABLE `shop_order` ADD `iml` TEXT NOT NULL AFTER `id`';
    $model->query($sql);
}