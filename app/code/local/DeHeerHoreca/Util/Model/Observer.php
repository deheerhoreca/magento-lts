<?php

class DeHeerHoreca_Util_Model_Observer extends Varien_Event_Observer {
  
  public function __construct() {}
  
  public function updateProductOnEdit($observer) {
    $event = $observer->getEvent();
    $product = $event->getProduct();
    $product->lockAttribute('cost');
    $product->lockAttribute('price_min');
    $product->lockAttribute('recommended_product');
    $product->lockAttribute('additional_attributes');
    $product->lockAttribute('automation_flags_json');
    $product->lockAttribute('amazon_id');
  }
  
  public function updateProductBeforeSave($observer) {
    $product = $observer->getProduct();
    // echo "<pre>"; print_r($product->getData());
    // var_dump($product->getData("bargain"));
    // var_dump($product->getData("featured"));
    
    /* SHORT NAME */
    if(strlen($product->getData("name_short")) < 3) {
      $new_value = $product->getAttributeText("supplier")." ".$product->getData("sku_seller");
      $product->setData("name_short", $new_value);
      Mage::getSingleton('core/session')->addSuccess("Auto-filled name_short");
    }
    
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
      if(empty($product->getData("txtstockdate")) === false) {
        $product->setData("txtstockdate", null);
        Mage::getSingleton('core/session')->addSuccess("Product is EOL: Back in stock date removed");
      }
      if($product->getData("skip_auto_stock") !== "1") {
        $product->setData("skip_auto_stock", "1");
        Mage::getSingleton('core/session')->addSuccess("Product is EOL: Excluded from stock updates");
      }
    }
    
    /* PRICING */
    if(empty($product->getData("msrp")) === false) {
      if(empty($product->getData("price_supplier_discount_perc")) === false) {
        $new_value = (float) round($product->getData("msrp") * (1 - ($product->getData("price_supplier_discount_perc") / 100)), 2);
        if($new_value > 0) {
          $current_value = (double) $product->getData("cost");
          if($current_value !== $new_value) {
            $product->setData("cost", $new_value);
            Mage::getSingleton('core/session')->addSuccess("Cost Price overwritten");
          }
        }
      }
      if(empty($product->getData("price_supplier_msrp_disc_limit")) === false) {
        $new_value = (double) round($product->getData("msrp") * (1 - ($product->getData("price_supplier_msrp_disc_limit") / 100)), 2);
        if($new_value > 0) {
          $current_value = (double) $product->getData("price_min");
          if($current_value !== $new_value) {
            $product->setData("price_min", $new_value);
            Mage::getSingleton("core/session")->addSuccess("price_min overwritten");
          }
        }
      }
    }
    
    if(empty($product->getData("price_bol_be_auto")) === false && $product->getData("price_bol_be_auto") === "1") {
      $new_value = (float) $product->getData("special_price") * 1.21;
      $new_value = round($new_value, 0);
      $new_value = (string) $new_value;
      if($new_value > 0 && $new_value != $product->getData("price_bol_be")) {
        $product->setData("price_bol_be", $new_value);
        Mage::getSingleton('core/session')->addSuccess("price_bol_be auto-filled");
      }
    }
    
    if(empty($product->getData("price_bol_nl_auto")) === false && $product->getData("price_bol_nl_auto") === "1") {
      $new_value = (float) $product->getData("special_price") * 1.21;
      $new_value = round($new_value, 0);
      $new_value = (string) $new_value;
      if($new_value > 0 && $new_value != $product->getData("price_bol_nl")) {
        $product->setData("price_bol_nl", $new_value);
        Mage::getSingleton('core/session')->addSuccess("price_bol_nl auto-filled");
      }
    }
    
    /* MERCHANDISING */
    if(empty($product->getData("tagline")) === false) {
      if($product->getData("recommended_product") !== "1826") {
        $product->setData("recommended_product", "1826");
      }
    } else {
      if($product->getData("recommended_product") !== "0") {
        $product->setData("recommended_product", "0");
      }
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
