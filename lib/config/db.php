<?php
return array(
    'shop_qdiscount' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'product_id' => array('int', 11, 'null' => 0),
        'sku_id' => array('int', 11, 'null' => 0),
        'count' => array('int', 11, 'null' => 0),
        'price' => array('decimal', "15,4", 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('id'),
            'product_id' => 'product_id',
            'sku_id' => 'sku_id',
        ),
    ),
);
