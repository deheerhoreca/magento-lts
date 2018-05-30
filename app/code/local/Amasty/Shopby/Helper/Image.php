<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Helper_Image extends Mage_Catalog_Helper_Image
{
    /** @var array|null */
    protected $requestConfigurableMap;

    /** @var  array|null */
    protected $mappedValues;

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Helper_Image
     */
    public function setProduct($product)
    {
        if ($product->isConfigurable() && $product->isSaleable() && $this->getRequestConfigurableMap()) {
            $child = $this->getMatchingSimpleProduct($this->getChildrenCollection($product));
            if (!$child) {
                // If simple options haven't an image, try to receive it from mapped.
                $child = $this->getMatchingSimpleProductByMappedOptions($this->getChildrenCollection($product));
            }
            if (is_object($child)) {
                $product = $child;
            }
        }

        return parent::setProduct($product);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable_Product_Collection
     */
    protected function getChildrenCollection($product)
    {
        $productTypeIns = $product->getTypeInstance(true);
        return $productTypeIns->getUsedProductCollection($product)
            ->addFieldToFilter('small_image', array('notnull' => true))
            ->addFieldToFilter('small_image', array('neq' => 'no_selection'));
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable_Product_Collection $children
     * @return Mage_Catalog_Model_Product|null
     */
    protected function getMatchingSimpleProduct($children)
    {
        foreach ($this->getRequestConfigurableMap() as $code => $values) {
            $children->addAttributeToFilter($code, array('in' => $values));
        }

        return $this->getImageFromChildrenCollection($children);
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable_Product_Collection $children
     * @return Mage_Catalog_Model_Product|null
     */
    protected function getMatchingSimpleProductByMappedOptions($children)
    {
        if (!$this->getMappedValues()) {
            return null;
        }
        foreach ($this->getMappedValues() as $code => $values) {
            $children->addAttributeToFilter($code, array('in' => $values));
        }

        return $this->getImageFromChildrenCollection($children);
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable_Product_Collection $childrenCollection
     * @return Mage_Catalog_Model_Product|null
     */
    protected function getImageFromChildrenCollection($childrenCollection)
    {
        if ($childrenCollection->getSize()) {
            return $childrenCollection->getFirstItem();
        }
        return null;
    }

    /**
     * @return array
     */
    protected function getRequestConfigurableMap()
    {
        if (null === $this->requestConfigurableMap) {
            $this->requestConfigurableMap = array();
            $configurableCodes = explode(
                ",", trim(Mage::getStoreConfig('amshopby/general/configurable_images'))
            );
            $requestParams = Mage::app()->getRequest()->getQuery();
            $inRequestConfigurableCodes = array_intersect($configurableCodes, array_keys($requestParams));

            foreach ($inRequestConfigurableCodes as $code) {
                $value = $requestParams[$code];
                if (strpos($value, ",") !== false) {
                    $values = explode(",", $value);
                } else {
                    $values = array($value);
                }

                $this->requestConfigurableMap[$code] = $values;
            }
        }
        return $this->requestConfigurableMap;
    }


    /**
     * @return array
     */
    protected function getMappedValues()
    {
        if (null === $this->mappedValues) {
            if (!$this->getRequestConfigurableMap()) {
                return $this->mappedValues = array();
            }
            $helper = Mage::helper('amshopby/attributes');
            $prefix = $helper::MAPPED_PREFIX;
            $linkHash = $helper->getValueLinkHash();
            $simpleValues = array();
            $result = array();
            foreach ($this->getRequestConfigurableMap() as $code => $values) {
                foreach ($values as $value) {
                    if (strpos($value, $prefix) === 0) {
                        $simpleValues[] = str_replace($prefix, '', $value);
                    }
                }
                $result[$code] = array();
                foreach ($simpleValues as $mappedValue) {
                    if (isset($linkHash[$mappedValue])) {
                        $result[$code] = array_merge($result[$code], $linkHash[$mappedValue]);
                    }
                }
            }
            $this->mappedValues = $result;
        }

        return $this->mappedValues;
    }
}
