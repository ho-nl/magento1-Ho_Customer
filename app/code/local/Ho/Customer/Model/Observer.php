<?php

class Ho_Customer_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Automatically creates a customer when a guest orders is placed,
     * and links guest orders to existing customer accounts
     *
     * @event sales_order_save_before
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function autoCreateCustomer(Varien_Event_Observer $observer)
    {
        if (!$this->_getHelper()->autoCreateCustomers()) return;

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order) return $this;

        // Skip customer creating when customer ID already exists at order
        if ($order->getCustomerId()) return $this;

        // Create customer
        $customer = $this->_createCustomerFromOrder($order);

        if (!$customer instanceof Mage_Customer_Model_Customer) return $this;

        // Set customer at order
        $order->setCustomerId($customer->getId());
        $order->setCustomer($customer);
        $order->setCustomerIsGuest(false);
        $order->setCustomerGroupId($customer->getGroupId());

        return $this;
    }

    /**
     * @return Ho_Customer_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('ho_customer');
    }

    /**
     * Creates new customer from order, adds order addresses as customer addresses
     *
     * @param Mage_Sales_Model_Order|bool $order
     * @return Mage_Customer_Model_Customer
     */
    protected function _createCustomerFromOrder($order)
    {
        if (!$order instanceof Mage_Sales_Model_Order) {
            return false;
        }

        // Check if customer with email address exists
        $existingCustomer = Mage::getModel('customer/customer')
            ->getCollection()
            ->addFieldToFilter('email', $order->getCustomerEmail())
            ->getFirstItem();

        if ($existingCustomer->getId()) {
            return $existingCustomer;
        }

        // Create customer
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')
            ->setEmail($order->getCustomerEmail())
            ->setStoreId($order->getStoreId())
            ->setFirstname($order->getCustomerFirstname())
            ->setLastname($order->getCustomerLastname());

        $customer->save();

        // Create customer addresses
        foreach ($order->getAddressesCollection() as $orderAddress) {
            /** @var Mage_Sales_Model_Order_Address $orderAddress */

            /** @var Mage_Customer_Model_Address $address */
            $address = Mage::getModel('customer/address')
                ->setParentId($customer->getEntityId())
                ->setCustomerId($customer->getEntityId())
                ->setIsActive(true)
                ->setFirstname($orderAddress->getFirstname())
                ->setLastname($orderAddress->getLastname())
                ->setStreet($orderAddress->getStreet())
                ->setCity($orderAddress->getCity())
                ->setPostcode($orderAddress->getPostcode())
                ->setCountryId($orderAddress->getCountryId())
                ->setTelephone($orderAddress->getTelephone())
                ->setCompany($orderAddress->getCompany())
                ->setRegion($orderAddress->getRegion())
                ->setRegionId($orderAddress->getRegionId());

            $address->save();

            // Save default billing and shipping
            if ($orderAddress->getAddressType() == 'billing') {
                $customer->setDefaultBilling($address->getEntityId());
            }
            elseif ($orderAddress->getAddressType() == 'shipping') {
                $customer->setDefaultShipping($address->getEntityId());
            }
        }

        // Force confirmation
        $customer->setConfirmation($customer->getRandomConfirmationKey());

        $customer->save();

        return $customer;
    }
}