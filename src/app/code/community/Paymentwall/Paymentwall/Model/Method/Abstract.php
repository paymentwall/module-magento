<?php

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall_Paymentwall
 */

if (!class_exists('Paymentwall_Base')) {
    require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'paymentwall-php' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'paymentwall.php';
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


    /**
     * Process pending payment
     * @param Paymentwall_Pingback $pingback
     * @throws Exception
     * @return void
     */
    public function processPendingPayment(Paymentwall_Pingback $pingback)
    {
        $order = $this->getCurrentOrder();
        if ($order->getState() === Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW || $order->getState() === Mage_Sales_Model_Order::STATE_NEW) {
            if ($pingback->isDeliverable()) {
                $this->makeInvoice();
            } elseif ($pingback->isCancelable()) {
                $reason = $pingback->getParameter('reason');
                $order->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    $reason == self::REASON_ORDER_FRAUD || $reason == self::REASON_CC_FRAUD ? Mage_Sales_Model_Order::STATUS_FRAUD : true
                );
                $order->save();
            }
        } else {
            $incrementId = $order->getIncrementId();
            throw new Exception("This order {$incrementId} is not put in PENDING REVIEW STATE", 1);
        }
    }
    
    
    private function makeInvoice()
    {
        $order = $this->getCurrentOrder();
        if ($order) {
            $invoice = $order->prepareInvoice()
                ->setTransactionId($order->getId())
                ->addComment("Invoice created by PaymentWall Paymentwall module")
                ->register()
                ->pay();

            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transactionSave->save();

            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
        }
    }
    
    
}

    
    