<?php

class shopQdiscountPluginBackendSaveController extends waJsonController {

    public function execute() {
        if ($hash = waRequest::post('hash')) {
            $collection = new shopProductsCollection($hash);
            $products = $collection->getProducts('*', 0, 99999);
            $product_ids = array_keys($products);
        } else {
            $product_ids = waRequest::post('product_id');
        }

        if (empty($product_ids)) {
            throw new waException('Выберите хотя бы один товар');
        }

        $discount_route_hash = waRequest::post('qdiscount_route_hash', array());
        $qdiscount_sku = waRequest::post('qdiscount_sku', array());
        $qdiscount_count = waRequest::post('qdiscount_count', array());
        $qdiscount_type = waRequest::post('qdiscount_type', array());
        $qdiscount_price = waRequest::post('qdiscount_price', array());
        $qdiscount_currency = waRequest::post('qdiscount_currency', array());


        $qdiscount_model = new shopQdiscountPluginModel();
        $qdiscount_model->deleteByField('product_id', $product_ids);
        
        $data = array();
        foreach ($product_ids as $product_id) {
            foreach ($discount_route_hash as $index => $route_hash) {
                if (empty($qdiscount_count[$index]) || empty($qdiscount_price[$index])) {
                    continue;
                }
                $data[] = array(
                    'route_hash' => $route_hash,
                    'product_id' => $product_id,
                    'sku_id' => $qdiscount_sku[$index],
                    'count' => $qdiscount_count[$index],
                    'type' => $qdiscount_type[$index],
                    'price' => $qdiscount_price[$index],
                    'currency' => $qdiscount_currency[$index],
                );
            }
        }
        $qdiscount_model->multiInsert($data);
    }

}
