<?php

return array(
    'name' => 'Скидка от количеста',
    'description' => 'Стоимость определяется количеством купленного товара',
    'vendor' => '985310',
    'version' => '1.0.0',
    'img' => 'img/qdiscount.png',
    'frontend' => true,
    'shop_settings' => true,
    'handlers' => array(
        'backend_product' => 'backendProduct',
        'frontend_product' => 'frontendProduct',
        'order_calculate_discount' => 'orderCalculateDiscount',
    ),
);
