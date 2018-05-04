<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Searchautocomplete
 * @version    3.4.8
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Searchautocomplete_Model_Source_Product_Attribute
{
    public $_entityTypeId = null;
    public $_productAttributes = null;
    public $_productAttributeOptionsArray = null;
    public $_productAttributeArray = null;

    public function getEntityTypeId()
    {
        if (!$this->_entityTypeId) {
            $this->_entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();
        }
        return $this->_entityTypeId;
    }

    public function getProductAttributeList()
    {
        if (!$this->_productAttributes) {
            $this->_productAttributes = array();
            $attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToSelect('attribute_id', 'id')
                ->addFieldToSelect('frontend_label', 'title')
                ->addFieldToSelect('attribute_code', 'code')
                ->addFieldToSelect('backend_type', 'type')
                ->addFieldToFilter(
                    'backend_type',
                    array('in' => array('text', 'varchar', 'static'))
                )
                ->addFieldToFilter(
                    'frontend_label',
                    array('neq' => '')
                )
                ->setOrder(
                    'frontend_label',
                    Varien_Data_Collection::SORT_ORDER_ASC
                )
            ;

            foreach ($attributeCollection as $attribute) {
                $this->_productAttributes[$attribute->getData('id')] = $attribute->getData();
            }
        }
        return $this->_productAttributes;
    }

    public function toOptionArray()
    {
        if (!is_array($this->_productAttributeOptionsArray)) {
            $this->_productAttributeOptionsArray = array();
            foreach ($this->getProductAttributeList() as $attributeId => $attributeData) {
                $this->_productAttributeOptionsArray[] = array(
                    'value' => $attributeId,
                    'label' => "{$attributeData['title']} ({$attributeData['code']})"
                );
            }
        }
        return $this->_productAttributeOptionsArray;
    }

    public function toArray()
    {
        if (!is_array($this->_productAttributeArray)) {
            $this->_productAttributeArray = array();
            foreach ($this->getProductAttributeList() as $attributeId => $attributeData) {
                $this->_productAttributeArray[$attributeData['code']] = "{$attributeData['title']} ({$attributeData['code']})";
            }
        }
        return $this->_productAttributeArray;
    }
}