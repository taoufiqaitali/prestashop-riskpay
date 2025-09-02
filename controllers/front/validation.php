<?php

class RiskpayValidationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        // Get callback data (RiskPay sends GET or POST)
        $data = array_merge($_GET, $_POST);
        PrestaShopLogger::addLog(json_encode($data), 1, null, null, null, true);
        // Log or process callback data
        if (isset($data['order_id'], $data['address_in'])) {
            $order_id = (int)$data['order_id'];
            $order = new Order($order_id);
            if ($order && Validate::isLoadedObject($order) && $order->getCurrentState() !== Configuration::get('PS_OS_PAYMENT')) {
                $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                /*if (!empty($data['address_in'])) {
                    $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                } else {
                    $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
                }*/
            }
        }
        // Respond to RiskPay
        header('Content-Type: application/json');
        echo json_encode(['result' => 'ok']);
        exit;
    }
}
