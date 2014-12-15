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

    /**
     * When a customer tries to register which has already an automatically created account,
     * the following actions are performed:
     * - Set entered password at customer account;
     * - Send account confirmation email to customer;
     * - Set success messages ('Account confirmation required');
     * - Redirect to login page.
     * These actions make it look like as if the customer just registered a new account.
     *
     * @param Varien_Event_Observer $observer
     */
    public function registerUnconfirmedCustomer($observer)
    {
        if (!$this->_getHelper()->autoCreateCustomers()) return;

        $request = Mage::app()->getRequest();

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')
            ->getCollection()
            ->addAttributeToSelect('confirmation')
            ->addAttributeToFilter('email', $request->getParam('email'))
            ->getFirstItem();

        // No customer found; no changes to registration flow
        if (!$customer->getId()) return;

        // Customer is already confirmed; no changes to registration flow
        if (!$customer->getConfirmation()) return;

        $session = Mage::getSingleton('customer/session');

        // Save password (automatically created accounts have no password)
        $customer->setPassword($request->getParam('password'));
        $customer->save();

        // Send account confirmation email
        $customer->sendNewAccountEmail('confirmation');

        // Set confirmation required message, as if account was just created by the user
        $session->addSuccess(Mage::helper('customer')->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName()));
        $session->addSuccess(Mage::helper('customer')->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.',
            Mage::helper('customer')->getEmailConfirmationUrl($request->getParam('email'))));

        // Send customer to login page
        // This skips Mage_Customer_AccountController::createPostAction
        $url = Mage::getUrl('customer/account/login');

        Mage::app()->getResponse()
            ->setRedirect($url)
            ->sendResponse();
        exit;
    }
}