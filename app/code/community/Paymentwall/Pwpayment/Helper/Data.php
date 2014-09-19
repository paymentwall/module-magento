<?php

/**
 * @author tridung <tridung@paymentwall.com>
 * @package PaymentWall_Pwpayment
 * 
 * Class Paymentwall_Pwpayment_Helper_Data
 */
class Paymentwall_Pwpayment_Helper_Data extends Mage_Core_Helper_Data
{
    private $methods = array(
        'paymentwall_pwpro' => 'pwpro'
    );

    /**
     * Check if the payment method provided is valid for this module
     * @param $code
     * @return bool
     */
    public function isValidMethodForModule($code)
    {
        return isset($this->methods[$code]);
    }

    /**
     * @param $method
     * @return false|Mage_Core_Model_Abstract
     */
    public function getPaymentMethodModel($method)
    {
        return Mage::getModel("paymentwall/method_{$method}");
    }

    /**
     * @param $method
     * @param $field
     * @return mixed
     */
    public function getPaymentMethodConfig($method, $field)
    {
        return Mage::getStoreConfig("payment/{$method}/{$field}");
    }

    /**
     * @return string
     */
    public function getProcessUrl()
    {
        return Mage::getUrl('paymentwall/payment/process');
    }
}