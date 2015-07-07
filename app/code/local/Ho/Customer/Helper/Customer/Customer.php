<?php

class Ho_Customer_Helper_Customer_Customer extends Enterprise_Customer_Helper_Customer
{
    /**
     * Return available customer attribute form as select options
     *
     * Extended to add the 'Complete Profile' option
     *
     * @return array
     */
    public function getAttributeFormOptions()
    {
        return array(
            array(
                'label' => Mage::helper('enterprise_customer')->__('Customer Checkout Register'),
                'value' => 'checkout_register'
            ),
            array(
                'label' => Mage::helper('enterprise_customer')->__('Customer Registration'),
                'value' => 'customer_account_create'
            ),
            array(
                'label' => Mage::helper('enterprise_customer')->__('Customer Account Edit'),
                'value' => 'customer_account_edit'
            ),
            array(
                'label' => Mage::helper('enterprise_customer')->__('Admin Checkout'),
                'value' => 'adminhtml_checkout'
            ),
            array(
                'label' => Mage::helper('ho_customer')->__('Complete Profile'),
                'value' => 'ho_customer_complete_profile',
            ),
        );
    }
}