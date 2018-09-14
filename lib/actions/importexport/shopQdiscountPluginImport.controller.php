<?php

class shopQdiscountPluginImportController extends waLongActionController {

    const STAGE_PRODUCTS = 'products';

    private static $models = array();

    protected function preExecute() {
        $this->getResponse()->addHeader('Content-type', 'application/json');
        $this->getResponse()->sendHeaders();
    }

    protected $steps = array(
        self::STAGE_PRODUCTS => 'Импорт скидка от количества',
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

            $filepath = wa()->getCachePath('plugins/qdiscount/import-qdiscount.csv', 'shop');
            if (!file_exists($filepath)) {
                throw new waException('Ошибка загрузки файла');
            }

            $this->data['import_filepath'] = $filepath;

            $this->data['profile_id'] = $profile_id;
            $this->data['profile_config'] = $profile_config;

            $this->data['delimiter'] = $profile_config['delimiter'];
            $this->data['enclosure'] = $profile_config['enclosure'];

            $this->data['timestamp'] = time();

            $stages = array_keys($this->steps);

            $this->data['count'] = array_fill_keys($stages, 0);

            $f = fopen($this->data['import_filepath'], "r");
            $count = 0;
            while (($data = fgetcsv($f, null, $this->data['delimiter'], $this->data['enclosure'])) !== FALSE) {
                if ($data[0] == iconv('UTF-8', 'CP1251', 'ID Товара')) {
                    continue;
                }
                $count++;
            }
            fclose($f);
            $this->data['file_offset'] = 0;

            $this->data['count'][self::STAGE_PRODUCTS] = $count;

            $this->data['route_hash'] = array_merge(array(0 => 'Общие настройки для всех поселений'), shopQdiscountRouteHelper::getRouteHashs());
            $this->data['types'] = array(
                'new_price' => 'Новая цена',
                'currency' => 'Скидка в валюте',
                'percent' => 'Скидка в процентах',
            );

            $this->data['current'] = array_fill_keys($stages, 0);
            $this->data['processed_count'] = array_fill_keys($stages, 0);
            $this->data['stage'] = reset($stages);

            $this->data['error'] = null;
            $this->data['stage_name'] = $this->steps[$this->data['stage']];
            $this->data['memory'] = memory_get_peak_usage();
            $this->data['memory_avg'] = memory_get_usage();
        } catch (waException $ex) {
            echo json_encode(array('error' => $ex->getMessage(),));
            exit;
        }
    }

    public function stepProducts() {
        $f = fopen($this->data['import_filepath'], "r");
        fseek($f, $this->data['file_offset']);

        $qdiscount_model = $this->getModel('shopQdiscountPluginModel');

        if (($data = fgetcsv($f, null, $this->data['delimiter'], $this->data['enclosure'])) !== FALSE) {
            if ($data[0] != iconv('UTF-8', 'CP1251', 'ID Товара')) {
                $key = array(
                    'product_id' => $data[0],
                    'route_hash' => array_search(iconv('CP1251', 'UTF-8', $data[4]), $this->data['route_hash']),
                );
                if ($data[2]) {
                    $key['sku_id'] = $data[2];
                }

                $update = array(
                    'count' => $data[5],
                    'type' => array_search($data[6], $this->data['types']),
                    'price' => $data[7],
                    'currency' => $data[8],
                );

                if ($qdiscount_model->getByField($key)) {
                    $qdiscount_model->updateByField($key, $update);
                } else {
                    $qdiscount_model->insert(array_merge($key, $update));
                }
            }

            $this->data['current'][self::STAGE_PRODUCTS] ++;
        }
        $this->data['file_offset'] = ftell($f);
        fclose($f);


        if ($this->data['current'][self::STAGE_PRODUCTS] > $this->data['count'][self::STAGE_PRODUCTS]) {
            $this->data['processed_count'][self::STAGE_PRODUCTS] = 1;
        }
    }

    public function getModel($model_name) {
        if (!class_exists($model_name)) {
            throw new waException(sprintf('Модель %s не найдена', $model_name));
        }
        if (!isset(self::$models[$model_name])) {
            self::$models[$model_name] = new $model_name();
        }
        return self::$models[$model_name];
    }

}
