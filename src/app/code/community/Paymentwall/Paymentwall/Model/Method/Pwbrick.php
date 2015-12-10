<?php

/**
 * @author  Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall_Paymentwall
 *
 * Class Paymentwall_Paymentwall_Model_Method_Pwbrick
 */
class Paymentwall_Paymentwall_Model_Method_Pwbrick extends Paymentwall_Paymentwall_Model_Method_Abstract implements Mage_Payment_Model_Recurring_Profile_MethodInterface {

    protected $_isInitializeNeeded = false;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canAuthorize = false;
    protected $_canVoid = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;

    /**
     * Constructor method.
     * Set some internal properties
     */
    public function __construct() {
        parent::__construct('pwbrick');
    }

    /**
     * Prepare data for payment
     * @param  $payment
     * @return array
     */
    public function prepareCardInfo($payment) {
        $order = $payment->getOrder();
        $info = $this->getInfoInstance();
        $this->setCurrentOrder($order);
        return array(
            'email' => $order->getBillingAddress()->getEmail(),
            'amount' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode(),
            'token' => $info->getAdditionalInformation('brick_token'),
            'fingerprint' => $info->getAdditionalInformation('brick_fingerprint'),
            'description' => 'Order #' . $order->getIncrementId(),
            'plan' => $order->getIncrementId(),
        );
    }

    /**
     * @param $data
     * @return mixed
     */
    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('brick_token', $data->getBrickToken())
            ->setAdditionalInformation('brick_fingerprint', $data->getBrickFingerprint());

        return $this;
    }

    /**
     * @param $payment
     * @param $amount
     * @return Mage_Payment_Model_Abstract|void
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount) {

        $this->initPaymentwallConfig();

        $customerId = $_SERVER['REMOTE_ADDR'];

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $customerId = $customer->getId();
        }

        $payment->setAmount($amount);

        $charge = new Paymentwall_Charge();
        $charge->create(array_merge(
            $this->prepareUserProfile($payment->getOrder()), // for User Profile API
            $this->prepareCardInfo($payment),
            array(
                'uid' => $customerId // for Pingback Request
            )
        ));
        $response = $charge->getPublicData();

        // Debug
        $this->log($response, 'Charge response');

        if ($charge->isSuccessful()) {
            if ($charge->isCaptured()) {

                $payment->setTransactionId($charge->getId());
                $payment->setIsTransactionClosed(0);

                // store token data
                $payment->setTransactionAdditionalInfo('saved_token', Mage::helper('core')->encrypt($charge->getCard()->getToken()));
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

        return $this;
    }

    /**
     * Validate data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @throws Mage_Core_Exception
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile) {

    }

    /**
     * Submit to the gateway
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param Mage_Payment_Model_Info $paymentInfo
     * @throws Mage_Exception
     */
    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile, Mage_Payment_Model_Info $paymentInfo) {

        $this->initPaymentwallConfig();
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        $subscriptionData = $this->prepareSubscriptionData($profile, $quote);
        $paymentwallSubscription = new Paymentwall_Subscription();
        $paymentwallSubscription->create($subscriptionData);

        $response = json_decode($paymentwallSubscription->GetRawResponseData());
        $this->log($response, 'Subscription Response Data');

        if ($paymentwallSubscription->isSuccessful() && $response->object == 'subscription') {

            $profile->setReferenceId($response->id);
            if ($response->active) {
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);
            } else {
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_PENDING);
            }
            $profile->save();
        } else {
            $error = json_decode($paymentwallSubscription->getPublicData());
            $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_UNKNOWN);
            $profile->save();
        }
    }

    /**
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param $quote
     * @return array
     */
    protected function prepareSubscriptionData(Mage_Payment_Model_Recurring_Profile $profile, $quote) {

        $post = Mage::app()->getRequest()->getPost('payment');

        return array_merge(
            array(
                'token' => $post['brick_token'], // Onetime token
                'amount' => $profile->getBillingAmount(),
                'currency' => $profile->getCurrencyCode(),
                'email' => $quote->getBillingAddress()->getEmail(),
                'fingerprint' => $post['brick_fingerprint'],
                'description' => $profile->getScheduleDescription(),
                'plan' => $profile->getInternalReferenceId(),
                'period' => $profile->getPeriodUnit(),
                'period_duration' => $profile->getPeriodFrequency(),
            ),
            $this->prepareTrialData($profile)
        );
    }

    /**
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @return array
     */
    protected function prepareTrialData(Mage_Payment_Model_Recurring_Profile $profile) {

        if (!$profile->getTrialPeriodFrequency()) {
            return array();
        }

        return array(
            'trial[amount]' => $profile->getTrialBillingAmount() ? $profile->getTrialBillingAmount() : 0,
            'trial[currency]' => $profile->getCurrencyCode(),
            'trial[period]' => $profile->getTrialPeriodUnit(),
            'trial[period_duration]' => $profile->getTrialPeriodFrequency(),
        );
    }

    /**
     * Fetch details
     *
     * @param string $referenceId
     * @param Varien_Object $result
     * @return Varien_Object
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result) {

        $this->initPaymentwallConfig();

        $paymentwallSubscription = new Paymentwall_Subscription($referenceId);
        $paymentwallSubscription->get();

        $subscriptionData = json_decode($paymentwallSubscription->GetRawResponseData());
        $result->setData($subscriptionData);

    }

    /**
     * Check whether can get recurring profile details
     *
     * @return bool
     */
    public function canGetRecurringProfileDetails() {
        return true;
    }

    /**
     * Update data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile) {
        // TODO: Implement updateRecurringProfile() method.
    }

    /**
     * Manage status
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    /**
     * Manage status
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile) {
        $this->initPaymentwallConfig();

        $paymentwallSubscription = new Paymentwall_Subscription($profile->getReferenceId());

        if ($profile->getNewState() == Mage_Sales_Model_Recurring_Profile::STATE_CANCELED) {
            $paymentwallSubscription->cancel();
        }
    }
}
