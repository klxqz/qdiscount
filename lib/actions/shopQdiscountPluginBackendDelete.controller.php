<?php

class shopQdiscountPluginBackendDeleteController extends waJsonController {

    public function execute() {
        $id = waRequest::post('id');
        $qdiscount_model = new shopQdiscountPluginModel();
        $qdiscount_model->deleteById($id);
    }

}
