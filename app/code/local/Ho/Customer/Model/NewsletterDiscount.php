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
 * @category    Ho
 * @package     Ho_Customer
 * @copyright   Copyright © 2016 H&O (http://www.h-o.nl/)
 * @license     H&O Commercial License (http://www.h-o.nl/license)
 * @author      Maikel Koek – H&O <info@h-o.nl>
 */

class Ho_Customer_Model_NewsletterDiscount
{
    /**
     * Return discount amount
     */
    public function getDiscountAmount()
    {
        $rule = Mage::getModel('salesrule/rule')->load($this->getConfig()->getNewsletterDiscountPriceRule());

        if ($rule->getId()) {
            return $rule->getDiscountAmount();
        }

        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    public function sendDiscountEmail(Mage_Sales_Model_Order $order)
    {
        $email = $this->getConfig()->getNewsletterDiscountEmailTemplate();
        $sender = $this->getConfig()->getNewsletterDiscountEmailSender();

        $coupon = $this->generateCoupon();

        // Send email
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);

        $emailTemplate = Mage::getModel('core/email_template');

        $emailTemplate->setDesignConfig(['area' => 'frontend', 'store' => $order->getStoreId()]);
        $emailTemplate->sendTransactional(
            $email,
            $sender,
            $order->getCustomerEmail(),
            $order->getCustomerName(),
            ['coupon_code' => $coupon->getCode()]
        );

        $translate->setTranslateInline(true);
    }

    /**
     * @return Mage_SalesRule_Model_Coupon|void
     */
    public function generateCoupon()
    {
        $generator = Mage::getModel('salesrule/coupon_massgenerator');

        $data = [
            'qty'               => 1,
            'length'            => 8,
            'format'            => Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHANUMERIC,
            'rule_id'           => $this->getConfig()->getNewsletterDiscountPriceRule(),
        ];

        if ($this->getConfig()->getNewsletterDiscountLimitUsage()) {
            $data['uses_per_coupon'] = 1;
        }

        $generator->setData($data);
        $generator->generatePool();

        $salesRule = Mage::getModel('salesrule/rule')->load($data['rule_id']);

        /** @var Mage_SalesRule_Model_Coupon $coupon */
        $coupon = Mage::getResourceModel('salesrule/coupon_collection')
            ->addRuleToFilter($salesRule)
            ->addGeneratedCouponsFilter()
            ->getLastItem();

        return $coupon;
    }

    /**
     * @return Ho_Customer_Helper_Config
     */
    public function getConfig()
    {
        return Mage::helper('ho_customer/config');
    }
}
