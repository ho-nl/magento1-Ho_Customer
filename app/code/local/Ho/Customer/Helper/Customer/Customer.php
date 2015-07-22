<?php

class Ho_Customer_Helper_Customer_Customer extends Enterprise_Customer_Helper_Customer
{
    protected $_options;
    /**
     * Return available customer attribute form as select options
     * Extended to add the Admin Medical Information customer attribute type
     *
     * @return array
     */
    public function getAttributeFormOptions()
    {
        if (! is_null($this->_options)) {
            return $this->_options;
        }


        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');


        /** @var Magento_Db_Adapter_Pdo_Mysql $writeConnection */
        $writeConnection = $resource->getConnection('core_read');

        $select = $writeConnection->select();
        $select->from($resource->getTableName('customer/form_attribute'), [
            'form_code' => new Zend_Db_Expr('DISTINCT form_code')
        ]);

        $formCodes = $writeConnection->fetchCol($select);
        $this->_options = [];
        foreach ($formCodes as $formCode) {
            $formLabel = ucwords(str_replace(['_', 'adminhtml'], [' ', 'admin'], $formCode));

            $this->_options[] = [
                'label' => Mage::helper('enterprise_customer')->__($formLabel),
                'value' => $formCode
            ];
        }

        return $this->_options;
    }
}
