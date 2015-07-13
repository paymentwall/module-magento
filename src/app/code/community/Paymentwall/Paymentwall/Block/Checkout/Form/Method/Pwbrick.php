<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 *
 * Class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Pwbrick
 */
class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Pwbrick extends Paymentwall_Paymentwall_Block_Checkout_Form_Method_Abstract
{
    /**
     * Set template for block
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setPaymentModelName('pwbrick');
        $this->setTemplate('paymentwall/checkout/form/method/pwbrick.phtml');
    }

    /**
     * Get merchant public key from configuration
     * @return string|null
     */
    public function getPublicKey()
    {
        if ($this->getPaymentModel()) {
            return $this->getPaymentModel()->getConfigData('paymentwall_public_key');
        }
        return null;
    }

    /**
     * Get private key from configuration
     * @return string|null
     */
    public function getPrivateKey()
    {
        if ($this->getMethod()) {
            return $this->getPaymentModel()->getConfigData('paymentwall_private_key');
        }
        return null;
    }


    /**
     * Retrieve payment configuration object
     *
     * @return Mage_Payment_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $type = $this->_getConfig()->getCcTypes();
        unset($type['OT']);
        unset($type['SM']);
        unset($type['SO']);
        return $type;
    }

}