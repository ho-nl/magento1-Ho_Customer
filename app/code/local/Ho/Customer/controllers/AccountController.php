<?php

class Ho_Customer_AccountController extends Mage_Core_Controller_Front_Action
{
    /**
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_title(Mage::helper('ho_customer')->__('Complete your profile'));

        return $this;
    }

    /**
     * Login a customer by an encrypted customer ID
     */
    public function loginAction()
    {
        $params = $this->getRequest()->getParams();

        $helper = Mage::helper('core');

        $customerId = $helper->getEncryptor()->decrypt($params['encryption']);
        $customer = Mage::getModel('customer/customer')->load($customerId);

        try {
            if ($customer->getWebsiteId()) {
                $session = Mage::getSingleton('customer/session');
                $session->loginById($customerId);

                if (isset($params['forward_url'])) {
                    $forwardUrl = base64_decode($params['forward_url']);

                    $this->_redirectUrl($forwardUrl);
                }
                else {
                    $this->_redirect('/');
                }
            }
        }
        catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('customer/session')->addError(
                Mage::helper('ho_customer')->__('An error occurred while trying to login')
            );

            $this->_redirect('/');
        }
    }

    public function completeProfileAction()
    {
        $this->_initAction()
            ->renderLayout();
    }
}