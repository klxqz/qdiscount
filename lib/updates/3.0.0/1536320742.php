<?php

$files = array(
    'plugins/qdiscount/js/settings.js',
    'plugins/qdiscount/js/qdiscount.js',
    'plugins/qdiscount/js/loadcontent.js',
    'plugins/qdiscount/lib/config/uninstall.php',
    'plugins/qdiscount/lib/actions/shopQdiscountPluginBackend.action.php',
    'plugins/qdiscount/lib/actions/shopQdiscountPluginBackendDelete.controller.php',
    'plugins/qdiscount/lib/actions/shopQdiscountPluginBackendSave.controller.php',
    'plugins/qdiscount/lib/actions/shopQdiscountPluginSettings.action.php',
    'plugins/qdiscount/lib/actions/shopQdiscountPluginSettingsRoute.action.php',
    'plugins/qdiscount/lib/classes/shopQdiscountHelper.class.php',
    'plugins/qdiscount/templates/actions/backend/BackendProduct.html',
    'plugins/qdiscount/templates/actions/frontend/FrontendProduct.html',
    'plugins/qdiscount/templates/actions/frontend/',
);

foreach ($files as $file) {
    try {
        waFiles::delete(wa()->getAppPath($file, 'shop'), true);
    } catch (Exception $e) {
        
    }
}

$model = new waModel();
$sqls = array(
    "ALTER TABLE `shop_qdiscount` DROP `id`",
    "ALTER TABLE `shop_qdiscount` ADD `type` ENUM( 'new_price', 'currency', 'percent' ) NOT NULL DEFAULT 'new_price' AFTER `count`",
    "ALTER TABLE `shop_qdiscount` ADD `currency` CHAR( 3 ) NOT NULL",
    "ALTER TABLE `shop_product` DROP `qdiscount_units`",
);

foreach ($sqls as $sql) {
    try {
        $model->query($sql);
    } catch (waDbException $e) {
        
    }
}

