<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall_Paymentwall
 */

if (!class_exists('Paymentwall_Base')) {
    require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'paymentwall-sdk' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'paymentwall.php';
}

/**
 * Class Paymentwall_Paymentwall_Model_Method_Abstract
 */
class Paymentwall_Paymentwall_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * @param string $code
     */
    public function __construct($code = '')
    {
        if ($code) {
            $this->_code          = 'paymentwall_' . $code;
            $this->_formBlockType = 'paymentwall/checkout_form_method_' . $code;
            $this->_infoBlockType = 'paymentwall/checkout_info_method_' . $code;
            $this->setData('original_code', $code);
        }
    }
}