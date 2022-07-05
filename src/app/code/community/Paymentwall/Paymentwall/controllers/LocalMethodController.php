<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 */

/**
 * Class Paymentwall_Payment_PaymentController
 */
class Paymentwall_Paymentwall_LocalMethodController extends Mage_Core_Controller_Front_Action
{
    public function getLocalMethodAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }
        $countryCode = $this->getRequest()->get('countryCode');
        if ($countryCode) {
            $result = Mage::getModel('paymentwall/method_pwlocal')->getLocalMethods($countryCode);
            $this->getResponse()->setBody($result);
        }
    }

}