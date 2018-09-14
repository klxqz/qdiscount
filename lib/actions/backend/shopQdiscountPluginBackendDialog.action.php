<?php

class shopQdiscountPluginBackendDialogAction extends waViewAction {

    public function execute() {
        $currency_model = new shopCurrencyModel();
        $this->view->assign(array(
            'currencies' => $currency_model->getCurrencies(),
            'route_hashs' => shopQdiscountRouteHelper::getRouteHashs()
        ));
    }

}
