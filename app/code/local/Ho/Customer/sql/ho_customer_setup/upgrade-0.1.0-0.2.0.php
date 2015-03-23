<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('sales/order'),
    'order_completed',
    Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
    null
);

$installer->endSetup();
