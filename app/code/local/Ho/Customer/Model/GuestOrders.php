<?php

class Ho_Customer_Model_GuestOrders extends Mage_Core_Model_Abstract
{
    /**
     * Create customers from orders, link guest orders to customers
     */
    public function registerCustomers()
    {
        $i = 0;

        $guestOrders = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('customer_id', array('null' => true))
            ->addFieldToFilter('customer_email', array('notnull' => true))
            ->addFieldToFilter('customer_email', array('like' => '%@%'));

        $guestOrders->getSelect()
            ->columns(array('increment_id', 'customer_email'))
            ->order('created_at')
            ->limit(1000);

        foreach ($guestOrders as $guestOrder) {
            $this->_createCustomer($guestOrder);
            $i++;
        }

        return sprintf('%s orders updated', $i);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return int
     */
    protected function _createCustomer(Mage_Sales_Model_Order $order)
    {
        /** @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer')
            ->setWebsiteId($order->getStore()->getWebsiteId())
            ->loadByEmail($order->getCustomerEmail());

        $customerGroupId = 1; // @todo load general customer group ID?

        if (!$customer->getId()) {
            $customer->addData(array(
                'prefix'            => $order->getCustomerPrefix(),
                'firstname'         => $order->getCustomerFirstname(),
                'middlename'        => $order->getCustomerMiddlename(),
                'lastname'          => $order->getCustomerLastname(),
                'suffix'            => $order->getCustomerSuffix(),
                'email'             => $order->getCustomerEmail(),
                'group_id'          => $customerGroupId,
                'taxvat'            => $order->getCustomerTaxvat(),
                'website_id'        => $order->getStore()->getWebsiteId(),
                'default_billing'   => '_item1',
                'default_shipping'  => '_item2',
            ));

            // Billing Address
            /** @var $billingAddress Mage_Sales_Model_Order_Address */
            $billingAddress = $order->getBillingAddress();
            /** @var $customerBillingAddress Mage_Customer_Model_Address */
            $customerBillingAddress = Mage::getModel('customer/address');

            $billingAddressArray = $billingAddress->toArray();
            unset($billingAddressArray['entity_id']);
            unset($billingAddressArray['parent_id']);
            unset($billingAddressArray['customer_id']);
            unset($billingAddressArray['customer_address_id']);
            unset($billingAddressArray['quote_address_id']);

            $customerBillingAddress->addData($billingAddressArray);
            $customerBillingAddress->setPostIndex('_item1');
            $customer->addAddress($customerBillingAddress);

            // Shipping Address
            /** @var $shippingAddress Mage_Sales_Model_Order_Address */
            $shippingAddress = $order->getShippingAddress();
            /** @var $customerShippingAddress Mage_Customer_Model_Address */
            $customerShippingAddress = Mage::getModel('customer/address');

            $shippingAddressArray = $shippingAddress->toArray();
            unset($shippingAddressArray['entity_id']);
            unset($shippingAddressArray['parent_id']);
            unset($shippingAddressArray['customer_id']);
            unset($shippingAddressArray['customer_address_id']);
            unset($shippingAddressArray['quote_address_id']);

            $customerShippingAddress->addData($shippingAddressArray);
            $customerShippingAddress->setPostIndex('_item2');
            $customer->addAddress($customerShippingAddress);

            // Save the customer
            $customer->setPassword($customer->generatePassword());
            $customer->save();
        }

        // Link customer to order
        $order->setCustomerId($customer->getId());
        $order->setCustomerIsGuest(0);
        $order->setCustomerGroupId($customerGroupId);
        $order->save();

        return $customer->getId();
    }
}