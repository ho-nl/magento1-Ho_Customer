<?php

class Ho_Customer_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_AUTO_CREATE_CUSTOMERS    = 'ho_customer/automatic/create_customers';

    /**
     * @param int|Mage_Core_Model_Store $store
     * @return bool
     */
    public function autoCreateCustomers($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUTO_CREATE_CUSTOMERS, $store);
    }
}