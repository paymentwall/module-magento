<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 * 
 * Class Paymentwall_Paymentwall_Block_Checkout_Info_Method_Pwpro
 */
class Paymentwall_Paymentwall_Block_Checkout_Info_Method_Pwpro extends Mage_Checkout_Block_Onepage_Payment_Info
{
    protected function _construct()
    {
        $this->setData('template', 'paymentwall/Paymentwall/checkout/info/method/pwpro.phtml');
        parent::_construct();
    }

    public function setInfo($info)
    {
        $this->setData('info', $info);
        $this->setData('method', $info->getMethodInstance());
        return $this;
    }
}