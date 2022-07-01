<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 */

/**
 * Class Paymentwall_Payment_PaymentController
 */
class Paymentwall_Paymentwall_UserCountryController extends Mage_Core_Controller_Front_Action
{
    protected $paymentModel;

    public function getUserCountryAction()
    {
        $result = Mage::getModel('paymentwall/method_pwlocal')->getUserCountryByRemoteAddress();
        $this->getResponse()->setBody($result);
    }

}