<?php

/**
 * @author tridung <tridung@paymentwall.com>
 * @package Paymentwall_Pwpayment
 * 
 * Class Paymentwall_Pwpayment_Block_Checkout_Form_Method_Abstract
 */
class Paymentwall_Pwpayment_Block_Checkout_Form_Method_Abstract extends Mage_Payment_Block_Form
{
    /**
     * Get payment model
     * @return Paymentwall_Pwpayment_Model_Method_Abstract
     */
    public function getModel()
    {
        return $this->getPaymentModel();
    }

    /**
     * Get total amount of current order
     * @return mixed|null
     */
    public function getTotal()
    {
        return $this->getOrder() ? $this->getOrder()->getGrandTotal() : null;
    }

    /**
     * Get currency code of current order
     * @return string|null
     */
    public function getOrderCurrencyCode()
    {
        return $this->getOrder() ? $this->getOrder()->getOrderCurrencyCode() : null;
    }
}