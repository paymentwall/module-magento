<?php

/**
 * @author  Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall_Pwpayment
 *
 * Class Paymentwall_Pwpayment_Model_Method_Pwpro
 */
class Paymentwall_Pwpayment_Model_Method_Pwpro extends Paymentwall_Pwpayment_Model_Method_Abstract
{
    const REASON_CC_FRAUD = 2;
    const REASON_ORDER_FRAUD = 3;

    protected $_isInitializeNeeded = false;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canAuthorize = true;

    /**
     * Constructor method.
     * Set some internal properties
     */
    public function __construct()
    {
        parent::__construct('pwpro');
    }

    /**
     * Prepare data for payment
     * @param  Mage_Sales_Model_Order_Payment $payment
     * @return $this
     */
    public function preparePaymentData($payment, $amount)
    {
        $order = $payment->getOrder();
        $data  = array(
            'amount'          => $amount,
            'currency'        => $order->getOrderCurrencyCode(),
            'card[number]'    => $payment->getData('cc_number'),
            'card[cvv]'       => $payment->getData('cc_cid'),
            'card[exp_month]' => $payment->getData('cc_exp_month'),
            'card[exp_year]'  => $payment->getData('cc_exp_year'),
            'browser_domain'  => $payment->getData('browser_domain'),
            'plan'            => $order->getIncrementId()
        );

        $this->setData('payment_data', $data);
        $this->setCurrentOrder($order);
        return $this;
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
        if ($order->getState() === Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
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

    /**
     * Make invoice for paid order
     * @return void
     */
    protected function makeInvoice()
    {
        $order = $this->getCurrentOrder();
        if ($order) {
            $invoice = $order->prepareInvoice()
                ->setTransactionId($order->getId())
                ->addComment("Invoice created by PaymentWall PWpayment module")
                ->register()
                ->pay();

            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transactionSave->save();

            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    public function assignData($data)
    {
        $infoInstance = $this->getInfoInstance();
        $infoInstance->setData('cc_owner', $data->getData('cc_owner'))
            ->setData('cc_last4', $data->getData('cc_last_4'));

        return parent::assignData($data);
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function capture($payment, $amount)
    {
        $this->preparePaymentData($payment, $amount);
        $paymentData = $this->getData('payment_data');
        $order       = $payment->getOrder();
        $apiKey      = $this->getConfigData('api_key');
        if (!$apiKey) {
            Mage::throwException(Mage::helper('pwpayment')->__("API Key is not set!"));
        }
        Paymentwall_Base::setProApiKey($apiKey);
        $charge   = new Paymentwall_Pro_Charge($paymentData);
        $response = $charge->getPublicData();
        if ($charge->isCaptured()) {
            if ($charge->isRiskPending()) {
                // set state of order to PENDING REVIEW since the payment is put on review queue
                $payment->setIsTransactionPending(true);
            }
        } else {
            $payment->setIsTransactionPending(true);
            $payment->setIsFraudDetected(true);
        }

    }
}
