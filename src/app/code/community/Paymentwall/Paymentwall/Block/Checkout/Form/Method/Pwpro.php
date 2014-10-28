<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 * 
 * Class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Pwpro
 */
class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Pwpro extends Paymentwall_Paymentwall_Block_Checkout_Form_Method_Abstract
{
    /**
     * Set template for block
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paymentwall/checkout/form/method/pwpro.phtml');
    }

    /**
     * Get merchant public key from configuration
     * @return string|null
     */
    public function getPublicKey()
    {
        if ($this->getMethod()) {
            return $this->getMethod()->getConfigData('public_key');
        }

        return null;
    }

    /**
     * Get API key from configuration
     * @return string|null
     */
    public function getApiKey()
    {
        if ($this->getMethod()) {
            return $this->getMethod()->getConfigData('api_key');
        }
        return null;
    }
}