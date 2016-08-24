<?php

class shopQdiscountPluginSettingsRouteAction extends waViewAction {

    public function execute() {
        $route_hash = waRequest::get('route_hash');
        $view = wa()->getView();
        $view->assign(array(
            'route_hash' => $route_hash,
            'route_settings' => shopQdiscountHelper::getRouteSettings($route_hash),
            'templates' => shopQdiscountHelper::getRouteTemplates($route_hash),
        ));
    }

}
