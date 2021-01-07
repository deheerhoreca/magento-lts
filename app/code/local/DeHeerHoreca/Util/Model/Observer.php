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
    $product->lockAttribute('gross_margin_perc');
    $product->lockAttribute('gross_margin_euro');
    $product->lockAttribute('last_auto_stock');
  }
  
  // Also used directly in resave_all_products.php
  public function updateProductBeforeSave($observer_or_product) {
    if(get_class($observer_or_product) === "Varien_Event_Observer") {
      $product = $observer_or_product->getProduct();
      $return = false;
    } else {
      $return = true;
      $product = $observer_or_product;
    }
    
    // echo "<pre>"; print_r($product->getData());
    // var_dump($product->getData("bargain"));
    // var_dump($product->getData("featured"));
    
    /* SHORT NAME */
    if(strlen($product->getData("name_short")) < 3) {
      $new_value = $product->getAttributeText("supplier")." ".$product->getData("sku_seller");
      $product->setData("name_short", $new_value);
      if($return === false) Mage::getSingleton('core/session')->addSuccess("Auto-filled name_short");
    }
    
    /* END OF LIFE */
    if($product->getData("eol") === "2075") {
      if(empty($product->getData("tagline")) === false) {
        $product->setData("tagline", null);
        if($return === false) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Tagline removed");
      }
      if($product->getData("featured") === "1") {
        $product->setData("featured", "0");
        if($return === false) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Featured flag removed");
      }
      if($product->getData("bargain") === "1") {
        $product->setData("bargain", "0");
        if($return === false) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Bargain flag removed");
      }
      if(empty($product->getData("txtstockdate")) === false) {
        $product->setData("txtstockdate", null);
        if($return === false) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Back in stock date removed");
      }
      if($product->getData("skip_auto_stock") !== "1") {
        $product->setData("skip_auto_stock", "1");
        if($return === false) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Excluded from stock updates");
      }
      if(empty($product->getData("product_label")) === false) {
        $product->setData("product_label", null);
        if($return === false) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Product label removed");
      }
      // var_dump($product->getVisibility());exit;
      if((int) $product->getVisibility() === 4 || (int) $product->getVisibility() === 2) {
        $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH);
        if($return === false) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Visibility set to Search only");
      }
    }
    
    /* PRICING */
    
    // Fill cost from msrp and price_supplier_discount_perc
    if(empty($product->getData("msrp")) === false) {
      if(empty($product->getData("price_supplier_discount_perc")) === false) {
        $new_value = (float) round($product->getData("msrp") * (1 - ($product->getData("price_supplier_discount_perc") / 100)), 2);
        if($new_value > 0) {
          $current_value = (double) $product->getData("cost");
          if($current_value !== $new_value) {
            $product->setData("cost", $new_value);
            if($return === false) Mage::getSingleton('core/session')->addSuccess("Cost Price overwritten");
          }
        }
      }
      
      // Fill price_min from msrp and price_supplier_msrp_disc_limit
      if(empty($product->getData("price_supplier_msrp_disc_limit")) === false) {
        $new_value = (double) round($product->getData("msrp") * (1 - ($product->getData("price_supplier_msrp_disc_limit") / 100)), 2);
        if($new_value > 0) {
          $current_value = (double) $product->getData("price_min");
          if($current_value !== $new_value) {
            $product->setData("price_min", $new_value);
            if($return === false) Mage::getSingleton("core/session")->addSuccess("price_min overwritten");
          }
        }
      }
    }
    
    
    if(empty($product->getData("price_supplier_msrp_disc_limit")) === true) {
      // Clear the value if price_supplier_msrp_disc_limit is empty
      if(empty($product->getData("price_min")) === false) {
        $product->setData("price_min", null);
        if($return === false) Mage::getSingleton("core/session")->addSuccess("price_min emptied");
      }
    }
    
    // if(empty($product->getData("price_bol_be_auto")) === false && $product->getData("price_bol_be_auto") === "1") {
      // $new_value = (float) $product->getData("special_price") * 1.21;
      // $new_value = round($new_value, 0);
      // $new_value = (string) $new_value;
      // if($new_value > 0 && $new_value != $product->getData("price_bol_be")) {
        // $product->setData("price_bol_be", $new_value);
        // if($return === false) Mage::getSingleton('core/session')->addSuccess("price_bol_be auto-filled");
      // }
    // }
    
    // if(empty($product->getData("price_bol_nl_auto")) === false && $product->getData("price_bol_nl_auto") === "1") {
      // $new_value = (float) $product->getData("special_price") * 1.21;
      // $new_value = round($new_value, 0);
      // $new_value = (string) $new_value;
      // if($new_value > 0 && $new_value != $product->getData("price_bol_nl")) {
        // $product->setData("price_bol_nl", $new_value);
        // if($return === false) Mage::getSingleton('core/session')->addSuccess("price_bol_nl auto-filled");
      // }
    // }
    
    if(empty($product->getData("cost")) === false && $product->getData("cost") > 0) {
      $our_price = (float) $product->getData("price");
      if(empty($product->getData("special_price")) === false) {
        $our_price = (float) $product->getData("special_price");
      }
      
      if($our_price > 0) {
        // Gross Margin EUR
        $new_value = (float) number_format($our_price - $product->getData("cost"), 4, null, "");
        if($new_value > 0 && $new_value != (float) $product->getData("gross_margin_euro")) {
          $product->setData("gross_margin_euro", $new_value);
          if($return === false) Mage::getSingleton('core/session')->addSuccess("gross_margin_euro auto-filled");
        }
        
        // Gross Margin PERC
        $new_value = (float) number_format(((($our_price - $product->getData("cost")) / $our_price) * 100), 4, null, "");
        if($new_value > 0 && $new_value != (float) $product->getData("gross_margin_perc")) {
          $product->setData("gross_margin_perc", $new_value);
          if($return === false) Mage::getSingleton('core/session')->addSuccess("gross_margin_perc auto-filled");
        }
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
    
    /* POWER */
    if(empty($product->getData("vermogen")) === false
    || empty($product->getData("vermogen_kw")) === false) {
      $vermogen    = empty($product->getData("vermogen")) === true    ? 0 : (double) $product->getData("vermogen");
      $vermogen_kw = empty($product->getData("vermogen_kw")) === true ? 0 : (double) $product->getData("vermogen_kw");
      $new_value   = ($vermogen + $vermogen_kw) * 1000;
      
      if($new_value > 0 && $new_value != $product->getData("total_power_watt")) {
        $product->setData("total_power_watt", $new_value);
        if($return === false) Mage::getSingleton('core/session')->addSuccess("total_power_watt auto-filled");
      }
    }
    
    /* EAN */
    if(empty($product->getData("ean")) === false && strlen($product->getData("ean")) < 13) {
      $new_value = sprintf('%013d', $product->getData("ean"));
      $product->setData("ean", $new_value);
      if($return === false) Mage::getSingleton('core/session')->addSuccess("ean zerofilled");
    }
    
    if($return === true) {
      return $product;
    }
    
    /* NOTHING BELOW THIS */
  }
  
  // Also used directly in resave_all_products.php
  public function updateProductAfterSave($observer_or_product) {
    if(get_class($observer_or_product) === "Varien_Event_Observer") {
      $product = $observer_or_product->getProduct();
      $return = false;
    } else {
      $return = true;
      $product = $observer_or_product;
    }
    
    // $product = $observer->getProduct();
    $productId = $product->getId();
    
    /* END OF LIFE */
    if($product->getAttributeText("eol") === "Ja") {
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
            if($return === false) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Stock data altered");
          }
        } catch (Excpetion $e) {
          if($return === false) Mage::getSingleton('core/session')->addError("Failed to apply EOL business rules on stock item: {$e->getMessage()}");
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
            if($return === false) Mage::getSingleton('core/session')->addSuccess("Stock is unmanaged: Setting defaults.");
          }
        } catch (Excpetion $e) {
          if($return === false) Mage::getSingleton('core/session')->addError("Failed to apply 'unmanaged' business rules on stock item: {$e->getMessage()}");
        }
      }      
    } elseif($stockItem->getManageStock() === "1" && $stockItem->getData('use_config_manage_stock') === "0") {
      if($stockItem->getData('qty') > 0 && $stockItem->getData('is_in_stock') === "0") {
        $stockItem->setData('is_in_stock', 1);
        try {
          if($stockItem->save()) {
            if($return === false) Mage::getSingleton('core/session')->addSuccess("Product is back in stock: Setting in_stock to Yes.");
          }
        } catch (Excpetion $e) {
          if($return === false) Mage::getSingleton('core/session')->addError("Failed to update product stock status: {$e->getMessage()}");
        }
      }
    }
    
    if($return === true) {
      return $product;
    }
    
    // exit;
    return;
  }

  // Adds an EOL = No filter to any listview unless it's explicitly set to Yes
  public function addEolFilter($observer) {
    // $productCollection = $observer->getEvent()->getCollection();
    
    // /* If the EOL filter is not set to "Yes", apply a default filter that removes EOL products */
    // if(Mage::helper('core')->isModuleEnabled('Amasty_Shopby')) {
      // $eol_filter = Mage::helper('amshopby')->getRequestValues("eol") ?? false;
      // if(empty($eol_filter[0]) === false && 
        // ($eol_filter[0] === "2075" || $eol_filter[0] === "1910")) { // Prod and Dev IDs
        // // EOL filter set to "Yes", do nothing
      // } else {
        // $productCollection->addAttributeToFilter([
          // ['attribute' => "eol",  ['null' => true]],
          // ['attribute' => "eol", ['eq'   => '2074']],
          // ['attribute' => "eol", ['eq'   => 'NO FIELD']],
        // ], '', 'left');
      // }
    // }

    // $observer->getEvent()->setCollection($productCollection);    
    
    // if($_SERVER["REMOTE_ADDR"] === "185.127.111.251" && isset($_GET['nofpc'])) {
      // echo $productCollection->getSelect()->__toString();
      // $filters = Mage::getSingleton('catalog/layer')->getState()->getFilters();
      // echo $productCollection->getSize();
    // }
    
  }

}
