<?php

$model = new waModel();
try {
    $model->exec("ALTER TABLE `shop_qdiscount` ADD `route_hash` VARCHAR( 32 ) NOT NULL DEFAULT '0' AFTER `id`");
} catch (waDbException $e) {
    
}

try {
    $files = array(
        'plugins/qdiscount/lib/actions/shopQdiscountPluginBackendSaveSettings.controller.php',
        'plugins/qdiscount/templates/BackendProduct.html',
        'plugins/qdiscount/templates/FrontendProduct.html',
    );

    foreach ($files as $file) {
        waFiles::delete(wa()->getAppPath($file, 'shop'), true);
    }
} catch (Exception $e) {
    
}