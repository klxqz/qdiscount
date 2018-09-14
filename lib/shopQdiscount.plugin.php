<?php

class shopQdiscountPlugin extends shopPlugin {

    private static $frontend_products_off = false;
    public static $templates = array(
        'FrontendProduct' => array(
            'name' => 'Шаблон блока «Скидка от количества»',
            'tpl_path' => 'plugins/qdiscount/templates/handlers/frontend/',
            'tpl_name' => 'FrontendProduct',
            'tpl_ext' => 'html',
            'public' => false
        ),
        'qdiscount_css' => array(
            'name' => 'qdiscount.css',
            'tpl_path' => 'plugins/qdiscount/css/',
            'tpl_name' => 'qdiscount',
            'tpl_ext' => 'css',
            'public' => true
        ),
        'qdiscount_js' => array(
            'name' => 'qdiscount.js',
            'tpl_path' => 'plugins/qdiscount/js/frontend/',
            'tpl_name' => 'qdiscount',
            'tpl_ext' => 'js',
            'public' => true
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

        shopQdiscountRouteHelper::saveTemplates($route_hash, waRequest::post('templates'));
    }

    public static function isEnabled(&$route_hash = null) {
        if (!wa('shop')->getPlugin('qdiscount')->getSettings('status')) {
            return false;
        }
        if (shopQdiscountRouteHelper::getRouteSettings(null, 'status')) {
            $route_hash = shopQdiscountRouteHelper::getCurrentRouteHash();
            return shopQdiscountRouteHelper::getRouteSettings();
        } elseif (shopQdiscountRouteHelper::getRouteSettings(0, 'status')) {
            $route_hash = 0;
            return shopQdiscountRouteHelper::getRouteSettings(0);
        } else {
            return false;
        }
    }

    public function productSave($params) {
        if (!$this->getSettings('status') || !waRequest::post('qdiscount_backend_save')) {
            return;
        }
        $product = $params['data'];
        $discount_route_hash = waRequest::post('qdiscount_route_hash', array());
        $qdiscount_sku = waRequest::post('qdiscount_sku', array());
        $qdiscount_count = waRequest::post('qdiscount_count', array());
        $qdiscount_type = waRequest::post('qdiscount_type', array());
        $qdiscount_price = waRequest::post('qdiscount_price', array());
        $qdiscount_currency = waRequest::post('qdiscount_currency', array());

        $qdiscount_model = new shopQdiscountPluginModel();
        $qdiscount_model->deleteByField('product_id', $product['id']);

        $data = array();
        foreach ($discount_route_hash as $index => $route_hash) {
            if (empty($qdiscount_count[$index]) || empty($qdiscount_price[$index])) {
                continue;
            }
            $data[] = array(
                'route_hash' => $route_hash,
                'product_id' => $product['id'],
                'sku_id' => $qdiscount_sku[$index],
                'count' => $qdiscount_count[$index],
                'type' => $qdiscount_type[$index],
                'price' => $qdiscount_price[$index],
                'currency' => $qdiscount_currency[$index],
            );
        }
        $qdiscount_model->multiInsert($data);
    }

    public function backendProducts($params) {
        if (!$this->getSettings('status')) {
            return;
        }
        $plugin_url = $this->getPluginStaticUrl();

        $collection = new shopProductsCollection('qdiscount');
        $count = $collection->count();
        $sidebar_top_li_html = <<<HTML
<li id="s-qdiscount">
<script type="text/javascript" src = "{$plugin_url}js/backend/products.js"></script>
<a href = "#/products/hash=qdiscount">
    <span class="count">{$count}</span>
    <i class="icon16" style="background-image: url({$plugin_url}img/qdiscount.png);"></i>
    Скидка от количества
</a>
</li>
HTML;
        if ($params['info']['hash'] == 'qdiscount') {
            $toolbar_organize_li_html = '<li><a class="delete-qdiscount" href="#"><i class="icon16 cross"></i>Убрать скидку от количества</a></li>';
        } else {
            $toolbar_organize_li_html = '<li><a class="add-qdiscount" href="#"><i class="icon16 dollar"></i>Задать скидку от количества</a></li>';
        }

        return array(
            'sidebar_top_li' => $sidebar_top_li_html,
            'toolbar_organize_li' => ifset($toolbar_organize_li_html)
        );
    }

    public function backendProduct($product) {
        if (!$this->getSettings('status')) {
            return;
        }
        $plugin_url = $this->getPluginStaticUrl();

        $html = <<<HTML
<li class="qdiscount">
    <a href="#/product/{$product['id']}/edit/qdiscount/">Скидка от количества<span class="hint"></span>
        <span class="s-product-edit-tab-status"></span>
    </a>
    <span class="shop-tooltip"></span>
    <script src="{$plugin_url}js/backend/loadcontent.js?{$this->getVersion()}" type="text/javascript"></script>
</li>
HTML;
        return array('edit_section_li' => $html);
    }

    public function frontendProduct($product) {
        if (!($route_settings = self::isEnabled($route_hash))) {
            return;
        }

        $qdiscount_js_url = shopQdiscountRouteHelper::getRouteTemplateUrl('qdiscount_js', $route_hash) . '?' . $this->getVersion();
        $qdiscount_css_url = shopQdiscountRouteHelper::getRouteTemplateUrl('qdiscount_css', $route_hash) . '?' . $this->getVersion();

        waSystem::getInstance()->getResponse()->addJs(ltrim($qdiscount_js_url, '/'));
        waSystem::getInstance()->getResponse()->addCss(ltrim($qdiscount_css_url, '/'));

        if ($route_settings['frontend_product_output']) {
            return array($route_settings['frontend_product_output'] => self::display($product));
        }
    }

    public static function display($product) {
        if (!($route_settings = self::isEnabled($route_hash))) {
            return;
        }

        $qdiscount_model = new shopQdiscountPluginModel();
        $qdiscounts = $qdiscount_model->where("product_id = '" . (int) $product['id'] . "' AND route_hash = '" . $qdiscount_model->escape($route_hash) . "'")
                ->order('sku_id ASC, count ASC')
                ->fetchAll();



        if ($qdiscounts) {
            if (!empty($product['unconverted_currency'])) {
                $product_currency = $product['unconverted_currency'];
            } else {
                $product_currency = $product['currency'];
            }
            $currency = wa('shop')->getConfig()->getCurrency(true);
            $frontend_currency = wa('shop')->getConfig()->getCurrency(false);
            $sku_model = new shopProductSkusModel();
            foreach ($qdiscounts as &$qdiscount) {
                if ($qdiscount['type'] == 'new_price') {
                    if ($qdiscount['currency']) {
                        $price_value = self::shop_currency($qdiscount['price'], $qdiscount['currency'], $product_currency, false);
                    } else {
                        $price_value = $qdiscount['price'];
                    }
                } elseif ($qdiscount['type'] == 'currency') {
                    $sku = $sku_model->getById($qdiscount['sku_id']);
                    if ($qdiscount['currency']) {
                        $qdiscount['price'] = self::shop_currency($qdiscount['price'], $qdiscount['currency'], $product_currency, false);
                        $price_value = $sku['price'] - $qdiscount['price'];
                    } else {
                        $price_value = $sku['price'] - $qdiscount['price'];
                    }
                } elseif ($qdiscount['type'] == 'percent') {
                    $sku = $sku_model->getById($product['sku_id']);
                    $price_value = $sku['price'] - $sku['price'] * ($qdiscount['price'] / 100);
                }


                $price_value = self::shop_currency($price_value, $product_currency, $frontend_currency, false);
                $price_value = shopRounding::roundCurrency($price_value, $frontend_currency);
                $qdiscount['price'] = self::shop_currency($price_value, $frontend_currency, $currency, false);
            }
            unset($qdiscount);


            $view = wa()->getView();
            $view->assign(array(
                'qdiscounts' => $qdiscounts,
                'product' => $product,
                'product_form_selector' => ifset($route_settings['product_form_selector'], 'form#cart-form'),
                'product_price_selector' => ifset($route_settings['product_price_selector'], 'form#cart-form .add2cart .price'),
                'product_compare_price_selector' => ifset($route_settings['product_compare_price_selector'], 'form#cart-form .add2cart .compare-at-price'),
                'html_currency' => ifset($route_settings['html_currency'], '1'),
            ));

            $template = shopQdiscountRouteHelper::getRouteTemplates($route_hash, 'FrontendProduct');
            $html = $view->fetch('string:' . $template['template']);
            return $html;
        }
    }

    public function orderCalculateDiscount($params) {
        if (!($route_settings = self::isEnabled($route_hash)) || !empty($route_settings['price_replace'])) {
            return;
        }

        $discount = array();

        $qdiscount_model = new shopQdiscountPluginModel();
        $qdiscounts = $qdiscount_model->filter($route_hash, $params['order']['items']);

        if ($qdiscounts) {
            $frontend_currency = wa('shop')->getConfig()->getCurrency(false);
            foreach ($params['order']['items'] as $item_id => $item) {
                if ($item['type'] == 'product') {
                    $qdiscount = null;
                    $id = $item['product_id'] . '_' . $item['sku_id'];
                    if (empty($qdiscounts[$id])) {
                        $id = $item['product_id'] . '_0';
                    }
                    if (!empty($qdiscounts[$id])) {
                        $qdiscount = $qdiscounts[$id];

                        $price_value = 0;
                        if ($qdiscount['type'] == 'new_price') {
                            if ($qdiscount['currency']) {
                                $price_value = self::shop_currency($qdiscount['price'], $qdiscount['currency'], $frontend_currency, false);
                            } else {
                                $price_value = self::shop_currency($qdiscount['price'], $item['product']['unconverted_currency'], $frontend_currency, false);
                            }
                        } elseif ($qdiscount['type'] == 'currency') {
                            if ($qdiscount['currency']) {
                                $price_value = $item['price'] - self::shop_currency($qdiscount['price'], $qdiscount['currency'], $frontend_currency, false);
                            } else {
                                $price_value = $item['price'] - self::shop_currency($qdiscount['price'], $item['product']['unconverted_currency'], $frontend_currency, false);
                            }
                        } elseif ($qdiscount['type'] == 'percent') {
                            $price_value = $item['price'] - $item['price'] * ($qdiscount['price'] / 100);
                        }

                        $price_value = shopRounding::roundCurrency($price_value, $frontend_currency);

                        $discount['items'][$item_id] = array(
                            'discount' => $item['quantity'] * ($item['price'] - $price_value),
                            'description' => "Скидка от плагина  «Скидка от количества»",
                        );
                    }
                }
            }
        }
        return $discount;
    }

    public function frontendProducts(&$params) {
        if (self::$frontend_products_off) {
            return;
        }
        if (!empty($params['products'])) {
            $params['products'] = self::prepareProducts($params['products']);
        }
        if (!empty($params['skus'])) {
            $params['skus'] = self::prepareSkus($params['skus']);
        }
    }

    public static function prepareProducts($products = array()) {
        if (!($route_settings = self::isEnabled($route_hash)) || empty($route_settings['price_replace'])) {
            return $products;
        }

        $cart = new shopCart();
        self::$frontend_products_off = true;
        $items = $cart->items();
        self::$frontend_products_off = false;

        $qdiscount_model = new shopQdiscountPluginModel();
        $qdiscounts = $qdiscount_model->filter($route_hash, $items);

        $prices = array();
        foreach ($products as $product) {
            $prices[] = $product['price'];
        }

        if ($qdiscounts) {
            $frontend_currency = wa('shop')->getConfig()->getCurrency(false);
            $currency = wa('shop')->getConfig()->getCurrency(true);
            $sku_model = new shopProductSkusModel();
            foreach ($products as &$product) {
                $id = $product['id'] . '_' . $product['sku_id'];
                if (empty($qdiscounts[$id])) {
                    $id = $product['id'] . '_0';
                }
                if (!empty($qdiscounts[$id])) {
                    $qdiscount = $qdiscounts[$id];
                    if (!empty($product['unconverted_currency'])) {
                        $product_currency = $product['unconverted_currency'];
                    } else {
                        $product_currency = $product['currency'];
                    }

                    $price_value = 0;
                    if ($qdiscount['type'] == 'new_price') {
                        if ($qdiscount['currency']) {
                            $price_value = self::shop_currency($qdiscount['price'], $qdiscount['currency'], $product_currency, false);
                        } else {
                            $price_value = $qdiscount['price'];
                        }
                    } elseif ($qdiscount['type'] == 'currency') {
                        $sku = $sku_model->getById($product['sku_id']);
                        if ($qdiscount['currency']) {
                            $price_value = $sku['price'] - self::shop_currency($qdiscount['price'], $qdiscount['currency'], $product_currency, false);
                        } else {
                            $price_value = $sku['price'] - $qdiscount['price'];
                        }
                    } elseif ($qdiscount['type'] == 'percent') {
                        $sku = $sku_model->getById($product['sku_id']);
                        $price_value = $sku['price'] - $sku['price'] * ($qdiscount['price'] / 100);
                    }

                    if ($price_value > 0) {
                        $price_value = self::shop_currency($price_value, $product_currency, $frontend_currency, false);
                        $price_value = shopRounding::roundCurrency($price_value, $frontend_currency);
                        $product['price'] = self::shop_currency($price_value, $frontend_currency, $currency, false);
                    }
                }
            }
            unset($product);
        }
        return $products;
    }

    public static function prepareSkus($skus = array()) {
        if (!($route_settings = self::isEnabled($route_hash)) || empty($route_settings['price_replace'])) {
            return $skus;
        }

        $cart = new shopCart();
        self::$frontend_products_off = true;
        $items = $cart->items();
        self::$frontend_products_off = false;

        $qdiscount_model = new shopQdiscountPluginModel();
        $qdiscounts = $qdiscount_model->filter($route_hash, $items);


        if ($qdiscounts) {
            $product_model = new shopProductModel();
            $frontend_currency = wa('shop')->getConfig()->getCurrency(false);
            $currency = wa('shop')->getConfig()->getCurrency(true);
            foreach ($skus as &$sku) {
                $id = $sku['product_id'] . '_' . $sku['id'];
                if (empty($qdiscounts[$id])) {
                    $id = $sku['product_id'] . '_0';
                }
                if (!empty($qdiscounts[$id])) {
                    $qdiscount = $qdiscounts[$id];
                    $product = $product_model->getById($sku['product_id']);

                    if (!empty($sku['unconverted_currency'])) {
                        $sku['price'] = $sku['unconverted_price'];
                    }

                    $price_value = 0;
                    if ($qdiscount['type'] == 'new_price') {
                        if ($qdiscount['currency']) {
                            $price_value = self::shop_currency($qdiscount['price'], $qdiscount['currency'], $product['currency'], false);
                        } else {
                            $price_value = $qdiscount['price'];
                        }
                    } elseif ($qdiscount['type'] == 'currency') {
                        if ($qdiscount['currency']) {
                            $price_value = $sku['price'] - self::shop_currency($qdiscount['price'], $qdiscount['currency'], $product['currency'], false);
                        } else {
                            $price_value = $sku['price'] - $qdiscount['price'];
                        }
                    } elseif ($qdiscount['type'] == 'percent') {
                        $price_value = $sku['price'] - $sku['price'] * ($qdiscount['price'] / 100);
                    }
 
                    if ($price_value > 0) {
                        $price_value = self::shop_currency($price_value, $product['currency'], $frontend_currency, false);
                        $price_value = shopRounding::roundCurrency($price_value, $frontend_currency);
                        $sku['price'] = self::shop_currency($price_value, $frontend_currency, $product['currency'], false);

                        if (!empty($sku['unconverted_currency'])) {
                            unset($sku['unconverted_currency']);
                            $round_skus = array($sku['id'] => $sku);
                            shopRounding::roundSkus($round_skus);
                            $sku = array_pop($round_skus);
                        }
                    }
                }
            }
            unset($sku);
        }
        return $skus;
    }

    public static function shop_currency($n, $in_currency = null, $out_currency = null, $format = true) {
        /**
         * @var shopConfig $config
         */
        $config = wa('shop')->getConfig();

        // primary currency
        $primary = $config->getCurrency(true);

        // current currency (in backend - it's primary, in frontend - currency of storefront)
        $currency = $config->getCurrency(false);

        if (!$in_currency) {
            $in_currency = $primary;
        }
        if ($in_currency === true || $in_currency === 1) {
            $in_currency = $currency;
        }
        if (!$out_currency) {
            $out_currency = $currency;
        }

        if ($in_currency != $out_currency) {
            $currencies = wa('shop')->getConfig()->getCurrencies(array($in_currency, $out_currency));
            if (isset($currencies[$in_currency]) && $in_currency != $primary) {
                $n = $n * $currencies[$in_currency]['rate'];
            }
            if ($out_currency != $primary) {
                $n = $n / ifempty($currencies[$out_currency]['rate'], 1.0);
            }
        }
        if ($format === 'h') {
            return wa_currency_html($n, $out_currency);
        } elseif ($format) {
            return wa_currency($n, $out_currency);
        } else {
            return str_replace(',', '.', $n);
        }
    }

    public function productsCollection($params) {
        if (!$this->getSettings('status')) {
            return false;
        }
        $collection = $params['collection'];
        $hash = $collection->getHash();
        if ($hash[0] !== 'qdiscount') {
            return false;
        }
        $collection->addTitle('Скидка от количества');
        $collection->addWhere("`id` IN (SELECT `product_id` FROM `shop_qdiscount`)");
        return true;
    }

    /*
      public function frontendCategory($category) {
      if (!($route_settings = self::isEnabled($route_hash))) {
      return;
      }

      $view = wa()->getView();
      $filters = $view->getVars('filters');

      // Исправление минимальной максимальной цены в фильтре товаров
      if (!empty($filters['price'])) {
      $cart = new shopCart();
      self::$frontend_products_off = true;
      $items = $cart->items();
      self::$frontend_products_off = false;

      $qdiscount_model = new shopQdiscountPluginModel();
      $qdiscounts = $qdiscount_model->filter($route_hash, $items);

      if ($qdiscounts) {
      $min = array(
      $filters['price']['min']
      );
      $max = array(
      $filters['price']['max']
      );

      $currency = wa('shop')->getConfig()->getCurrency(true);
      $frontend_currency = wa('shop')->getConfig()->getCurrency(false);

      $collection = new shopProductsCollection('category/' . $category['id']);
      $skus_alias = $collection->addJoin('shop_product_skus', ':table.product_id = p.id', ":table.price_plugin_type_{$price['id']} = '{$type}'");


      $filters['price']['min'] = min($min);
      $filters['price']['max'] = max($max);
      $view->assign('filters', $filters);
      }
      }

      $products = $view->getVars('products');
      //Исправление цены после фильтрации shopFrontendCategoryAction::filterListSkus
      if ($products) {
      $product_ids = array();
      foreach ($products as $p_id => $p) {
      if ($p['sku_count'] > 1) {
      $product_ids[] = $p_id;
      }
      }
      if ($product_ids && $filters) {
      $tmp = array();
      foreach ($filters as $fid => $f) {
      if ($fid != 'price') {
      $fvalues = waRequest::get($f['code']);
      if ($fvalues && !isset($fvalues['min']) && !isset($fvalues['max'])) {
      $tmp[$fid] = $fvalues;
      }
      }
      }
      if ($tmp) {
      $products = $this->prepareProducts($products);
      wa('shop')->event('frontend_products', ref(array(
      'products' => &$products,
      )));
      $view->assign('products', $products);
      }
      }
      }
      } */
}
