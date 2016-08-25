<?php

class shopQdiscountPlugin extends shopPlugin {

    public static $templates = array(
        'FrontendProduct' => array(
            'name' => 'Шаблон блока «Скидка от количества»',
            'tpl_path' => 'plugins/qdiscount/templates/actions/frontend/',
            'tpl_name' => 'FrontendProduct',
            'tpl_ext' => 'html',
            'public' => false
        ),
    );

    public function saveSettings($settings = array()) {
        $route_hash = waRequest::post('route_hash');
        $route_settings = waRequest::post('route_settings');

        if ($routes = $this->getSettings('routes')) {
            $settings['routes'] = $routes;
        } else {
            $settings['routes'] = array();
        }
        $settings['routes'][$route_hash] = $route_settings;
        $settings['route_hash'] = $route_hash;
        parent::saveSettings($settings);


        $templates = waRequest::post('templates');
        foreach ($templates as $template_id => $template) {
            $s_template = self::$templates[$template_id];
            if (!empty($template['reset_tpl'])) {
                $tpl_full_path = $s_template['tpl_path'] . $route_hash . '.' . $s_template['tpl_name'] . '.' . $s_template['tpl_ext'];
                $template_path = wa()->getDataPath($tpl_full_path, $s_template['public'], 'shop', true);
                @unlink($template_path);
            } else {
                $tpl_full_path = $s_template['tpl_path'] . $route_hash . '.' . $s_template['tpl_name'] . '.' . $s_template['tpl_ext'];
                $template_path = wa()->getDataPath($tpl_full_path, $s_template['public'], 'shop', true);
                if (!file_exists($template_path)) {
                    $tpl_full_path = $s_template['tpl_path'] . $s_template['tpl_name'] . '.' . $s_template['tpl_ext'];
                    $template_path = wa()->getAppPath($tpl_full_path, 'shop');
                }
                $content = file_get_contents($template_path);
                if (!empty($template['template']) && $template['template'] != $content) {
                    $tpl_full_path = $s_template['tpl_path'] . $route_hash . '.' . $s_template['tpl_name'] . '.' . $s_template['tpl_ext'];
                    $template_path = wa()->getDataPath($tpl_full_path, $s_template['public'], 'shop', true);
                    $f = fopen($template_path, 'w');
                    if (!$f) {
                        throw new waException('Не удаётся сохранить шаблон. Проверьте права на запись ' . $template_path);
                    }
                    fwrite($f, $template['template']);
                    fclose($f);
                }
            }
        }
    }

    public function backendProduct($product) {
        if ($this->getSettings('status')) {
            $view = wa()->getView();
            $view->assign('product', $product);
            $html = $view->fetch('plugins/qdiscount/templates/actions/backend/BackendProduct.html');
            return array('edit_section_li' => $html);
        }
    }

    public function frontendProduct($product) {
        if (!$this->getSettings('status')) {
            return false;
        }

        if (shopQdiscountHelper::getRouteSettings(null, 'status')) {
            $route_settings = shopQdiscountHelper::getRouteSettings();
        } elseif (shopQdiscountHelper::getRouteSettings(0, 'status')) {
            $route_settings = shopQdiscountHelper::getRouteSettings(0);
        } else {
            return false;
        }

        if ($route_settings['frontend_product_output']) {
            return array($route_settings['frontend_product_output'] => self::display($product));
        }
    }

    public static function display($product) {
        $plugin = wa()->getPlugin('qdiscount');
        if (!$plugin->getSettings('status')) {
            return false;
        }
        $route_hash = null;
        if (shopQdiscountHelper::getRouteSettings(null, 'status')) {
            $route_hash = shopQdiscountHelper::getCurrentRouteHash();
            $route_settings = shopQdiscountHelper::getRouteSettings();
        } elseif (shopQdiscountHelper::getRouteSettings(0, 'status')) {
            $route_hash = 0;
            $route_settings = shopQdiscountHelper::getRouteSettings(0);
        } else {
            return false;
        }

        $qdiscount_model = new shopQdiscountPluginModel();
        $items = $qdiscount_model->where("product_id = '" . (int) $product['id'] . "' AND route_hash = '" . $route_hash . "'")
                ->order('sku_id ASC, count ASC')
                ->fetchAll();

        if ($items) {
            foreach ($items as &$item) {
                $item['price'] = (float) shop_currency($item['price'], $product['currency'], null, false);
            }
            unset($item);
            $view = wa()->getView();
            $view->assign('items', $items);
            $view->assign('product', $product);

            $template = shopQdiscountHelper::getRouteTemplates($route_hash, 'FrontendProduct');
            $html = $view->fetch('string:' . $template['template']);
            return $html;
        }
    }

    public function orderCalculateDiscount($params) {
        if (!$this->getSettings('status')) {
            return false;
        }

        if (shopQdiscountHelper::getRouteSettings(null, 'status')) {
            $route_hash = shopQdiscountHelper::getCurrentRouteHash();
            $route_settings = shopQdiscountHelper::getRouteSettings();
        } elseif (shopQdiscountHelper::getRouteSettings(0, 'status')) {
            $route_hash = 0;
            $route_settings = shopQdiscountHelper::getRouteSettings(0);
        } else {
            return false;
        }

        $qdiscount_model = new shopQdiscountPluginModel();
        $discount = array();
        foreach ($params['order']['items'] as $item_id => $item) {
            if ($item['type'] == 'product') {
                $qdiscount = $qdiscount_model->where("product_id = '" . (int) $item['product_id'] . "'"
                                . " AND sku_id = '" . (int) $item['sku_id'] . "'"
                                . " AND count <= '" . (int) $item['quantity'] . "'"
                                . " AND route_hash = '" . $route_hash . "'")
                        ->order('count DESC')
                        ->fetch();
                if ($qdiscount) {
                    $discount['items'][$item_id] = array(
                        'discount' => $item['quantity'] * ($item['price'] - (float) shop_currency($qdiscount['price'], $item['product']['currency'], null, false)),
                        'description' => "Скидка от плагина  «Скидка от количества»",
                    );
                }
            }
        }

        return $discount;
    }

}
