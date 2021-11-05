<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product description block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Block_Product_View_Attributes extends Mage_Core_Block_Template
{
    protected $_product = null;

    /**
     * @return mixed|null
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = Mage::registry('product');
        }
        return $this->_product;
    }

    /**
     * $excludeAttr is optional array of attribute codes to
     * exclude them from additional data array
     *
     * @param array $excludeAttr
     * @return array
     */
    public function getAdditionalData(array $excludeAttr = array())
    {
        $data = array();
        $product = $this->getProduct();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($product);

                if (!$product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif (is_null($value) || $value === false || $value === '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
        return $data;
    }
  
    // DHH CORE HACK
    // Taken from https://community.magento.com/t5/Magento-1-x-Programming/How-to-display-Attribute-Group-Name-on-Product-page/td-p/12156

    public function getAdditionalDataCustom(array $excludeAttr = []) {
      $data       = [];
      $product    = $this->getProduct();
      $products   = [$product];
      
      /* In case of configurable products, we get all the children's attributes as well */
      if($product->getTypeId() === Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
        // $childIds = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId());
        $childIds = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId());
        // if($_SERVER["REMOTE_ADDR"] === "185.127.111.251" && isset($_GET["nofpc"])) {
         // print_r($childIds);
        // }
        $childIds = array_pop($childIds);
        if(empty($childIds) === false) {
          foreach($childIds as $childId) {
            $products[] = Mage::getModel('catalog/product')->load($childId);
          }
        }
      }
            
      foreach($products as $product) {

        $attributes = $product->getAttributes();

        foreach($attributes as $attribute) {
          
          if($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            
            $value = $attribute->getFrontend()->getValue($product);
            $option_id = null;
            if($attribute->usesSource()) {
              $option_id = $attribute->setStoreId(0)->getSource()->getOptionId($value);
            }
                        
            // Do not display "No" on some attribute types
            // "No/Nee" is put 
            // @todo replace boolean attributes with proper dropdowns that include "n.v.t."
            // if($value === "Nee") {
              // continue;
            // }
            
            if(is_string($value)) {
              if(strlen($value) && $product->hasData($attribute->getAttributeCode())) {
              //if ($attribute->getFrontendInput() == 'price') {
              //$value = Mage::app()->getStore()->convertPrice($value,true);
              //}

              $group = 0;
              if($tmp = $attribute->getData('attribute_group_id') ) {
                $group = $tmp;
              }
              
              if(isset($data[$group]['items'][$attribute->getAttributeCode()])) {
                $data[$group]['items'][$attribute->getAttributeCode()]["value"][] = $value;
              } else {
                $data[$group]['items'][$attribute->getAttributeCode()] = [
                  'label'     => $attribute->getStoreLabel(),
                  'value'     => [$value],
                  'code'      => $attribute->getAttributeCode(),
                  'option_id' => $option_id,
                ];
              }

              $data[$group]['attrid'] = $attribute->getId();

              }
            }
          }
        }
      }

      foreach($data AS $groupId => &$group) {
        $groupModel     = Mage::getModel('eav/entity_attribute_group')->load($groupId);
        $group['title'] = $groupModel->getAttributeGroupName();
      }
      
      // if($_SERVER["REMOTE_ADDR"] === "185.127.111.251" && isset($_GET["nofpc"])) {
        // echo "<pre>";print_r($data);echo "</pre>";
      // }

      return $data;
    }
    // DHH END
}
