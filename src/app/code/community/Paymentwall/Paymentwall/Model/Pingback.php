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
     * Handle ping back
     * @return [type] [description]
     */
    public function handlePingback()
    {
        $result = array('success' => false);
        $pingback = new Paymentwall_Pingback($_POST, $_SERVER['REMOTE_ADDR']);
        if ($pingback->validate()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($pingback->getProductId());
            if ($order->getId()) {
                try {
                    $paymentModel = $order->getPayment()->getMethodInstance();
                    $paymentModel->setCurrentOrder($order)->processPendingPayment($pingback);
                    $result['success'] = true;
                } catch (Exception $e) {
                    Mage::log($e->getMessage());
                    $result['message'] = "Internal server error";
                }
            } else {
                $result['message'] = "Invalid order";
            }
        } else {
            $result['message'] = "Invalid pingback";
        }

        return $result;
    }
}