<?php
class Paymentwall_Paymentwall_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_isGateway               = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;

    protected $_code = 'paymentwall';

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('paymentwall/index/redirect', array('_secure' => true));
    }
}
