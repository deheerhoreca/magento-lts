<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Product_View_Attributes extends Mage_Catalog_Block_Product_View_Attributes
{

    public function getAdditionalData(array $excludeAttr = array())
    {
        $data = parent::getAdditionalData($excludeAttr);
        $attributes = $this->getProduct()->getAttributes();

        foreach ($data as $code => &$attribute) {
            if ($attributes[$code]->getBackendType() == 'decimal') {
                $filter = Mage::getResourceModel('amshopby/filter')
                    ->getFilterByAttributeId($attributes[$code]->getAttributeId());
                $attribute['value'] = $this->convertDecimal(
                    preg_replace('/[^\d.]/', '', $attribute['value']), $filter['value_label']
                );
            }
        }
        unset($attribute);

        return $data;
    }

    public function convertDecimal($value, $label)
    {
        return '<span class="decimal">' . round($value, 4) . $label .'</span>';
    }
}