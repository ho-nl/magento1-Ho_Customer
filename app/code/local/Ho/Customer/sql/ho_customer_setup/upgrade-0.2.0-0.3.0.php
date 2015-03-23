<?php

$installer = $this;
$installer->startSetup();

$attributeCode = 'hear_about_us';

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$setup->addAttribute('customer', $attributeCode, array(
    'label'         => 'How did you hear about us?',
    'input'         => 'select',
    'type'          => 'int',
    'visible'       => 1,
    'required'      => 0,
    'user_defined'  => 1,
    'source'        => 'eav/entity_attribute_source_table',
    'option'        => array('values' => array(
        'Friend or acquaintance',
        'TV',
        'Radio',
        'Facebook',
    )),
));

$setup->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    $attributeCode,
    '999'
);

$oAttribute = Mage::getSingleton('eav/config')->getAttribute('customer', $attributeCode);
$oAttribute->setData('used_in_forms', array('adminhtml_customer', 'adminhtml_checkout', 'customer_account_edit', 'ho_customer_complete_profile'));
$oAttribute->setData('sort_order', 300);
$oAttribute->save();

$setup->endSetup();