<?php

class shopQdiscountPluginSettingsAction extends waViewAction {

    protected $templates = array(
        'FrontendProduct' => array('name' => 'Шаблон в карточке товара', 'tpl_path' => 'plugins/qdiscount/templates/FrontendProduct.html'),
    );

    public function execute() {
        $plugin = wa()->getPlugin('qdiscount');
        $settings = $plugin->getSettings();


        foreach ($this->templates as &$template) {
            $template['full_path'] = wa()->getDataPath($template['tpl_path'], false, 'shop', true);
            if (file_exists($template['full_path'])) {
                $template['change_tpl'] = true;
            } else {
                $template['full_path'] = wa()->getAppPath($template['tpl_path'], 'shop');
                $template['change_tpl'] = false;
            }
            $template['template'] = file_get_contents($template['full_path']);
        }

        $this->view->assign('settings', $settings);
        $this->view->assign('templates', $this->templates);
        waSystem::popActivePlugin();
    }

}
