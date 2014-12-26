<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 */

if (!class_exists('Paymentwall_Base')) {
    require_once dirname(
            dirname(__FILE__)
        ) . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'paymentwall-sdk' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'paymentwall.php';
}

/**
 * Class Paymentwall_Payment_PaymentController
 */
class Paymentwall_Paymentwall_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get last order
     */
    protected function getOrder()
    {
        if (!$this->_order) {
            $session = Mage::getSingleton('checkout/session');
            $this->_order = $this->loadOrderById($session->getLastRealOrderId());
        }
        return $this->_order;
    }

    protected function loadOrderById($orderId)
    {
        return Mage::getModel('sales/order')->loadByIncrementId($orderId);
    }

    /**
     * Action that handles pingback call from paymentwall system
     * @return string
     */
    public function ipnAction()
    {
        // we should get the data here via $_POST
        $pingbackModel = Mage::getModel('paymentwall/pingback');
        $result = $pingbackModel->handlePingback();
        die($result);
    }

    public function pwlocalAction()
    {
        $order = $this->getOrder();
        if ($order) {
            try {
                $model = Mage::getModel('paymentwall/method_pwlocal');
                $model->initPaymentMethod($order);
                $widget = $model->getPaymentWidget($order);
                die($widget->getHtmlCode());
            } catch (Exception $e) {
                die($e->getMessage());
            }
        } else {
            die("wrong way"); //should redirect back to homepage
        }
    }
}