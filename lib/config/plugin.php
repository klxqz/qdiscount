<?php

return array(
    'name' => 'Скидка от количества',
    'description' => 'Стоимость определяется количеством купленного товара',
    'vendor' => '985310',
    'version' => '2.0.1',
    'img' => 'img/qdiscount.png',
    'frontend' => true,
    'shop_settings' => true,
    'handlers' => array(
        'backend_product' => 'backendProduct',
        'frontend_product' => 'frontendProduct',
        'order_calculate_discount' => 'orderCalculateDiscount',
    ),
);
