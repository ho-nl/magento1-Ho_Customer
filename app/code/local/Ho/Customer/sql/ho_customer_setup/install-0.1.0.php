<?php

$installer = $this;
$installer->startSetup();

$attributeCode = 'complete_profile_sent';

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$setup->addAttribute('customer', $attributeCode, array(
    'label'         => 'Complete Profile Mail Sent',
    'input'         => 'select',
    'type'          => 'int',
    'visible'       => 1,
    'required'      => 0,
    'user_defined'  => 0,
    'sort_order'    => 999,
    'source'        => 'eav/entity_attribute_source_boolean',
));

$setup->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    $attributeCode,
    '999'
);

$oAttribute = Mage::getSingleton('eav/config')->getAttribute('customer', $attributeCode);
$oAttribute->setData('used_in_forms', array('adminhtml_customer', 'adminhtml_checkout'));
$oAttribute->setData('sort_order', 999);
$oAttribute->save();

$setup->endSetup();