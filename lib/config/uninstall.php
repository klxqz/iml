<?php

$model = new waModel();

try {
    $model->query("SELECT `iml` FROM `shop_order` WHERE 0");
    $model->exec("ALTER TABLE `shop_order` DROP `iml`");
} catch (waDbException $e) {
    
}
