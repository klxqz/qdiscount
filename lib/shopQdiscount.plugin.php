<?php

class shopQdiscountPlugin extends shopPlugin {

    public function backendProduct($product) {
        if ($this->getSettings('status')) {
            $view = wa()->getView();
            $view->assign('product', $product);
            $html = $view->fetch('plugins/qdiscount/templates/BackendProduct.html');
            return array('edit_section_li' => $html);
        }
    }

    public function frontendProduct($product) {
        if ($this->getSettings('status') && $this->getSettings('frontend_product')) {
            $html = self::display($product);
            $frontend_product_output = $this->getSettings('frontend_product_output');
            return array($frontend_product_output => $html);
        }
    }

    public static function display($product) {
        $app_settings_model = new waAppSettingsModel();
        if ($app_settings_model->get(array('shop', 'qdiscount'), 'status')) {
            $qdiscount_model = new shopQdiscountPluginModel();
            $items = $qdiscount_model->where('product_id = ' . (int) $product['id'])
                    ->order('sku_id ASC, count ASC')
                    ->fetchAll();
            if ($items) {
                $view = wa()->getView();
                $view->assign('items', $items);
                $view->assign('product', $product);

                $template_path = wa()->getDataPath('plugins/qdiscount/templates/FrontendProduct.html', false, 'shop', true);
                if (!file_exists($template_path)) {
                    $template_path = wa()->getAppPath('plugins/qdiscount/templates/FrontendProduct.html', 'shop');
                }
                $html = $view->fetch($template_path);
                return $html;
            }
        }
    }

    public function orderCalculateDiscount($params) {

        if ($this->getSettings('status')) {
            $qdiscount_model = new shopQdiscountPluginModel();
            $discount = 0;
            foreach ($params['order']['items'] as $item) {
                if ($item['type'] == 'product') {
                    $qdiscount = $qdiscount_model->where('product_id = ' . (int) $item['product_id']
                                    . ' AND sku_id = ' . (int) $item['sku_id']
                                    . ' AND count <= ' . (int) $item['quantity'])
                            ->order('count DESC')
                            ->fetch();
                    if ($qdiscount) {
                        $discount += $item['quantity'] * ($item['price'] - $qdiscount['price']);
                    }
                }
            }

            if ($discount) {
                return $discount;
            }
        }
    }

}
