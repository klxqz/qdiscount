<?php

return array(
    'name' => 'Скидка от количества',
    'description' => 'Стоимость определяется количеством купленного товара',
    'vendor' => '985310',
    'version' => '3.0.0',
    'img' => 'img/qdiscount.png',
    'frontend' => true,
    'shop_settings' => true,
    'importexport' => true,
    'handlers' => array(
        'product_save' => 'productSave',
        'backend_products' => 'backendProducts',
        'backend_product' => 'backendProduct',
        'frontend_product' => 'frontendProduct',
        'order_calculate_discount' => 'orderCalculateDiscount',
        'products_collection' => 'productsCollection',
        'frontend_products' => 'frontendProducts',
        //'frontend_category' => 'frontendCategory',
    ),
);
