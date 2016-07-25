<?php

/**
 * @author  Paymentwall Inc <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento\Model\Method
 */
class Paymentwall_Paymentwall_Model_Method_Pwlocaluni extends Paymentwall_Paymentwall_Model_Method_Abstract {

    protected $_isInitializeNeeded = false;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canAuthorize = true;
    protected $_canVoid = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;

    /**
     * Constructor method.
     * Set some internal properties
     */
    public function __construct() {
        parent::__construct('pwlocaluni');
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('paymentwall/payment/pwlocaluni', array('_secure' => true));
    }

    /**
     * Generate Paymentwall Widget
     * @param $order
     * @return Paymentwall_Widget
     */
    public function getPaymentWidget(Mage_Sales_Model_Order $order) {
        $this->initPaymentwallConfig();

        $customerId = $_SERVER['REMOTE_ADDR'];

        if(Mage::getSingleton('customer/session')->isLoggedIn()){
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $customerId = $customer->getId();
        }

        $widget = new Paymentwall_Widget(
            $customerId,
            $this->getConfigData('paymentwall_widget_code'),
            array(
                new Paymentwall_Product(
                    $order->getIncrementId(),
                    $order->getGrandTotal(),
                    $order->getOrderCurrencyCode(),
                    'Order id #' . $order->getIncrementId(),
                    Paymentwall_Product::TYPE_FIXED
                )
            ),
            array_merge(
                array(
                    'email' => $order->getCustomerEmail(),
                    'success_url' => $this->getConfigData('paymentwall_url'),
                    'test_mode' => (int)$this->getConfigData('paymentwall_istest'),
                    'integration_module' => 'magento',
                    'ps' => ('1' == $this->getConfigData('paymentwall_istest')) ? 'test' : 'cc'
                ),
                $this->prepareUserProfile($order) // for User Profile API
            )
        );

        return $widget;
    }
}