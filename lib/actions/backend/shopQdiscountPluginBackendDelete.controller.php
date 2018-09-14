<?php

class shopQdiscountPluginBackendDeleteController extends waJsonController {

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

        $qdiscount_model = new shopQdiscountPluginModel();
        $qdiscount_model->deleteByField('product_id', $product_ids);
    }

}
