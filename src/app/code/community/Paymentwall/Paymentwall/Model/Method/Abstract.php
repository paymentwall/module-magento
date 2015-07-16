<?php

if (!class_exists('Paymentwall_Config'))
    require_once Mage::getBaseDir('lib') . '/paymentwall-php/lib/paymentwall.php';

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall_Paymentwall
 */

/**
 * Class Paymentwall_Paymentwall_Model_Method_Abstract
 */
class Paymentwall_Paymentwall_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    protected $_code;
    protected $_logFile = 'paymentwall.log';

    /**
     * @param string $code
     */
    public function __construct($code = '')
    {
        if ($code) {
            $this->_code = 'paymentwall_' . $code;
        }

        $this->_formBlockType = 'paymentwall/checkout_form_method_' . $code;
        $this->_infoBlockType = 'paymentwall/checkout_info_method_' . $code;
        $this->setData('original_code', $code);
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

    /**
     * Init paymentwall configs
     */
    public function initPaymentwallConfig()
    {
        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => $this->getConfigData('paymentwall_public_key'),
            'private_key' => $this->getConfigData('paymentwall_private_key')
        ));
    }

    public function getMethodCode()
    {
        return $this->_code;
    }

    /**
     * @param $order
     * @return array
     */
    protected function prepareUserProfile($order)
    {
        $billing = $order->getBillingAddress();
        $data = array(
            'customer[city]' => $billing->getCity(),
            'customer[state]' => $billing->getRegion(),
            'customer[address]' => $billing->getStreetFull(),
            'customer[country]' => $billing->getCountry(),
            'customer[zip]' => $billing->getPostcode(),
        );

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            // Load the customer's data
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $data = array_merge($data, array(
                'customer[birthday]' => $customer->getDob() ? strtotime($customer->getDob()) : '',
                'customer[sex]' => $customer->getGender() ? $customer->getGender() : '',
                'customer[username]' => $customer->getEntityId(),
                'customer[firstname]' => $customer->getFirstname(),
                'customer[lastname]' => $customer->getLastname(),
                'email' => $customer->getEmail(),
                'history[registration_email]' => $customer->getEmail(),
                'history[registration_email_verified]' => $customer->getIsActive(),
                'history[registration_date]' => $customer->getCreatedAtTimestamp(),
            ));
        } else {
            $data = array_merge($data, array(
                'customer[username]' => $billing->getCustomerEmail(),
                'customer[firstname]' => $billing->getFirstname(),
                'customer[lastname]' => $billing->getLastname(),
                'email' => $billing->getEmail()
            ));
        }

        return $data;
    }

    /**
     * Log Function
     * @param $message
     */
    public function log($message, $section = '')
    {
        if ($this->getConfigData('debug_mode')) {
            if (!is_string($message)) {
                $message = var_export($message, true);
            }
            $message = "\n/********** " . $this->getCode() . ($section ? " " . $section : "") . " **********/\n" . $message;
            Mage::log($message, null, $this->_logFile);
        }
    }

}

    
    