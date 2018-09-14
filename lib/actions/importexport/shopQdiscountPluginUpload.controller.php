<?php

class shopQdiscountPluginUploadController extends waJsonController {

    public function execute() {
        try {
            $file = waRequest::file('files');
            if ($file->uploaded()) {
                $filepath = wa()->getCachePath('plugins/qdiscount/import-qdiscount.csv', 'shop');
                $file->moveTo($filepath);
            } else {
                throw new waException('Ошибка загрузки файла');
            }
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }

}
