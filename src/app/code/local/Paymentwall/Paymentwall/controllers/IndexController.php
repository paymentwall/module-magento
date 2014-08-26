<?php
/**
 * @category    Paymentwall
 * @package     Paymentwall_Paymentwall
 * @copyright   Copyright (c) 2010 - 2013 Paymentwall (http://www.paymentwall.com)
 */

DEFINE('SECRET', Mage::getStoreConfig('payment/paymentwall/paymentwall_secret')); //Secret Key
//is set using graphical interface
DEFINE('APPKEY', Mage::getStoreConfig('payment/paymentwall/paymentwall_shop_id')); //Application Key

class Paymentwall_Paymentwall_IndexController extends Mage_Core_Controller_Front_Action
{   
    public function updateOrderState($type, $reason)
    {
        $order = $this->getOrder();
        if($type == "positive") {
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_COMPLETE);
        } else { //chargeback
            if(($reason == 2) || ($reason == 3)) {
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATUS_FRAUD);
            } else {
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_CANCELED);
            }
        }

        $order->save();
        return true;
    }
    
                
    private function loadOrderById($orderId)
    {
        return Mage::getModel('sales/order')->loadByIncrementId($orderId);
    }
    
    public function getOrder()
    {
        if (!$this->_order) {
            $session = Mage::getSingleton('checkout/session');
            $this->_order = $this->loadOrderById($session->getLastRealOrderId());
        }
        return $this->_order;
    }

    function checkResponse($response) {
        return preg_match('/^[a-z0-9]{32}$/', $response);
    }
    
    public function redirectAction()
    {
        require 'lib/lib/paymentwall.php';
        Paymentwall_Base::setApiType(Paymentwall_Base::API_GOODS);
        Paymentwall_Base::setAppKey(APPKEY); // available in your Paymentwall merchant area
        Paymentwall_Base::setSecretKey(SECRET); // available in your Paymentwall merchant area

        $order = $this->getOrder();

        $widget = new Paymentwall_Widget(
            $order->getCustomerEmail(),   // id of the end-user who's making the payment
            Mage::getStoreConfig('payment/paymentwall/paymentwall_widget_code'),        // widget code, e.g. p1; can be picked inside of your merchant account
            array(         // product details for Flexible Widget Call. To let users select the product on Paymentwall's end, leave this array empty
                new Paymentwall_Product(
                    $order->getIncrementId(),
                    $order->getGrandTotal(),
                    $order->getOrderCurrencyCode(),
                    'Order id #' . $order->getIncrementId(),
                    Paymentwall_Product::TYPE_FIXED
                )
            ),
            array(
                'email' => $order->getCustomerEmail(),
                'success_url' => Mage::getStoreConfig('payment/paymentwall/paymentwall_url'),
                'test_mode' => (int) Mage::getStoreConfig('payment/paymentwall/paymentwall_istest')
            )           // additional parameters
        );

        echo $widget->getHtmlCode();
    }

    public function ipnAction()
    {
        require 'lib/lib/paymentwall.php';
        Paymentwall_Base::setApiType(Paymentwall_Base::API_GOODS);
        Paymentwall_Base::setAppKey(APPKEY); // available in your Paymentwall merchant area
        Paymentwall_Base::setSecretKey(SECRET); // available in your Paymentwall merchant area

        $pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);

        $reason = $pingback->getParameter('reason');

        $productId = $pingback->getProductId();
        $order = $this->loadOrderById($productId);
        $this->_order = $order;
        if($order) {
            if ($pingback->validate()) {
                if ($pingback->isDeliverable()) {
                    $this->updateOrderState("positive", $reason);
                } else if ($pingback->isCancelable()) {
                    $this->updateOrderState("negative", $reason);
                }

                echo 'OK'; // Paymentwall expects response to be OK, otherwise the pingback will be resent
            } else {
                echo $pingback->getErrorSummary();
            }
        } else {
            echo 'Order not found!';
        }
    }
    
    public function confirmNotification()
    {
        return "OK";
    }
    
    private function successfulPayment(){
        $message = '
                You should now receive an email with the link to the widget!
                <br />
                In case you have not got an email, please check your spam folder.
                ';

        return $message;
    }
}
?>