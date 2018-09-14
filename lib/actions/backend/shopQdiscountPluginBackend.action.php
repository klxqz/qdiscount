<?php

class shopQdiscountPluginBackendAction extends waViewAction {

    public function execute() {
        $id = waRequest::get('id', null, waRequest::TYPE_INT);

        $product_model = new shopProductModel();
        $product = $product_model->getById($id);

        $qdiscount_model = new shopQdiscountPluginModel();
        $items = $qdiscount_model->where('product_id = ' . (int) $id)
                ->order('sku_id ASC, count ASC')
                ->fetchAll();

        $currency_model = new shopCurrencyModel();
        $this->view->assign(array(
            'currencies' => $currency_model->getCurrencies(),
            'product' => $product ? new shopProduct($id) : null,
            'items' => $items,
            'route_hashs' => shopQdiscountRouteHelper::getRouteHashs()
        ));
    }

}
