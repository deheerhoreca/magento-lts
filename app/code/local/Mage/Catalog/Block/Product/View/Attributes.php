<?php
/**
* OpenMage
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available at https://opensource.org/license/osl-3-0-php
*
* @category   Mage
* @package    Mage_Catalog
*
* @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://www.magento.com)
* @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://www.openmage.org)
* @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

/**
* Product description block
*
* @category   Mage
* @package    Mage_Catalog
*/
class Mage_Catalog_Block_Product_View_Attributes extends Mage_Core_Block_Template {
  protected $_product = null;
  
  /**
  * @return mixed|null
  */
  public function getProduct() {
    if(! $this->_product) {
      $this->_product = Mage::registry('product');
    }
    return $this->_product;
  }
  
  /**
  * $excludeAttr is optional array of attribute codes to
  * exclude them from additional data array
  *
  * @param  array   $excludeAttr
  * @return array
  */
  public function getAdditionalData(array $excludeAttr = []) {
    $data       = [];
    $product    = $this->getProduct();
    $attributes = $product->getAttributes();
    foreach($attributes as $attribute) {
      if($attribute->getIsVisibleOnFront() && ! in_array($attribute->getAttributeCode(), $excludeAttr)) {
        $value = $attribute->getFrontend()->getValue($product);
        
        if(! $product->hasData($attribute->getAttributeCode())) {
          $value = Mage::helper('catalog')->__('N/A');
        } elseif(is_null($value) || $value === false || $value === '') {
          $value = Mage::helper('catalog')->__('No');
        } elseif($attribute->getFrontendInput() == 'price' && is_string($value)) {
          $value = Mage::app()->getStore()->convertPrice($value, true);
        }
        
        if(is_string($value) && strlen($value)) {
          $data[$attribute->getAttributeCode()] = [
            'label' => $attribute->getStoreLabel(),
            'value' => $value,
            'code'  => $attribute->getAttributeCode(),
          ];
        }
      }
    }
    return $data;
  }
  
  /**
   *
   * @see https://community.magento.com/t5/Magento-1-x-Programming/How-to-display-Attribute-Group-Name-on-Product-page/td-p/12156
   * @param  array $excludeAttr
   */
  public function getAdditionalDataCustom(array $excludeAttr = []) {
    Varien_Profiler::start("DHH_PRODUCT_DETAILVIEW_PREPROCESS_ATTRIBUTES_getAdditionalDataCustom");
    $data     = [];
    $product  = $this->getProduct();
    $products = [$product];
    
    /* In case of configurable products, we get all the children's attributes as well */
    if($product->getTypeId() === Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
      $childIds = Mage::getModel("catalog/product_type_configurable")->getChildrenIds($product->getId());
      $childIds = array_pop($childIds);
      if(empty($childIds) === false) {
        foreach($childIds as $childId) {
          // @todo improve performance
          $products[] = Mage::getModel("catalog/product")->load($childId);
        }
      }
    }
    
    foreach($products as $product) {
      $attributes = $product->getAttributes();
      foreach($attributes as $attribute) {
        $attribute_code = $attribute->getAttributeCode();
        if(!in_array($attribute_code, $excludeAttr, true) && $product->hasData($attribute_code)&&
          $product->getDAta($attribute_code) !== null && $attribute->getIsVisibleOnFront()) {
          $value = $attribute->getFrontend()->getValue($product);
          
          // if($attribute->getBackendType() == 'decimal') {
          //   /** @var Amasty_Shopby_Model_Mysql4_Filter $amshopbyFilterModel */
          //   $amshopbyFilterModel ??= Mage::getResourceModel('amshopby/filter');
          //   $filter             = $amshopbyFilterModel->getFilterByAttributeId($attribute->getAttributeId());
          //   $attribute['value'] = $attribute->getFrontend()->getValue($product);
          //   $attribute['value'] = round($attribute['value'], 4).$filter['value_label'];
          // }
          
          if(is_string($value) && strlen($value) > 0) {
            $option_id = null;
            if($attribute->usesSource()) {
              $option_id = $product->getData($attribute_code);
            }
            
            // if($attribute->getFrontendInput() == "price") {
            //   $value = Mage::app()->getStore()->convertPrice($value,true);
            // }
            
            $groupId            = 0;
            $attribute_set_info = $attribute->getData("attribute_set_info") ?? [];
            if($attribute_set_info !== []) {
              $this_set = array_pop($attribute_set_info);
              if(isset($this_set["group_id"])) {
                $groupId = $this_set["group_id"];
              }
            }
            
            if(isset($data[$groupId]["items"][$attribute_code])) {
              $data[$groupId]["items"][$attribute_code]["value"][] = $value;
            } else {
              $data[$groupId]["items"][$attribute_code] = [
                "label"     => $attribute->getStoreLabel(),
                "value"     => [$value],
                "code"      => $attribute_code,
                "option_id" => $option_id,
              ];
            }
            $data[$groupId]["attrid"] = $attribute->getId();
          }
        }
      }
    }
    
    $_entity_attribute_groups = Mage::getModel("eav/entity_attribute_group")
      ->getCollection()
      ->addFieldToSelect("attribute_group_id")
      ->addFieldToSelect("attribute_group_name")
      ->load();
    foreach($_entity_attribute_groups as $_entity_attribute_group) {
      $GLOBALS["eav_group_cache"][$_entity_attribute_group->getId()] = $_entity_attribute_group->getAttributeGroupName();
    }
    
    foreach($data as $groupId => &$group) {
      if(isset($GLOBALS["eav_group_cache"][$groupId])) {
        $group["title"] = $GLOBALS["eav_group_cache"][$groupId];
      }
    }
    
    Varien_Profiler::stop("DHH_PRODUCT_DETAILVIEW_PREPROCESS_ATTRIBUTES_getAdditionalDataCustom");
    return $data;
  }
}
