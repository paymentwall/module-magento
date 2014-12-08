<?php
/**
 * @author Paymentwall Inc <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 */
if (!class_exists('Paymentwall_Base')) {
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'paymentwall-sdk' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'paymentwall.php';
}

class Paymentwall_Paymentwall_Model_Pingback extends Mage_Core_Model_Abstract
{
    /**
     * Handle pingback
     * @return string
     */
    public function handlePingback()
    {
        $result = '';
        $pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);
        
        if ($pingback->validate()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($pingback->getProductId());
            if ($order->getId()) {
                try {
                    $paymentModel = $order->getPayment()->getMethodInstance();
                    $paymentModel->setCurrentOrder($order)->processPendingPayment($pingback);
                    $result = 'OK';
                } catch (Exception $e) {
                    Mage::log($e->getMessage());
                    $result = 'Internal server error';
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