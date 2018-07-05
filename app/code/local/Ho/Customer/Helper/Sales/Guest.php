<?php
/**
 * Copyright (c) Reach Digital (http://reachdigital.nl/)
 * See LICENSE.txt for license details.
 */

class Ho_Customer_Helper_Sales_Guest extends Mage_Sales_Helper_Guest
{
    /**
     * Try to load valid order by $_POST or $_COOKIE
     *
     * Extended to make it possible to view order as guest when shadow customer is created for order
     *
     * @return bool|null
     */
    public function loadValidOrder()
    {
        if (!Mage::helper('ho_customer')->autoCreateCustomers()) {
            return parent::loadValidOrder();
        }

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::app()->getResponse()->setRedirect(Mage::getUrl('sales/order/history'));
            return false;
        }

        $post = Mage::app()->getRequest()->getPost();
        $errors = false;

        /** @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        /** @var Mage_Core_Model_Cookie $cookieModel */
        $cookieModel = Mage::getSingleton('core/cookie');
        $errorMessage = 'Entered data is incorrect. Please try again.';

        // Wether or not we should require login for non-guest/shadow customers
        $requireLogin = Mage::helper('ho_customer')->requireLogin();

        if (empty($post) && !$cookieModel->get($this->_cookieName)) {
            Mage::app()->getResponse()->setRedirect(Mage::getUrl('sales/guest/form'));
            return false;
        } elseif (!empty($post) && isset($post['oar_order_id']) && isset($post['oar_type']))  {
            $type           = $post['oar_type'];
            $incrementId    = $post['oar_order_id'];
            $lastName       = $post['oar_billing_lastname'];
            $email          = $post['oar_email'];
            $zip            = $post['oar_zip'];

            if (empty($incrementId) || empty($lastName) || empty($type) || (!in_array($type, array('email', 'zip')))
                || ($type == 'email' && empty($email)) || ($type == 'zip' && empty($zip))) {
                $errors = true;
            }

            if (!$errors) {
                $order->loadByIncrementId($incrementId);
            }

            if ($order->getId()) {
                $billingAddress = $order->getBillingAddress();
                if ((strtolower($lastName) != strtolower($billingAddress->getLastname()))
                    || ($type == 'email'
                        && strtolower($email) != strtolower($billingAddress->getEmail()))
                    || ($type == 'zip'
                        && (strtolower($zip) != strtolower($billingAddress->getPostcode())))
                ) {
                    $errors = true;
                }
            } else {
                $errors = true;
            }

            // Check if order customer is a shadow customer created for guest
            $guestCustomer = false;
            if ($order->getCustomerId()) {
                $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                $guestCustomer = !$customer->getData('password_hash');
            }

            // If enabled, redirect non-guests to login first, else error as usual
            if (!$guestCustomer && $requireLogin) {
                Mage::getSingleton('core/session')->addNotice($this->__('Please log in to view your order details.'));

                Mage::getSingleton('customer/session')->setAfterAuthUrl(
                    Mage::getUrl('sales/order/view',array('order_id' => $order->getId()))
                );
                Mage::app()->getResponse()->setRedirect(Mage::getUrl('customer/account/login'));
                return false;
            } else if ($errors === false && (!is_null($order->getCustomerId()) && (!$guestCustomer && $requireLogin))) {
                $errorMessage = 'Please log in to view your order details.';
                $errors = true;
            }

            if (!$errors) {
                $toCookie = base64_encode($order->getProtectCode() . ':' . $incrementId);
                $cookieModel->set($this->_cookieName, $toCookie, $this->_lifeTime, '/');
            }
        } elseif ($cookieModel->get($this->_cookieName)) {
            $cookie = $cookieModel->get($this->_cookieName);
            $cookieOrder = $this->_loadOrderByCookie( $cookie );
            if (!is_null($cookieOrder)) {
                // Check if order customer is guest (shadow customer), if so, bypass login message/redirect (unless login is required)
                $guestCustomer = false;
                if ($cookieOrder->getCustomerId()) {
                    $customer = Mage::getModel('customer/customer')->load($cookieOrder->getCustomerId());
                    $guestCustomer = !$customer->getData('password_hash');
                }
                if (is_null($cookieOrder->getCustomerId()) || ($guestCustomer && !$requireLogin)) {
                    $cookieModel->renew($this->_cookieName, $this->_lifeTime, '/');
                    $order = $cookieOrder;
                } else {
                    $errorMessage = 'Please log in to view your order details.';
                    $errors = true;
                }
            } else {
                $errors = true;
            }
        }

        if (!$errors && $order->getId()) {
            Mage::register('current_order', $order);
            return true;
        }

        Mage::getSingleton('core/session')->addError($this->__($errorMessage));
        Mage::app()->getResponse()->setRedirect(Mage::getUrl('sales/guest/form'));
        return false;
    }
}
