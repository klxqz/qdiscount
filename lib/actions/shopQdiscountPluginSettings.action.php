<?php

class shopQdiscountPluginSettingsAction extends waViewAction {

    public function execute() {
        $this->view->assign(array(
            'templates' => shopQdiscountPlugin::$templates,
            'settings' => wa()->getPlugin('qdiscount')->getSettings(),
            'route_hashs' => shopQdiscountHelper::getRouteHashs(),
        ));
    }

}
