<?php

/**
 * @author  Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall_Paymentwall
 *
 * Class Paymentwall_Paymentwall_Model_Method_Pwbrick
 */
class Paymentwall_Paymentwall_Model_Method_Pwbrick extends Paymentwall_Paymentwall_Model_Method_Abstract
{
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
        parent::__construct('pwbrick');
    }

    /**
     * Prepare data for payment
     * @param $payment
     * @param $amount
     * @return $this
     */
    public function prepareCardInfo($payment, $amount)
    {
        $order = $payment->getOrder();
        $info = $this->getInfoInstance();
        $this->setCurrentOrder($order);
        return array(
            'email' => $order->getBillingAddress()->getEmail(),
            'amount' => $amount,
            'currency' => $order->getOrderCurrencyCode(),
            'token' => $info->getAdditionalInformation('brick_token'),
            'fingerprint' => $info->getAdditionalInformation('brick_fingerprint'),
            'description' => 'Order #' . $order->getIncrementId(),
        );
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
                ->addComment("Invoice created by Paymentwall Brick")
                ->register()
                ->pay();

            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())

            ->setAdditionalInformation('brick_token', $data->getBrickToken())
            ->setAdditionalInformation('brick_fingerprint', $data->getBrickFingerprint());

        return $this;
    }

    /**
     * @param $payment
     * @param Varien_Object $amount
     * @return Mage_Payment_Model_Abstract|void
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $cardInfo = $this->prepareCardInfo($payment, $amount);
        $this->initPaymentwallConfig();
        $charge = new Paymentwall_Charge();
        $charge->create($cardInfo);
        $response = $charge->getPublicData();

        // Debug
        $this->log($response, 'Charge response');

        if ($charge->isSuccessful()) {
            if ($charge->isCaptured()) {
                // deliver a product

            } elseif ($charge->isUnderReview()) {
                $payment->setIsTransactionPending(true);
            }
        } else {
            $payment->setIsTransactionPending(true)
                ->setIsFraudDetected(true);
            $errors = json_decode($response, true);
            $this->log($errors, 'Charge error response');
            $strErrors = Mage::helper('paymentwall')->__("Brick error(s):");
            $strErrors .= "\n - Code #{$errors['error']['code']}: " . Mage::helper('paymentwall')->__($errors['error']['message']);
            Mage::throwException($strErrors);
        }
    }
}
