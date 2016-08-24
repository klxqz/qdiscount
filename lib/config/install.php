<?php

$plugin_id = array('shop', 'qdiscount');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'status', '1');

$model = new waModel();
try {
    $sql = 'SELECT `qdiscount_units` FROM `shop_product` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = "ALTER TABLE `shop_product` ADD `qdiscount_units` VARCHAR( 255 ) NOT NULL DEFAULT 'шт.' AFTER `id`";
    $model->query($sql);
}

