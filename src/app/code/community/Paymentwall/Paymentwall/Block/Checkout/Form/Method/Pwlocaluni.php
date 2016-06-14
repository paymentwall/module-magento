<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 *
 * Class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Local
 */
class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Pwlocaluni extends Paymentwall_Paymentwall_Block_Checkout_Form_Method_Abstract
{
    /**
     * Set template for block
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setPaymentModelName('pwlocaluni');
    }

    function getWidget()
    {
        $order = $this->getOrder();
        $return = array(
            'content' => '',
            'status' => false
        );

        if ($order) {
            try {
                $model = $this->getPaymentModel();
                $widget = $model->getPaymentWidget($order);

                // Get widget iframe
                $return['content'] = $widget->getHtmlCode(array(
                    'frameborder' => '0',
                    'width' => '100%',
                    'height' => '600'
                ));
                $return['status'] = true;
            } catch (Exception $e) {
                Mage::logException($e);
                $return['content'] = Mage::helper('paymentwall')->__('Errors, Please try again!');
            }
        } else {
            $return['content'] = Mage::helper('paymentwall')->__('Order invalid'); //should redirect back to homepage
        }

        return $return;
    }

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
}