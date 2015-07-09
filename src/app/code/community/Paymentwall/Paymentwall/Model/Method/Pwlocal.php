<?php

/**
 * @author  Paymentwall Inc <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento\Model\Method
 */
class Paymentwall_Paymentwall_Model_Method_Pwlocal extends Paymentwall_Paymentwall_Model_Method_Abstract
{
    protected $_isGateway = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;

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

    public function getPaymentWidget($order)
    {
        $this->initPaymentwallConfig();
        $widget = new Paymentwall_Widget(
            $order->getCustomerEmail(),
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
            array(
                'email' => $order->getCustomerEmail(),
                'success_url' => $this->getConfigData('paymentwall_url'),
                'test_mode' => (int) $this->getConfigData('paymentwall_istest'),
                'integration_module' => 'magento'
            )
        );

        return $widget;
    }
}