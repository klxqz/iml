<?php

class shopImlPluginBackendSaveOrderController extends waJsonController {

    public function execute() {
        try {
            $order_id = waRequest::post('order_id', 0);
            $iml_params = waRequest::post('iml', array());
            $order_model = new shopOrderModel();
            $order_model->updateById($order_id, array('iml' => json_encode($iml_params)));
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
