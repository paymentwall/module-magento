<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall_Pwpayment
 * 
 * Class Paymentwall_Pwpayment_Model_Observer
 */
class Paymentwall_Pwpayment_Model_Observer
{
    /**
     * @param $observer Varien_Event
     */
    public function addMoreDataToOrderPayment(Varien_Event $observer)
    {
        $source = $observer->getSource();
        $target = $observer->getTarget();
        if (Mage::helper('pwpayment')->isValidMethodForModule($target->getMethod())) {
            $target->setData('browser_domain', $source->getData('browser_domain'));
        }
    }
}