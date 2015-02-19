<?php

$model = new waModel();

try {
    $model->query("SELECT `qdiscount_units` FROM `shop_product` WHERE 0");
    $model->exec("ALTER TABLE `shop_product` DROP `qdiscount_units`");
} catch (waDbException $e) {
    
}
