<?php
/**
 * Ho_Customer
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the H&O Commercial License
 * that is bundled with this package in the file LICENSE_HO.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.h-o.nl/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@h-o.com so we can send you a copy immediately.
 *
 * @category  Ho
 * @package   Ho_Customer
 * @author    Paul Hachmang – H&O <info@h-o.nl>
 * @copyright 2015 Copyright © H&O (http://www.h-o.nl/)
 * @license   H&O Commercial License (http://www.h-o.nl/license)
 */
 
class Ho_Customer_Block_Adminhtml_Customer_Edit
    extends Mage_Adminhtml_Block_Customer_Edit
{

    public function getHeaderText()
    {
        /** @var Mage_Customer_Model_Customer $currentCustomer */
        $currentCustomer = Mage::registry('current_customer');

        if ($currentCustomer->getId()) {
            if ($currentCustomer->getData('increment_id')) {
                return sprintf('%s (#%s)', $this->escapeHtml($currentCustomer->getName()), $currentCustomer->getData('increment_id'));
            }
            return $this->escapeHtml($currentCustomer->getName());
        }
        else {
            return Mage::helper('customer')->__('New Customer');
        }
    }
}
