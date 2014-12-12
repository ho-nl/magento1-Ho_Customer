<?php

class Ho_Customer_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_AUTO_CREATE_CUSTOMERS    = 'ho_customer/automatic/create_customers';
    const XML_PATH_AUTO_LINK_ORDERS         = 'ho_customer/automatic/link_orders';

    /**
     * @param int|Mage_Core_Model_Store $store
     * @return bool
     */
    public function autoCreateCustomers($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUTO_CREATE_CUSTOMERS, $store);
    }

    /**
     * @param int|Mage_Core_Model_Store $store
     * @return bool
     */
    public function autoLinkOrders($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUTO_LINK_ORDERS, $store);
    }
}