<?php

if (!class_exists('Paymentwall_Config'))
    require_once Mage::getBaseDir('lib') . '/paymentwall-php/lib/paymentwall.php';

/**
 * @author Paymentwall Inc <devsupport@paymentwall.com>
 * @package Paymentwall\ThirdpartyIntegration\Magento
 */
class Paymentwall_Paymentwall_Model_Pingback extends Mage_Core_Model_Abstract {
    const DEFAULT_PINGBACK_RESPONSE = 'OK';

    /**
     * Handle pingback
     * @return string
     */
    public function handlePingback() {
        // Load paymentwall configs
        Mage::getModel('paymentwall/method_pwlocal')->initPaymentwallConfig();

        $pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);

        if ($pingback->validate()) {

            if ($this->isRecurring($pingback)) {
                return $this->processPingbackRecurringProfile($pingback);
            } else {
                return $this->processPingbackOrder($pingback);
            }
        } else {
            return $pingback->getErrorSummary();
        }

        return '';
    }

    /**
     * @param Paymentwall_Pingback $pingback
     * @return string
     */
    protected function processPingbackRecurringProfile(Paymentwall_Pingback $pingback) {
        $recurringProfile = Mage::getModel('sales/recurring_profile')->loadByInternalReferenceId($pingback->getProductId());

        if ($recurringProfile->getId()) {
            try {
                if ($pingback->isDeliverable()) {
                    $recurringProfile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE)->save();
                } elseif ($pingback->isCancelable()) {
                    $recurringProfile->setState(Mage_Sales_Model_Recurring_Profile::STATE_CANCELED)->save();
                }

                return self::DEFAULT_PINGBACK_RESPONSE;
            } catch (Exception $e) {
                Mage::log($e->getMessage());
                $result = 'Internal server error';
                $result .= ' ' . $e->getMessage();
                return $result;
            }
        } else {
            return 'The Recurring Profile is invalid';
        }

    }

    /**
     * @param Paymentwall_Pingback $pingback
     * @return string
     */
    protected function processPingbackOrder(Paymentwall_Pingback $pingback) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($pingback->getProductId());
        if ($order->getId()) {
            try {
                if ($pingback->isDeliverable()) {
                    $paymentModel = $order->getPayment()->getMethodInstance();
                    $paymentModel->setCurrentOrder($order)->callDeliveryApi($pingback->getReferenceId());
                    $paymentModel->makeInvoice();

                } elseif ($pingback->isCancelable()) {
                    $order->registerCancellation(Mage::helper('sales')->__('Order marked as cancelled by Paymentwall.'))
                        ->save();
                } elseif ($pingback->isUnderReview()) {
                    $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true)
                        ->save();
                }
                return self::DEFAULT_PINGBACK_RESPONSE;
            } catch (Exception $e) {
                Mage::log($e->getMessage());
                $result = 'Internal server error';
                $result .= ' ' . $e->getMessage();
                return $result;
            }
        } else {
            return 'The Order is invalid';
        }
    }

    /**
     * @param Paymentwall_Pingback $pingback
     * @return bool
     */
    protected function isRecurring(Paymentwall_Pingback $pingback) {
        return $pingback->getProductPeriodLength() > 0;
    }

}