<?php

class shopQdiscountPluginExportController extends waLongActionController {

    const STAGE_PRODUCTS = 'products';

    private static $models = array();

    protected function preExecute() {
        $this->getResponse()->addHeader('Content-type', 'application/json');
        $this->getResponse()->sendHeaders();
    }

    protected $steps = array(
        self::STAGE_PRODUCTS => 'Экспорт скидка от количества',
    );

    public function execute() {
        try {
            set_error_handler(array($this, 'errHandler'));
            parent::execute();
        } catch (waException $ex) {
            if ($ex->getCode() == '302') {
                echo json_encode(array('warning' => $ex->getMessage()));
            } else {
                echo json_encode(array('error' => $ex->getMessage()));
            }
        }
    }

    public function errHandler($errno, $errmsg, $filename, $linenum) {
        $error_message = sprintf('File %s line %s: %s (%s)', $filename, $linenum, $errmsg, $errno);
        waLog::log($error_message, 'qdiscount-errors.log');
    }

    protected function isDone() {
        $done = true;
        foreach ($this->data['processed_count'] as $stage => $done) {
            if (!$done) {
                $done = false;
                break;
            }
        }
        return $done;
    }

    private function getNextStep($current_key) {
        $array_keys = array_keys($this->steps);
        $current_key_index = array_search($current_key, $array_keys);
        if (isset($array_keys[$current_key_index + 1])) {
            return $array_keys[$current_key_index + 1];
        } else {
            return false;
        }
    }

    protected function step() {
        $stage = $this->data['stage'];
        if (!empty($this->data['processed_count'][$stage])) {
            $stage = $this->data['stage'] = $this->getNextStep($this->data['stage']);
        }

        $method_name = 'step' . ucfirst($stage);
        if (method_exists($this, $method_name)) {
            if (isset($this->data['profile_config']['step'][$stage]) && $this->data['profile_config']['step'][$stage] == 0) {
                $this->data['processed_count'][$stage] = 1;
            } else {
                $this->$method_name();
            }
        } else {
            throw new waException('Неизвестный метод ' . $method_name);
        }

        return true;
    }

    protected function finish($filename) {
        $this->info();
        if ($this->getRequest()->post('cleanup')) {
            $profile_id = $this->data['profile_id'];
            $profile_helper = new shopImportexportHelper('qdiscount');
            $profile = $profile_helper->getConfig($profile_id);
            $config = $profile['config'];
            $config['last_time'] = $this->data['timestamp'];
            $profile_helper->setConfig($config, $profile_id);
            return true;
        }
        return false;
    }

    protected function report() {
        $report = '<div class="successmsg"><i class="icon16 yes"></i>';
        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
            $interval = sprintf(_w('%02d hr %02d min %02d sec'), floor($interval / 3600), floor($interval / 60) % 60, $interval % 60);
            $report .= ' ' . sprintf(_w('(total time: %s)'), $interval);
        }
        $report .= '&nbsp;</div>';
        $report .= '<div><a href="?plugin=qdiscount&module=download&filename=' . $this->data['download_filename'] . '"><i class="icon16 download"></i>Скачать выгрузку</a></div>';
        return $report;
    }

    protected function info() {

        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }
        $stage = $this->data['stage'];
        $response = array(
            'time' => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId' => $this->processId,
            'progress' => 0.0,
            'ready' => $this->isDone(),
            'offset' => $this->data['current'][$stage],
            'count' => $this->data['count'][$stage],
            'stage_name' => $this->steps[$this->data['stage']] . ' - ' . $this->data['current'][$stage] . ($this->data['count'][$stage] ? ' из ' . $this->data['count'][$stage] : ''),
            'memory' => sprintf('%0.2fMByte', $this->data['memory'] / 1048576),
            'memory_avg' => sprintf('%0.2fMByte', $this->data['memory_avg'] / 1048576),
        );

        if ($this->data['count'][$stage]) {
            $response['progress'] = ($this->data['current'][$stage] / $this->data['count'][$stage]) * 100;
        }

        $response['progress'] = sprintf('%0.3f%%', $response['progress']);

        if ($this->getRequest()->post('cleanup')) {
            $response['report'] = $this->report();
        }

        echo json_encode($response);
    }

    protected function restore() {
        
    }

    protected function init() {
        try {
            $backend = (wa()->getEnv() == 'backend');
            $profiles = new shopImportexportHelper('qdiscount');
            if ($backend) {
                $profile_config = waRequest::post('settings', array(), waRequest::TYPE_ARRAY);
                $profile_id = $profiles->setConfig($profile_config);
            } else {
                $profile_id = waRequest::param('profile_id');
                if (!$profile_id || !($profile = $profiles->getConfig($profile_id))) {
                    throw new waException('Profile not found', 404);
                }
                $profile_config = $profile['config'];
            }
            $hash = shopImportexportHelper::getCollectionHash();
            $this->data['hash'] = $hash['hash'];

            $this->data['profile_id'] = $profile_id;
            $this->data['profile_config'] = $profile_config;

            $this->data['timestamp'] = time();

            $stages = array_keys($this->steps);

            $this->data['count'] = array_fill_keys($stages, 0);

            $collection = new shopProductsCollection($this->data['hash']);
            $products = $collection->getProducts('*', 0, 999999);

            $this->data['product_ids'] = array_keys($products);

            $qdiscount_model = $this->getModel('shopQdiscountPluginModel');

            if ($this->data['product_ids']) {
                $this->data['count'][self::STAGE_PRODUCTS] = $qdiscount_model->select('*')->where("product_id IN ('" . implode("','", $this->data['product_ids']) . "')")->query()->count();
            } else {
                $this->data['count'][self::STAGE_PRODUCTS] = 0;
            }

            $this->data['current'] = array_fill_keys($stages, 0);
            $this->data['processed_count'] = array_fill_keys($stages, 0);
            $this->data['stage'] = reset($stages);

            $this->data['error'] = null;
            $this->data['stage_name'] = $this->steps[$this->data['stage']];
            $this->data['memory'] = memory_get_peak_usage();
            $this->data['memory_avg'] = memory_get_usage();

            $this->data['download_filename'] = 'qdiscount-export_' . date('d-m-Y-H-i-s') . '.csv';
            $this->data['download_file'] = wa()->getTempPath('plugins/qdiscount/' . $this->data['download_filename']);
            if (file_exists($this->data['download_file'])) {
                @unlink($this->data['download_file']);
            }
            if (!($f = fopen($this->data['download_file'], 'w+'))) {
                throw new waException('Ошибка создания файла отчета ' . $this->data['download_file']);
            }



            $this->data['route_hash'] = array_merge(array(0 => 'Общие настройки для всех поселений'), shopQdiscountRouteHelper::getRouteHashs());
            $this->data['types'] = array(
                'new_price' => 'Новая цена',
                'currency' => 'Скидка в валюте',
                'percent' => 'Скидка в процентах',
            );
            $data = array(
                'product_id' => iconv('UTF-8', 'CP1251', 'ID Товара'),
                'product_name' => iconv('UTF-8', 'CP1251', 'Название товара'),
                'sku_id' => iconv('UTF-8', 'CP1251', 'ID Артикула'),
                'sku_name' => iconv('UTF-8', 'CP1251', 'Артикул'),
                'route_hash' => iconv('UTF-8', 'CP1251', 'Поселение'),
                'count' => iconv('UTF-8', 'CP1251', 'Количество'),
                'type' => iconv('UTF-8', 'CP1251', 'Тип скидки'),
                'price' => iconv('UTF-8', 'CP1251', 'Стоимость'),
                'currency' => iconv('UTF-8', 'CP1251', 'Валюта'),
            );
            $f = fopen($this->data['download_file'], 'a');
            fputcsv($f, $data, ';', '"');
            fclose($f);
        } catch (waException $ex) {
            echo json_encode(array('error' => $ex->getMessage(),));
            exit;
        }
    }

    public function stepProducts() {
        $qdiscount_model = $this->getModel('shopQdiscountPluginModel');
        $offset = $this->data['current'][self::STAGE_PRODUCTS];
        $qdiscount = $qdiscount_model->select('*')->where("product_id IN ('" . implode("','", $this->data['product_ids']) . "')")->order('product_id')->limit("$offset, 1")->query()->fetchAssoc();

        $product_model = $this->getModel('shopProductModel');
        $product = $product_model->getById($qdiscount['product_id']);

        if ($qdiscount['sku_id']) {
            $sku_model = $this->getModel('shopProductSkusModel');
            $sku = $sku_model->getById($qdiscount['sku_id']);
        }


        $data = array(
            'product_id' => $qdiscount['product_id'],
            'product_name' => iconv('UTF-8', 'CP1251', $product['name']),
            'sku_id' => $qdiscount['sku_id'],
            'sku_name' => iconv('UTF-8', 'CP1251', ifset($sku['sku'])),
            'route_hash' => iconv('UTF-8', 'CP1251', ifset($this->data['route_hash'][$qdiscount['route_hash']])),
            'count' => iconv('UTF-8', 'CP1251', $qdiscount['count']),
            'type' => iconv('UTF-8', 'CP1251', ifset($this->data['types'][$qdiscount['type']])),
            'price' => iconv('UTF-8', 'CP1251', $qdiscount['price']),
            'currency' => iconv('UTF-8', 'CP1251', $qdiscount['currency']),
        );

        $f = fopen($this->data['download_file'], 'a');
        fputcsv($f, $data, ';', '"');
        fclose($f);

        $this->data['current'][self::STAGE_PRODUCTS] ++;
        if ($this->data['current'][self::STAGE_PRODUCTS] > $this->data['count'][self::STAGE_PRODUCTS]) {
            $this->data['processed_count'][self::STAGE_PRODUCTS] = 1;
        }
    }

    private function getModel($model_name) {
        if (!class_exists($model_name)) {
            throw new waException(sprintf('Модель %s не найдена', $model_name));
        }
        if (!isset(self::$models[$model_name])) {
            self::$models[$model_name] = new $model_name();
        }
        return self::$models[$model_name];
    }

}
