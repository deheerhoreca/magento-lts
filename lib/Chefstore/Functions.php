<?php

// @todo Enable:
// declare(strict_types=1);

if(!function_exists("om_attr_val")) {
  /**
   * Retrieve a product attribute value.
   * @todo Add null coalescing variant of this function.
   *
   * @param  ?Mage_Catalog_Model_Product  $_product
   * @param  string                       $attribute_code
   * @param  ?string                      $as              "" | "string" | "int"
   * @param  array                        $options         Unused
   *
   * @return mixed
   */
  function om_attr_val(?Mage_Catalog_Model_Product $_product, string $attribute_code, ?string $as = "", array $options = []): mixed {
    if(!is_object($_product)) {
      return null;
    }
    if($_attribute = $_product->getResource()->getAttribute($attribute_code)) {
      $value = $_attribute->getFrontend()->getValue($_product);
      if(!blank($as)) {
        if($as === "array") {
          if(!is_scalar($value)) {
            return [$value];
          }
          return (array) $value;
        }
        if($as === "string") {
          if(is_iterable($value)) {
            return implode(", ", (array) $value);
          }
          return (string) $value;
        }
        if($as === "int") {
          return (int) $value;
        }
      }
      return $value;
    }
    $dhh_sku = $_product->getSku("dhh_sku") ?? "NO_DHH_SKU";  
    Mage::log("{$dhh_sku} Unknown attribute requested: {$attribute_code}", Zend_Log::NOTICE);
    
    return false;
  }
}

if(!function_exists("om_attr_val_as_array")) {
  /**
   * Get OM attribute as an array.
   * 
   * @param  ?Mage_Catalog_Model_Product  $_product
   * @param  string                       $attribute_code
   * @param  array                        $options         Unused
   * 
   * @return array|null
   */
  function om_attr_val_as_array(?Mage_Catalog_Model_Product $_product, string $attribute_code, array $options = []): array|null {
    if($_product === null) {
      return [];
    }
    
    $_attribute = $_product->getResource()->getAttribute($attribute_code);
    if($_attribute->getFrontendInput() === "multiselect") {
      $frontend = $_attribute->getFrontend();
      $values   = explode(",", $_product->getData($attribute_code));
      $values   = Arr::map($values, fn($id) => $frontend->getOption($id));
      return $values;
    } else {
      $values = om_attr_val($_product, $attribute_code, "array");
    }
    $ret = !empty($values) ? (array) $values : [];  // empty(), not blank()
    
    return $ret;
  }
}

/**
 * Get attribute value as string.
 * => Don't make $_product an (object) type hint
 * 
 * @param  ?Mage_Catalog_Model_Product  $_product
 * @param  string                       $attribute_code
 * @param  ?string                      $as              "" | "string" | "int"
 * @param  array                        $options         Unused
 * 
 * @return int|null
 */
if(!function_exists("om_attr_val_as_string")) {
  function om_attr_val_as_string(?Mage_Catalog_Model_Product $_product, string $attribute_code, array $options = []): string {
    return (string) om_attr_val($_product, $attribute_code, "string");
  }
}

/**
 * Get attribute value as float.
 * => Don't make $_product an (object) type hint
 * 
 * @param  ?Mage_Catalog_Model_Product  $_product
 * @param  string                       $attribute_code
 * @param  ?string                      $as              "" | "string" | "int"
 * @param  array                        $options         Unused
 * 
 * @return int|null
 */
if(!function_exists("om_attr_val_as_float")) {
  function om_attr_val_as_float(?Mage_Catalog_Model_Product $_product, string $attribute_code, array $options = []): float|null {
    $value = om_attr_val($_product, $attribute_code);
    if(is_numeric($value)) {
      return float(round($value));
    }
    
    return null;
  }
}

/**
 * Get attribute value as int.
 * => Don't make $_product an (object) type hint
 * 
 * @param  ?Mage_Catalog_Model_Product  $_product
 * @param  string                       $attribute_code
 * @param  ?string                      $as              "" | "string" | "int"
 * @param  array                        $options         Unused
 * 
 * @return int|null
 */
if(!function_exists("om_attr_val_as_int")) {
  function om_attr_val_as_int(?Mage_Catalog_Model_Product $_product, string $attribute_code, array $options = []): int|null {
    $value = om_attr_val_as_float($_product, $attribute_code, $options);
    if(is_numeric($value)) {
      return int(round($value));
    }
    
    return null;
  }
}

/**
 * Get cacheable product object.
 * 
 * Built to tame qquote module.
 *
 * @param  integer|string|Mage_Catalog_Model_Product|null $product
 * @return Mage_Catalog_Model_Product|false|null
 */
function dhh_get_cached_om_product(int|string|Mage_Catalog_Model_Product|null $product): Mage_Catalog_Model_Product|false|null {
  
  // COMMENT THIS to enable the cache (untested)
  // return Mage::getModel('catalog/product')->load($product); // Run the original code
  
  $GLOBALS["dhh_get_cached_om_product"] ??= [];
  
  if($product === null) {
    // Mage::log("dhh_get_cached_om_product::NULL", Zend_Log::DEBUG, "verbose.txt", true);
    return null;
  }
  
  if($product instanceof Mage_Catalog_Model_Product) {
    // Mage::log("dhh_get_cached_om_product::{$product->getId()} called with existing Mage_Catalog_Model_Product ", Zend_Log::DEBUG, "verbose.txt", true);
    return $product;
  }
  
  $product_id = $product;
  
  if(!is_numeric($product_id) || intval($product_id) < 1) {
    // Mage::log("dhh_get_cached_om_product::".json_encode($product_id).": Not a numeric product_id", Zend_Log::DEBUG, "verbose.txt", true);
    return Mage::getModel('catalog/product')->load($product_id); // Run the original code
  }
  
  $product_id = (int) $product_id;
  
  if(!isset($GLOBALS["dhh_get_cached_om_product"][$product_id])) {
    // Mage::log("dhh_get_cached_om_product::{$product_id}, SAVE", Zend_Log::DEBUG, "verbose.txt", true);
    if($_product = Mage::getModel('catalog/product')->load($product_id)) {
      $GLOBALS["dhh_get_cached_om_product"][$product_id] = $_product;
      destruct($_product);
    }
  }
  
  // Mage::log("dhh_get_cached_om_product::".json_encode($product_id).", HIT", Zend_Log::DEBUG, "verbose.txt", true);
  
  return $GLOBALS["dhh_get_cached_om_product"][$product_id];
}

/**
 * Get cacheable category object.
 *
 * @param  integer|string                   $id
 * @param  boolean                          $forceRefresh
 * @return Mage_Catalog_Model_Category|null
 */
function dhh_get_cached_category(int|string $id, bool $forceRefresh = false): Mage_Catalog_Model_Category|null {
  $id         = (int) $id;
  $currentUrl = dhh_get_current_url();
  $field      = null;
  
  if(!$forceRefresh && Mage::helper("aoe_modelcache")->exists("catalog/category", $id)) {
    Mage::log(__FUNCTION__."::{$id} HIT   [{$currentUrl}]", Zend_Log::DEBUG, "verbose.txt", true);
  } else {
    Mage::log(__FUNCTION__."::{$id} SAVE  [{$currentUrl}]", Zend_Log::DEBUG, "verbose.txt", true);
  }
  
  return Mage::helper("aoe_modelcache")->get("catalog/category", $id, $field, $forceRefresh);
}

/**
 * Get cacheable product object.
 *
 * @param  integer|string                  $id
 * @param  boolean                         $forceRefresh
 * @return Mage_Catalog_Model_Product|null
 */
function dhh_get_cached_product(int|string $id, bool $forceRefresh = false): Mage_Catalog_Model_Product|null {
  $id         = (int) $id;
  $currentUrl = dhh_get_current_url();
  $field      = null;
  
  if(!$forceRefresh && Mage::helper("aoe_modelcache")->exists("catalog/product", $id)) {
    if(dhh_profiler_enabled()) {
      Mage::log(__FUNCTION__."::{$id} HIT   [{$currentUrl}]", Zend_Log::DEBUG, "verbose.txt", true);
    }
  } else {
    if(dhh_profiler_enabled()) {
      Mage::log(__FUNCTION__."::{$id} SAVE  [{$currentUrl}]", Zend_Log::DEBUG, "verbose.txt", true);
    }
  }
  
  return Mage::helper("aoe_modelcache")->get("catalog/product", $id, $field, $forceRefresh);
}

/**
 * Get current URL from OpenMage. Set once, then cached.
 *
 * @return string
 */
function dhh_get_current_url(): string {
  $GLOBALS[__FUNCTION__] ??= htmlspecialchars_decode(Mage::helper("core/url")->getCurrentUrl(), ENT_COMPAT | ENT_HTML5 | ENT_HTML401);
  return $GLOBALS[__FUNCTION__];
}

/**
 * Returns whether the OM profiler is enabled. @todo fix for POST by not looking at the request.
 *
 * @return boolean
 */
function dhh_profiler_enabled(): bool {
  if(!(\Mage::app()->getRequest()->getParam("profile", false))) {
    return false;
  }
  
  return true;
}

/**
 * Get an empty product collection.
 *
 * @return Mage_Catalog_Model_Resource_Product_Collection
 */
function getProductCollection(): Mage_Catalog_Model_Resource_Product_Collection {
  /** @var Mage_Catalog_Model_Resource_Product_Collection */
  $_products = Mage::getResourceModel("catalog/product_collection");
  
  return $_products;
}

/**
 * Get a product URL by product entity ID, efficiently.
 *
 * @param  integer      $productId
 * @return string|false
 */
function getProductUrlById(int $id): string|false {
  
  $return = false;
  
  if($_products = getProductCollection()
    ->addAttributeToSelect("product_url")
    ->addIdFilter($id)
    ->addUrlRewrite()->load()) {
    
    /** @var Mage_Catalog_Model_Product */
    if($_product = $_products->getFirstItem()) {
      $return = $_product->getProductUrl();
      destruct($_product);
    }
    
    destruct($_products);
  }
  
  if(dhh_profiler_enabled()) {
    Mage::log(__FUNCTION__."(".json_encode(func_get_args()).") => ".json_encode($return), Zend_Log::DEBUG, "verbose.txt", true);
  }
  
  return $return;
}

/**
 * Get a product URL by product SKU, efficiently.
 *
 * @param  integer      $productId
 * @return string|false
 */
function getProductUrlBySku(string $sku): string|false {
  
  $return = false;
  
  if($_products = getProductCollection()
    ->addAttributeToSelect("product_url")
    ->addAttributeToFilter("sku", $sku)
    ->addUrlRewrite()->load()) {
    
    /** @var Mage_Catalog_Model_Product */
    if($_product = $_products->getFirstItem()) {
      $return = $_product->getProductUrl();
      destruct($_product);
    }
    
    destruct($_products);
  }
  
  if(dhh_profiler_enabled()) {
    Mage::log(__FUNCTION__."(".json_encode(func_get_args()).") => ".json_encode($return), Zend_Log::DEBUG, "verbose.txt", true);
  }
  
  return $return;
}
