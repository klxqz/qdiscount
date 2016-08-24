<?php

class shopQdiscountPluginBackendAction extends waViewAction {

    public function execute() {
        $id = waRequest::get('id', null, waRequest::TYPE_INT);

        $product_model = new shopProductModel();
        $product = $product_model->getById($id);
        if (!$product) {
            throw new waException(_w("Unknown product"));
        }
        $p = new shopProduct($product);

        $qdiscount_model = new shopQdiscountPluginModel();
        $items = $qdiscount_model->where('product_id = ' . (int) $id)
                ->order('sku_id ASC, count ASC')
                ->fetchAll();

        $this->view->assign(array(
            'product' => $p,
            'items' => $items,
            'route_hashs' => shopQdiscountHelper::getRouteHashs()
        ));
    }

}
