<?php

class Ho_Customer_Model_System_Config_Source_PromoRules
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $rules = Mage::getModel('salesrule/rule')->getCollection();

        $options = array();
        foreach ($rules as $rule)
        {
            $options[] = array(
                'value' => $rule->getId(),
                'label' => $rule->getName(),
            );
        }

        return $options;
    }
}
