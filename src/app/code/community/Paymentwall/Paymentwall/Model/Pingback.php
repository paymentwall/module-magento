<?php

if (!class_exists('Paymentwall_Config'))
    require_once Mage::getBaseDir('lib') . '/paymentwall-php/lib/paymentwall.php';

/**
 * @author Paymentwall Inc <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 */
class Paymentwall_Paymentwall_Model_Pingback extends Mage_Core_Model_Abstract
{
    const DEFAULT_PINGBACK_RESPONSE = 'OK';

    /**
     * Handle pingback
     * @return string
     */
    public function handlePingback()
    {
        // Load paymentwall configs
        Mage::getModel('paymentwall/method_pwlocal')->initPaymentwallConfig();

        $pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);

        if ($pingback->validate()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($pingback->getProductId());
            if ($order->getId()) {
                try {
                    $paymentModel = $order->getPayment()->getMethodInstance();
                    $paymentModel->setCurrentOrder($order)->processPendingPayment($pingback);
                    $result = self::DEFAULT_PINGBACK_RESPONSE;
                } catch (Exception $e) {
                    Mage::log($e->getMessage());
                    $result = 'Internal server error';
                    $result .= ' ' . $e->getMessage();
                }
            } else {
                $result = 'Invalid order';
            }
        } else {
            $result = $pingback->getErrorSummary();
        }

        return $result;
    }


}