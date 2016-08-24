<?php

class shopQdiscountPluginBackendSaveController extends waJsonController {

    public function execute() {
        $product_id = waRequest::post('qdiscount_product_id');
        $qdiscount_route_hash = waRequest::post('qdiscount_route_hash', array());
        $qdiscount_id = waRequest::post('qdiscount_id', array());
        $qdiscount_sku = waRequest::post('qdiscount_sku', array());
        $qdiscount_count = waRequest::post('qdiscount_count', array());
        $qdiscount_price = waRequest::post('qdiscount_price', array());


        $qdiscount_model = new shopQdiscountPluginModel();
        $items = array();
        foreach ($qdiscount_id as $key => $id) {
            $item = array(
                'id' => $id,
                'route_hash' => $qdiscount_route_hash[$key],
                'product_id' => $product_id,
                'sku_id' => $qdiscount_sku[$key],
                'count' => $qdiscount_count[$key],
                'price' => $qdiscount_price[$key],
            );
            if (empty($item['id'])) {
                $item['id'] = $qdiscount_model->insert($item);
            } else {
                $qdiscount_model->updateById($item['id'], $item);
            }
            $items[] = $item;
        }
        $this->response['items'] = $items;
    }

}
