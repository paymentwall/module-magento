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
        $defaultResult = [];
        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setBody(json_encode($defaultResult));
            return;
        }
        $result = Mage::getModel('paymentwall/method_pwlocal')->getUserCountryFromApi();
        $this->getResponse()->setBody($result);
    }

}