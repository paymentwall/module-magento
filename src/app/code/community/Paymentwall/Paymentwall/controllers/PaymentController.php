<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 */

/**
 * Class Paymentwall_Payment_PaymentController
 */
class Paymentwall_Paymentwall_PaymentController extends Mage_Core_Controller_Front_Action
{
    const ORDER_STATUS_AFTER_PINGBACK_SUCCESS = 'processing';

    /**
     * Action that handles pingback call from paymentwall system
     * @return string
     */
    public function ipnAction()
    {
        $result = Mage::getModel('paymentwall/pingback')->handlePingback();
        $this->getResponse()->setBody($result);
    }

    /**
     * Show Paymentwall widget
     * For Pw Local
     */
    public function pwlocalAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function pwlocaluniAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * 3Ds Processing
     */
    public function threedsAction()
    {
        $chargeId = $this->getRequest()->get('brick_charge_id');
        $secureToken = $this->getRequest()->get('brick_secure_token');
        $orderId = Mage::getModel('core/session')->getChargeOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $chargeData = json_decode(Mage::getModel('core/session')->getChargeData(), true);

        if (
            $chargeId
            && $secureToken
            && $chargeData
            && $order->getId()
        ) {
            $chargeData['charge_id'] = $chargeId;
            $chargeData['secure_token'] = $secureToken;

            try {
                if (Mage::getModel('paymentwall/method_pwbrick')->processOrderAfter3Ds($order, $chargeData)) {
                    $url = Mage::getUrl('checkout/onepage/success', array('_query' => '_secure=true'));
                    Mage::app()->getResponse()->setRedirect($url)->sendResponse();
                    die;
                }
            } catch (Mage_Exception $e) {
                Mage::getSingleton('core/session')->addError($e->getMessage());
            }
            
            $url = Mage::getUrl('/');
            Mage::app()->getResponse()->setRedirect($url)->sendResponse();
        } elseif ($secureForm = Mage::getModel('core/session')->getSecureFormHtml()) {
            $this->getResponse()->setBody($secureForm);
        }
    }

    /**
     * Handle ajax payment listener on Widget page
     * For Pw Local
     */
    public function ajaxPwlocalAction()
    {
        // Get current order id
        $curOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $response = array(
            'status' => 0,
            'url' => '',
            'message' => ''
        );

        if ($curOrderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($curOrderId);
            if ($order->getId()) {
                $response['status'] = $order->getStatus() == self::ORDER_STATUS_AFTER_PINGBACK_SUCCESS
                    ? 1 : 0;
                // Get success page redirect url
                $response['url'] = '';
            } else {
                $response['status'] = 2; // Error
                $response['message'] = 'Order Invalid';
            }
        }
        if ($response['status'] == 1) {
            $response['message'] = "<h3>{$this->__("Payment Processed")}</h3>";
            // Clear shopping cart
            Mage::getSingleton('checkout/cart')->truncate();
        }

        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($response));
    }
}