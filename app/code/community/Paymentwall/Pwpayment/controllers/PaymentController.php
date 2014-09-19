<?php

/**
 * @author tridung <tridung@paymentwall.com>
 * @package Paymentwall_Pwpayment
 */

if (!class_exists('Paymentwall_Base')) {
    require_once dirname(
            dirname(__FILE__)
        ) . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'paymentwall-sdk' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'paymentwall.php';
}

/**
 * Class Paymentwall_Pwpayment_PaymentController
 */
class Paymentwall_Pwpayment_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Action that handles pingback call from paymentwall system
     * @return string
     */
    public function pingAction()
    {
        // we should get the data here via $_POST
        $result = array('success' => false);
        $pingback = new Paymentwall_Pingback($_POST, $_SERVER['REMOTE_ADDR']);
        if ($pingback->validate()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($pingback->getProductId());
            if ($order->getId()) {
                try {
                    $paymentModel = $order->getPayment()->getMethodInstance();
                    $paymentModel->setCurrentOrder($order)->processPendingPayment($pingback);
                    $result['success'] = true;
                } catch (Exception $e) {
                    Mage::log($e->getMessage());
                    $result['message'] = "Internal server error";
                }
            } else {
                $result['message'] = "Invalid order";
            }
        } else {
            $result['message'] = "Invalid pingback";
        }

        die(json_encode($result));
    }
}