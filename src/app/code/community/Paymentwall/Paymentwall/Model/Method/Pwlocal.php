<?php
/**
 * @author  Paymentwall Inc <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento\Model\Method
 */

class Paymentwall_Paymentwall_Model_Method_Pwlocal extends Paymentwall_Paymentwall_Model_Method_Abstract
{
    protected $_isGateway               = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;

    /**
     * Constructor method.
     * Set some internal properties
     */
    public function __construct()
    {
        parent::__construct('pwlocal');
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('paymentwall/payment/pwlocal', array('_secure' => true));
    }

    public function initPaymentMethod()
    {
        if (!Mage::getStoreConfig('payment/paymentwall_pwlocal/active')) {
            throw new Exception("Method is not activated!");
        }

        $secret = Mage::getStoreConfig('payment/paymentwall_pwlocal/paymentwall_secret');
        $appKey = Mage::getStoreConfig('payment/paymentwall_pwlocal/paymentwall_shop_id');

        if (!($secret && $appKey)) {
            throw new Exception("Please check configuration and add missing information");
        }

        Paymentwall_Base::setApiType(Paymentwall_Base::API_GOODS);
        Paymentwall_Base::setAppKey($appKey); // available in your Paymentwall merchant area
        Paymentwall_Base::setSecretKey($secret); // available in your Paymentwall merchant area
    }

    public function getPaymentWidget($order)
    {
        $widget = new Paymentwall_Widget(
            $order->getCustomerEmail(),   // id of the end-user who's making the payment
            Mage::getStoreConfig('payment/paymentwall_pwlocal/paymentwall_widget_code'),        // widget code, e.g. p1; can be picked inside of your merchant account
            array(         // product details for Flexible Widget Call. To let users select the product on Paymentwall's end, leave this array empty
                new Paymentwall_Product(
                    $order->getIncrementId(),
                    $order->getGrandTotal(),
                    $order->getOrderCurrencyCode(),
                    'Order id #' . $order->getIncrementId(),
                    Paymentwall_Product::TYPE_FIXED
                )
            ),
            array(
                'email' => $order->getCustomerEmail(),
                'success_url' => Mage::getStoreConfig('payment/paymentwall_pwlocal/paymentwall_url'),
                'test_mode' => (int) Mage::getStoreConfig('payment/paymentwall_pwlocal/paymentwall_istest'),
				'integration_module' => 'magento'
				)
        );

        return $widget;
    }
}