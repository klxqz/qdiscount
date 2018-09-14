<?php

class shopQdiscountPluginSettingsAction extends waViewAction {

    public function execute() {
        $this->view->assign(array(
            'templates' => shopQdiscountPlugin::$templates,
            'plugin' => wa()->getPlugin('qdiscount'),
            'route_hashs' => shopQdiscountRouteHelper::getRouteHashs(),
        ));
    }

}
