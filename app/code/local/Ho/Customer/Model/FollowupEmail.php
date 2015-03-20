<?php

class Ho_Customer_Model_FollowupEmail extends Mage_Core_Model_Abstract
{
    protected $_helper;

    /**
     * Send emails to customers that have a completed order, after the number of configured days the order is completed
     *
     * @return string
     */
    public function sendCompleteProfileEmails()
    {
        $startDate = $this->getConfig()->getEmailStartDate();
        $timeSpan = date('Y-m-d H:i:s', strtotime('-' . $this->getConfig()->getEmailDaysAfter() . ' days'));

        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('created_at', array('gt' => $startDate))
            ->addFieldToFilter('order_completed', array('lt' => $timeSpan))
            ->addFieldToFilter('order_completed', array('neq' => '0000-00-00 00:00:00'));

        $attributeId = Mage::getModel('customer/customer')->getResource()->getAttribute('complete_profile_sent')->getId();
        $orderCollection->getSelect()->joinLeft(
            array('customers' => Mage::getSingleton('core/resource')->getTableName('customer_entity_int')),
            'customers.entity_id = `main_table`.customer_id AND customers.attribute_id = ' . $attributeId,
            array('complete_profile_sent' => 'customers.value')
        );
        $orderCollection->addFieldToFilter('customers.value', false);

        $i = $e = 0;
        foreach ($orderCollection as $order) {
            try {
                $this->_sendEmail($order);
                $i++;
            }
            catch (Exception $e) {
                Mage::logException($e);
                $e++;
            }
        }

        if ($e > 0) {
            return Mage::helper('ho_customer')->__('%s emails sent (%s errors, see exception log)', $i, $e);
        }
        else {
            return Mage::helper('ho_customer')->__('%s emails sent', $i);
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _sendEmail(Mage_Sales_Model_Order $order)
    {
        // Send email
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);

        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');

        $loginUrl = Mage::getUrl('ho_customer/account/login', array(
            'encryption'    => $helper->getEncryptor()->encrypt($order->getCustomerId()),
            'forward_url'   => base64_encode(Mage::getUrl('ho_customer/account/completeProfile')),
        ));

        /** @var Mage_Core_Model_Email_Template $emailTemplate */
        $emailTemplate = Mage::getModel('core/email_template');
        $emailTemplate
            ->setDesignConfig(array('area' => 'frontend', 'store' => $order->getStoreId()))
            ->sendTransactional(
                $this->getConfig()->getEmailTemplate($order->getStoreId()),
                $this->getConfig()->getEmailSender($order->getStoreId()),
                $order->getCustomerEmail(),
                $order->getCustomerName(),
                array(
                    'order'     => $order,
                    'login_url' => $loginUrl,
                )
            );

        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        $customer->setData('complete_profile_sent', true)->getResource()->saveAttribute($customer, 'complete_profile_sent');

        $translate->setTranslateInline(true);
    }

    /**
     * @return Ho_Customer_Helper_Config
     */
    public function getConfig()
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::helper('ho_customer/config');
        }

        return $this->_helper;
    }
}