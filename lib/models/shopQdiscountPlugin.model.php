<?php

class shopQdiscountPluginModel extends waModel {

    protected $table = 'shop_qdiscount';

    public function filter($route_hash, $items) {
        $where = array();
        foreach ($items as $item) {
            if (!empty($item['product_id']) && !empty($item['sku_id']) && !empty($item['quantity'])) {
                $where[] = "(`product_id` = '" . (int) $item['product_id'] . "' AND `count` <= '" . (int) $item['quantity'] . "' AND (`sku_id` = '" . (int) $item['sku_id'] . "' OR `sku_id` = 0))";
            }
        }

        $sql = "SELECT 
                *, 
                CONCAT(`product_id`, '_', `sku_id`) as `id`,
                MAX(`count`) as `count`
                FROM `" . $this->table . "`
                WHERE `route_hash` = '" . $this->escape($route_hash) . "' AND (" . implode(' OR ', $where) . ")
                GROUP BY `product_id`, `sku_id`";
        return $this->query($sql)->fetchAll('id');
    }

}
