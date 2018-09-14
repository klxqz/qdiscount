<?php

class shopQdiscountPluginSettingsRouteAction extends waViewAction {

    public function execute() {
        $route_hash = waRequest::get('route_hash');
        $view = wa()->getView();
        $view->assign(array(
            'route_hash' => $route_hash,
            'route_settings' => shopQdiscountRouteHelper::getRouteSettings($route_hash),
            'templates' => shopQdiscountRouteHelper::getRouteTemplates($route_hash),
        ));
    }

}
