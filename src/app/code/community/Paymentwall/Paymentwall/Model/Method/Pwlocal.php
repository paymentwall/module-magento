<?php

/**
 * @author  Paymentwall Inc <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento\Model\Method
 */
class Paymentwall_Paymentwall_Model_Method_Pwlocal extends Paymentwall_Paymentwall_Model_Method_Abstract {

    protected $_isInitializeNeeded = false;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canAuthorize = true;
    protected $_canVoid = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;
    const DEFAULT_USER_ID = 'user101';
    const SIGN_VERSION_TWO = 2;

    /**
     * Constructor method.
     * Set some internal properties
     */
    public function __construct() {
        parent::__construct('pwlocal');
    }

    public function assignData($data)
    {
        Mage::log($data->getPayInstallment());
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('paymentwall/payment/pwlocal', array('_secure' => true));
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

        $pwProducts = array(
            new Paymentwall_Product(
                $order->getIncrementId(),
                $order->getGrandTotal(),
                $order->getOrderCurrencyCode(),
                'Order id #' . $order->getIncrementId(),
                Paymentwall_Product::TYPE_FIXED
            )
        );
        $additionalParams = array_merge(
            array(
                'email' => $order->getCustomerEmail(),
                'success_url' => $this->getConfigData('paymentwall_url') ?
                    $this->getConfigData('paymentwall_url') : Mage::getBaseUrl() . 'checkout/onepage/success',
                'test_mode' => (int)$this->getConfigData('paymentwall_istest'),
                'integration_module' => 'magento',
                'pingback_url' => Mage::getBaseUrl() . 'paymentwall/payment/ipn'
            ),
            $this->prepareUserProfile($order) // for User Profile API
        );

        $paymentSystem = Mage::getSingleton('core/session')->getPaymentMethod();
        if ($paymentSystem) {
            $additionalParams['ps'] = $paymentSystem;
            Mage::getSingleton('core/session')->setPaymentMethod('');
        }

        $widget = new Paymentwall_Widget(
            $customerId,
            $this->getConfigData('paymentwall_widget_code'),
            $pwProducts,
            $additionalParams
        );

        return $widget;
    }

    public function getUserCountryFromApi()
    {
        $response = [
            'success' => 0
        ];

        try {
            $params = [
                'key' => $this->getConfigData('paymentwall_public_key'),
                'user_ip' => Mage::helper('core/http')->getRemoteAddr(),
                'uid' => self::DEFAULT_USER_ID
            ];

            $url = Paymentwall_Config::API_BASE_URL . '/rest/country?' . http_build_query($params);
            $curl = curl_init($url);
            curl_setopt($curl,CURLOPT_RETURNTRANSFER, TRUE);
            $result = curl_exec($curl);
            $result = json_decode($result, true);

            $response['success'] = 1;
            $response['data'] = $result['code'];
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return json_encode($response);
    }

    public function getLocalMethods($countryCode)
    {
        $response = [
            'success' => 0
        ];

        try {
            $params = array(
                'key' => $this->getConfigData('paymentwall_public_key'),
                'country_code' => $countryCode,
                'sign_version' => self::SIGN_VERSION_TWO
            );

            Paymentwall_Config::getInstance()->set(array('private_key' => $this->getConfigData('paymentwall_private_key')));
            $params['sign'] = (new Paymentwall_Signature_Widget())->calculate(
                $params,
                $params['sign_version']
            );

            $url = Paymentwall_Config::API_BASE_URL . '/payment-systems/?' . http_build_query($params);
            $curl = curl_init($url);
            curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
            $result = curl_exec($curl);
            $result = json_decode($result, true);

            $response['success'] = 1;
            $response['data'] = $result;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return json_encode($response);
    }

    public function storePaymentMethodToSession($paymentMethodName)
    {
        Mage::getSingleton('core/session')->setPaymentMethod($paymentMethodName);
        echo Mage::getSingleton('core/session')->getPaymentMethod();
    }
}