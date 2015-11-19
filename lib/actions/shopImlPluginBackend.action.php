<?php

/**
 * @author wa-plugins.ru <support@wa-plugins.ru>
 * @link http://wa-plugins.ru/
 */
class shopImlPluginBackendAction extends waViewAction {

    public function execute() {
        $this->setLayout(new shopImlPluginBackendLayout());
    }

}
