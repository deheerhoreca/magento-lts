<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Product_View_Attributes extends Mage_Catalog_Block_Product_View_Attributes
{

    public function getAdditionalData(array $excludeAttr = array())
    {
        $data = parent::getAdditionalData($excludeAttr);
        $attributes = $this->getProduct()->getAttributes();
        $product = $this->getProduct();

        $data = array_intersect_key($data, $attributes);
        foreach ($data as $code => &$attribute) {
            if ($attributes[$code]->getBackendType() == 'decimal') {
                $filter = Mage::getResourceModel('amshopby/filter')
                    ->getFilterByAttributeId($attributes[$code]->getAttributeId());
                $attribute['value'] = $attributes[$code]->getFrontend()->getValue($product);
                $attribute['value'] = round($attribute['value'], 4) . $filter['value_label'];
            }
        }
        unset($attribute);

        return $data;
    }
}