<?php

class DeHeerHoreca_Util_Model_Observer extends Varien_Event_Observer {
  
  public function __construct() {}
  
  public function updateProductBeforeSave($observer) {
    $product = $observer->getProduct();
    // echo "<pre>"; print_r($product->getData());
    // var_dump($product->getData("bargain"));
    // var_dump($product->getData("featured"));
    
    /* END OF LIFE */
    if($product->getData("eol") === "1") {
      if(empty($product->getData("tagline")) === false) {
        $product->setData("tagline", null);
        Mage::getSingleton('core/session')->addSuccess("Product is EOL: Tagline removed");
      }
      if($product->getData("featured") === "1") {
        $product->setData("featured", "0");
        Mage::getSingleton('core/session')->addSuccess("Product is EOL: Featured flag removed");
      }
      if($product->getData("bargain") === "1") {
        $product->setData("bargain", "0");
        Mage::getSingleton('core/session')->addSuccess("Product is EOL: Bargain flag removed");
      }
    }
    
    /* MERCHANDISING */
    
    if(empty($product->getData("tagline")) === false) {
      $product->setData("recommended_product", "1826");
    } else {
      $product->setData("recommended_product", "0");
    }
  }
  
  public function updateProductAfterSave($observer) {
    $product = $observer->getProduct();
    $productId = $product->getId();
    
    /* END OF LIFE */
    if($product->getData("eol") === "1") {
      $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
      $stockItem->setData('use_config_manage_stock', 0);
      $stockItem->setData('use_config_backorders', 0);
      $stockItem->setData('manage_stock', 1);
      $stockItem->setData('is_in_stock', 0);
      $stockItem->setData('qty', 0);
      $stockItem->setData('backorders', 0);
      
      if($stockItem->getOrigData() != $stockItem->getData()) {
        try {
          if($stockItem->save()) {
            Mage::getSingleton('core/session')->addSuccess("Product is EOL: Stock data altered");
          }
        } catch (Excpetion $e) {
          Mage::getSingleton('core/session')->addError("Failed to apply EOL business rules on stock item: {$e->getMessage()}");
        }
      }      
    }
    
    /* STOCK */    
    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
    // echo "<pre>"; var_dump($stockItem->getData());exit;
    
    if($stockItem->getManageStock() === 0 && $stockItem->getData('use_config_manage_stock') === "0") {
      $stockItem->setData('is_in_stock', 1);
      // $stockItem->setData('qty', 100);
      
      if($stockItem->getOrigData() != $stockItem->getData()) {
        try {
          if($stockItem->save()) {
            Mage::getSingleton('core/session')->addSuccess("Stock is unmanaged: Setting defaults.");
          }
        } catch (Excpetion $e) {
          Mage::getSingleton('core/session')->addError("Failed to apply 'unmanaged' business rules on stock item: {$e->getMessage()}");
        }
      }      
    } elseif($stockItem->getManageStock() === "1" && $stockItem->getData('use_config_manage_stock') === "0") {
      if($stockItem->getData('qty') > 0 && $stockItem->getData('is_in_stock') === "0") {
        $stockItem->setData('is_in_stock', 1);
        try {
          if($stockItem->save()) {
            Mage::getSingleton('core/session')->addSuccess("Product is back in stock: Setting in_stock to Yes.");
          }
        } catch (Excpetion $e) {
          Mage::getSingleton('core/session')->addError("Failed to update product stock status: {$e->getMessage()}");
        }
      }
    }
    // exit;
    return;
  }
}
