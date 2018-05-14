<?php

if (!class_exists('Paymentwall_Config'))
    require_once Mage::getBaseDir('lib') . '/paymentwall-php/lib/paymentwall.php';

/**
 * @author Paymentwall Inc. <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 *
 * Class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Abstract
 */
class Paymentwall_Paymentwall_Block_Checkout_Form_Method_Abstract extends Mage_Payment_Block_Form
{
    private $modelName;
    private $paymentModel;

    /**
     * Get total amount of current order
     * @return mixed|null
     */
    public function getTotal()
    {
        return $this->getOrder() ? $this->getOrder()->getGrandTotal() : null;
    }

    /**
     * Get currency code of current order
     * @return string|null
     */
    public function getOrderCurrencyCode()
    {
        return $this->getOrder() ? $this->getOrder()->getOrderCurrencyCode() : null;
    }

    /**
     * Set payment model name
     * @param $name
     */
    public function setPaymentModelName($name)
    {
        $this->modelName = $name;
    }

    /**
     * Get Payment Model
     * @return false|Mage_Core_Model_Abstract
     */
    public function getPaymentModel()
    {
        if (!$this->paymentModel) {
            $this->paymentModel = Mage::getModel('paymentwall/method_' . $this->modelName);
        }

        return $this->paymentModel;
    }
}