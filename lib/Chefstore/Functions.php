<?php

// Product attributes only
// Don't make $_product an (object) type hint
if(!function_exists("om_attr_val")) {
  function om_attr_val($_product, string $attribute_code, ?string $as = null, array $options = []) { // do not add type hint to $_product
    
    if(!is_object($_product)) {
      return null;
    }
    
    if($_attribute = $_product->getResource()->getAttribute($attribute_code)) {
      $value = $_attribute->getFrontend()->getValue($_product);
      if(!blank($as)) {
        if($as === "string") return (string)  $value;
        if($as === "int")    return (int)     $value;
      }
      
      return $value;
    }
    
    $dhh_sku = $_product->getSku("dhh_sku") ?? "NO_DHH_SKU";  
    Mage::log("{$dhh_sku} Unknown attribute requested: {$attribute_code}", Zend_Log::NOTICE);
    
    return false;
  }
}

// Don't make $_product an (object) type hint
if(!function_exists("om_attr_val_as_string")) {
  function om_attr_val_as_string($_product, string $attribute_code, array $options = []): string {  // do not add type hint to $_product
    return (string) om_attr_val($_product, $attribute_code);
  }
}
