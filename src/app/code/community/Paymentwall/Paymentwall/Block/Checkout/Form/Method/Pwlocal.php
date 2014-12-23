<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 * 
 * Class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Local
 */

class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Pwlocal extends Paymentwall_Paymentwall_Block_Checkout_Form_Method_Abstract
{
    /**
     * Set template for block
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paymentwall/checkout/form/method/pwlocal.phtml');
    }
}