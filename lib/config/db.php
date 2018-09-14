<?php

return array(
    'shop_qdiscount' => array(
        'route_hash' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'product_id' => array('int', 11, 'null' => 0),
        'sku_id' => array('int', 11, 'null' => 0),
        'count' => array('int', 11, 'null' => 0),
        'type' => array('enum', "'new_price','currency','percent'", 'null' => 0, 'default' => 'new_price'),
        'price' => array('decimal', "15,4", 'null' => 0),
        'currency' => array('char', 3, 'null' => 0),
        ':keys' => array(
            'route_hash' => 'route_hash',
            'product_id' => 'product_id',
            'sku_id' => 'sku_id',
        ),
    ),
);
