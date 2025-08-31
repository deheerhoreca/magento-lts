<?php

use \Elastic\Apm\ElasticApm;
use \Elastic\Apm\TransactionInterface;
use \Chefstore\Helper;

class DeHeerHoreca_Util_Model_Observer extends Varien_Event_Observer {
  
  public function __construct() {}
  
  /**
   * Setup Elastic APM.
   * 
   * Fired from controller_action_layout_load_before.
   *
   * @param  \Varien_Event_Observer $observer
   * @return void
   */
  public function configureElasticApm(\Varien_Event_Observer $observer): void {
    if(!self::isElasticApmAvailable()) {
      return;
    }
    
    // Careful execution with try block:
    try {
      $transaction = ElasticApm::getCurrentTransaction();
      if(is_object($transaction)) {
        
        // Set transaction.name to HTTP method + the name of the OpenMage action
        if($transaction_name = self::getApmTransactionName()) {
          $transaction->setName($transaction_name);
        }
        
        // Set transaction.type to segregate the various request types
        if($transaction_type = self::getApmTransactionType()) {
          $transaction->setType($transaction_type);
        }
        
        $transaction->context()->setLabel("store_id", Mage::app()->getStore()->getId());
        $transaction->context()->setLabel("sapi", PHP_SAPI);
      } else {
        Mage::log("Failed to get current Elastic APM transaction, cannot initialize APM", Zend_Log::NOTICE, "system.log", true);
      }
    } catch(Exception $e) {
      Mage::logException($e);
    }
    
    return;
  }
  
  /**
   * Check if Elastic APM is loaded.
   *
   * @param  string|null $transaction_name
   * @return boolean
   */
  public static function isElasticApmAvailable(?string $transaction_name = null): bool {
    if(is_null($transaction_name)) {
      $transaction_name = "no_transaction_name";
    }
    
    if(!extension_loaded("elastic_apm")) {
      Mage::log("Elastic APM extension not loaded [{$transaction_name}]: ".implode(", ", get_loaded_extensions()), Zend_Log::NOTICE, "system.log", true);
      return false;
    }
    
    // After clearing opcache, there are a few requests that strangely make it past extension_loaded() but still don't have Elastic APM available, adding another check:
    if(!class_exists(ElasticApm::class)) {
      Mage::log("Elastic APM extension loaded but class does not exist yet [{$transaction_name}]", Zend_Log::NOTICE, "system.log", true);
      return false;
    }
    
    return true;
  }
  
  /**
   * Get the name used for the APM root transaction, failing silently if not possible at this time.
   *
   * @return string|null
   */
  public static function getApmTransactionName(): ?string {
    return rescue(fn() => Helper::loadOmHelperDhhUtil()->get_apm_transaction_name(), null);
  }
  
  /**
   * Try to detect admin and set a different service_name.
   *
   * @return string|null
   */
  public static function getApmTransactionType(): ?string {
    return rescue(function(): string {
      if(PHP_SAPI === "cli") {
        return "cli";
      }
      
      if(Mage::app()->getStore()->isAdmin() || Mage::getDesign()->getArea() === "adminhtml") {
        return "admin";
      }
      
      return "frontend";
    }, null);
  }
  
  /**
   * Lock some attributes from editing.
   *
   * @param  \Varien_Event_Observer $observer
   * @return void
   */
  public function updateProductOnEdit(\Varien_Event_Observer $observer) {
    $event = $observer->getEvent();
    $product = $event->getProduct();
    $product->lockAttribute("recommended_product");
    $product->lockAttribute("additional_attributes");
    $product->lockAttribute("automation_flags_json");
    $product->lockAttribute("amazon_id");
    $product->lockAttribute("last_auto_stock");
    $product->lockAttribute("supplier_description");
    $product->lockAttribute("last_stock_info_date");
    $product->lockAttribute("stock_status");
    $product->lockAttribute("popularity");
    $product->lockAttribute("bol_category_current");
    $product->lockAttribute("external_documents_json");
    $product->lockAttribute("supplier_usps_json");
    $product->lockAttribute("supplier_name");
    $product->lockAttribute("supplier_subdescription");
  }
  
  // Also used directly in resave_all_products.php
  public static function updateProductBeforeSave($observer_or_product) {
    if($observer_or_product::class === "Varien_Event_Observer") {
      if(defined("MAGE_SKIP_DHH_PRODUCT_OBSERVER_EVENTS") && constant("MAGE_SKIP_DHH_PRODUCT_OBSERVER_EVENTS")) {
        return;
      }
      $product = $observer_or_product->getProduct();
      $return = false;
    } else {
      $return = true;
      $product = $observer_or_product;
    }

    // SHORT NAME -- 2023-10-10 Disabling, better done from intel
    // if(strlen($product->getData("name_short")) < 3) {
      // $new_value = $product->getAttributeText("supplier")." ".$product->getData("sku_seller");
      // $product->setData("name_short", $new_value);
      // if(!$return) Mage::getSingleton('core/session')->addSuccess("Auto-filled name_short");
    // }

    /* END OF LIFE */
    if($product->getData("eol") === "2075") {
      if(!empty($product->getData("tagline"))) {
        $product->setData("tagline", null);
        if(!$return) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Tagline removed");
      }
      if($product->getData("featured") === "1") {
        $product->setData("featured", "0");
        if(!$return) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Featured flag removed");
      }
      if($product->getData("bargain") === "1") {
        $product->setData("bargain", "0");
        if(!$return) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Bargain flag removed");
      }
      if(!empty($product->getData("txtstockdate"))) {
        $product->setData("txtstockdate", null);
        if(!$return) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Back in stock date removed");
      }
      if(!empty($product->getData("product_label"))) {
        $product->setData("product_label", null);
        if(!$return) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Product label removed");
      }

      // 2023-10-10 Disabling to see if this is the cause of the visibility bug
      // if((int) $product->getVisibility() === 4 || (int) $product->getVisibility() === 2) {
        // $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH);
        // if(!$return) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Visibility set to Search only");
      // }
    }

    /* PRICING */

    // Disabled, intel is better for this

    // // Fill cost from msrp and price_supplier_discount_perc
    // if(empty($product->getData("msrp")) === false) {
      // if(empty($product->getData("price_supplier_discount_perc")) === false) {
        // $new_value = (float) round($product->getData("msrp") * (1 - ($product->getData("price_supplier_discount_perc") / 100)), 2);
        // if($new_value > 0) {
          // $current_value = (double) $product->getData("cost");
          // if($current_value !== $new_value) {
            // $product->setData("cost", $new_value);
            // if(!$return) Mage::getSingleton('core/session')->addSuccess("Cost Price overwritten");
          // }
        // }
      // }

      // // Fill price_min from msrp and price_supplier_msrp_disc_limit
      // if(empty($product->getData("price_supplier_msrp_disc_limit")) === false) {
        // $new_value = (double) round($product->getData("msrp") * (1 - ($product->getData("price_supplier_msrp_disc_limit") / 100)), 2);
        // if($new_value > 0) {
          // $current_value = (double) $product->getData("price_min");
          // if($current_value !== $new_value) {
            // $product->setData("price_min", $new_value);
            // if(!$return) Mage::getSingleton("core/session")->addSuccess("price_min overwritten");
          // }
        // }
      // }
    // }


    // if(empty($product->getData("price_supplier_msrp_disc_limit")) === true) {
      // // Clear the value if price_supplier_msrp_disc_limit is empty
      // if(empty($product->getData("price_min")) === false) {
        // $product->setData("price_min", null);
        // if(!$return) Mage::getSingleton("core/session")->addSuccess("price_min emptied");
      // }
    // }

    // if(empty($product->getData("price_bol_be_auto")) === false && $product->getData("price_bol_be_auto") === "1") {
      // $new_value = (float) $product->getData("special_price") * 1.21;
      // $new_value = round($new_value, 0);
      // $new_value = (string) $new_value;
      // if($new_value > 0 && $new_value != $product->getData("price_bol_be")) {
        // $product->setData("price_bol_be", $new_value);
        // if(!$return) Mage::getSingleton('core/session')->addSuccess("price_bol_be auto-filled");
      // }
    // }

    // if(empty($product->getData("price_bol_nl_auto")) === false && $product->getData("price_bol_nl_auto") === "1") {
      // $new_value = (float) $product->getData("special_price") * 1.21;
      // $new_value = round($new_value, 0);
      // $new_value = (string) $new_value;
      // if($new_value > 0 && $new_value != $product->getData("price_bol_nl")) {
        // $product->setData("price_bol_nl", $new_value);
        // if(!$return) Mage::getSingleton('core/session')->addSuccess("price_bol_nl auto-filled");
      // }
    // }

    // if(empty($product->getData("cost")) === false && $product->getData("cost") > 0) {
      // $our_price = (float) $product->getData("price");
      // if(empty($product->getData("special_price")) === false) {
        // $our_price = (float) $product->getData("special_price");
      // }

      // if($our_price > 0) {
        // // Gross Margin EUR
        // $new_value = (float) number_format($our_price - $product->getData("cost"), 4, null, "");
        // if($new_value > 0 && $new_value != (float) $product->getData("gross_margin_euro")) {
          // $product->setData("gross_margin_euro", $new_value);
          // if(!$return) Mage::getSingleton('core/session')->addSuccess("gross_margin_euro auto-filled");
        // }

        // // Gross Margin PERC
        // $new_value = (float) number_format(((($our_price - $product->getData("cost")) / $our_price) * 100), 4, null, "");
        // if($new_value > 0 && $new_value != (float) $product->getData("gross_margin_perc")) {
          // $product->setData("gross_margin_perc", $new_value);
          // if(!$return) Mage::getSingleton('core/session')->addSuccess("gross_margin_perc auto-filled");
        // }
      // }
    // }

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
      $vermogen    = empty($product->getData("vermogen"))    ? 0 : (double) $product->getData("vermogen");
      $vermogen_kw = empty($product->getData("vermogen_kw")) ? 0 : (double) $product->getData("vermogen_kw");
      $new_value   = ($vermogen + $vermogen_kw) * 1000;

      if($new_value > 0 && $new_value != $product->getData("total_power_watt")) {
        $product->setData("total_power_watt", $new_value);
        if(!$return) Mage::getSingleton('core/session')->addSuccess("total_power_watt auto-filled");
      }
    }

    /* Backfill EAN13 from EAN if possible */
    if(!empty($product->getData("ean")) && strlen((string) $product->getData("ean")) < 13) {
      $new_value = sprintf('%013d', $product->getData("ean"));
      $product->setData("ean", $new_value);
      if(!$return) Mage::getSingleton('core/session')->addSuccess("ean zerofilled");
    }

    if($return) {
      return $product;
    }

    /* NOTHING BELOW THIS */
  }
  
  // Also used directly in resave_all_products.php
  public static function updateProductAfterSave($observer_or_product) {
    
    if($observer_or_product::class === "Varien_Event_Observer") {
      if(defined("MAGE_SKIP_DHH_PRODUCT_OBSERVER_EVENTS") && constant("MAGE_SKIP_DHH_PRODUCT_OBSERVER_EVENTS") === true) {
        return;
      }
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
            if(!$return) Mage::getSingleton('core/session')->addSuccess("Product is EOL: Stock data altered");
          }
        } catch (Excpetion $e) {
          if(!$return) Mage::getSingleton('core/session')->addError("Failed to apply EOL business rules on stock item: {$e->getMessage()}");
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
            if(!$return) Mage::getSingleton('core/session')->addSuccess("Stock is unmanaged: Setting defaults.");
          }
        } catch (Excpetion $e) {
          if(!$return) Mage::getSingleton('core/session')->addError("Failed to apply 'unmanaged' business rules on stock item: {$e->getMessage()}");
        }
      }      
    } elseif($stockItem->getManageStock() === "1" && $stockItem->getData('use_config_manage_stock') === "0") {
      if($stockItem->getData('qty') > 0 && $stockItem->getData('is_in_stock') === "0") {
        $stockItem->setData('is_in_stock', 1);
        try {
          if($stockItem->save()) {
            if(!$return) Mage::getSingleton('core/session')->addSuccess("Product is back in stock: Setting in_stock to Yes.");
          }
        } catch (Excpetion $e) {
          if(!$return) Mage::getSingleton('core/session')->addError("Failed to update product stock status: {$e->getMessage()}");
        }
      }
    }
    
    if($return) {
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
  
  public function logClick():void {
    global $dhh_click_log;
    
    $action     = Mage::app()->getFrontController()->getAction()->getFullActionName();
    $full_url   = Mage::helper("core/url")->getCurrentUrl();
    $url        = Mage::getSingleton("core/url")->parseUrl($full_url);
    $path       = ltrim((string) $url->getPath(), "/");
    $query      = ltrim((string) $url->getQuery(), "?");
    $entity_id  = null;
    
    // current_* is fastest, but in case of an FPC HIT we cannot use them
    if(isset($dhh_click_log["label"]["fpc_cache"]) && $dhh_click_log["label"]["fpc_cache"] !== "HIT") {
      switch($action) {
        case "catalog_product_view":
          $entity_id = Mage::registry('current_product')->getId();
          break;
        case "catalog_category_view":
          $entity_id = Mage::registry('current_category')->getId();
          break;
      }
    } else {
      $oRewrite = Mage::getModel('core/url_rewrite')->setStoreId(Mage::app()->getStore()->getId())->loadByRequestPath($path);
      switch($action) {
        case "catalog_product_view":
          $entity_id = $oRewrite->getProductId();
          break;
        case "catalog_category_view":
          $entity_id = $oRewrite->getCategoryId();
          break;
      }
    }
    
    if(is_numeric($entity_id)) {
      $entity_id = intval($entity_id);
    }
    
    Mage::helper("deheerhoreca_util/util")->addToClickLog("@timestamp", date("Y-m-d\TH:i:s.uP"));
    Mage::helper("deheerhoreca_util/util")->addToClickLog("client.ip", Mage::helper("deheerhoreca_util/util")->getUserIP());
    Mage::helper("deheerhoreca_util/util")->addToClickLog("event.action", $action);
    Mage::helper("deheerhoreca_util/util")->addToClickLog("event.created", date("Y-m-d"));
    Mage::helper("deheerhoreca_util/util")->addToClickLog("event.id", $entity_id);
    Mage::helper("deheerhoreca_util/util")->addToClickLog("event.module", "mage-clicks");
    Mage::helper("deheerhoreca_util/util")->addToClickLog("event.kind", "event");
    Mage::helper("deheerhoreca_util/util")->addToClickLog("url.full", $full_url);
    Mage::helper("deheerhoreca_util/util")->addToClickLog("url.domain", "www.chefstore.nl");
    Mage::helper("deheerhoreca_util/util")->addToClickLog("url.path", $path);
    if($query !== "") {
      Mage::helper("deheerhoreca_util/util")->addToClickLog("url.query", $query);
    }
    Mage::helper("deheerhoreca_util/util")->addToClickLog("host.name", gethostname());
    Mage::helper("deheerhoreca_util/util")->addToClickLog("ecs.version", "1.11.0");
    
    try {
      $json = json_encode($dhh_click_log);
      file_put_contents("./var/log/clicks.jsonl", $json.PHP_EOL, FILE_APPEND);
    } catch(Exception $e) {
      Mage::logException($e);
    }
  }
  
  // Called during placement of an order, to clear tm_field fields
  // These fields need to be cleared during reorder (admin only)
  public function beforeOrderPlace(Varien_Event_Observer $observer): void {
    // - tm_field1:  supplier.order_id
    // - tm_field2:  shipment.expected_date
    // - tm_field3:  shipment.forwarder
    // - tm_field4:  supplier.name
    // - tm_field5:  process.flags
    // - tm_field6:  marketplace.order_id
    // - tm_field7:  marketplace.name
    // - tm_field8:  shipment.id
    // - tm_field9:  supplier.packing_slip_id
    // - tm_field10: B2B/B2C
    // - tm_field11: TBD
    // - tm_field12: TBD
    // - tm_field13: TBD
    // - tm_field14: TBD
    // - tm_field15: TBD
    $_order = $observer->getEvent()->getOrder();
    $_order->setData("tm_field1", null);
    $_order->setData("tm_field2", null);
    $_order->setData("tm_field3", null);
    $_order->setData("tm_field4", null);
    $_order->setData("tm_field5", null);
    $_order->setData("tm_field6", null);
    $_order->setData("tm_field7", null);
    $_order->setData("tm_field8", null);
    $_order->setData("tm_field9", null);
    // $_order->setData("tm_field10", null); // Not this one
    $_order->setData("tm_field11", null);
    $_order->setData("tm_field12", null);
    $_order->setData("tm_field13", null);
    $_order->setData("tm_field14", null);
    $_order->setData("tm_field15", null);
  }
  
  /**
   * event observer called after emails are sent
   */
  public function emailSendAfter(Varien_Event_Observer $observer) {
    // 2024-01-21 Starting with 1 second sleep and monitoring for Gmail limit errors
    $n = 1;
    sleep($n);
    Mage::log("Slept for {$n} seconds after sending email", null, "system.log", true);
  }
  
  public function alterGa4Data(Varien_Event_Observer &$observer) {
    // if(_dhh_debug()) {
      // dump($observer);
      // $ga4Event = $observer->getEvent()->getGa4DataTransport()->getData()[0] ?? false;

      // if($varien_event = $observer->getEvent()) {
        // dump($varien_event);
        // if($ga4DataTransport = $varien_event->getData("ga4_data_transport")) {
          // dump($ga4DataTransport);

        // }
        // $event_name = $ga4Event[0] ?? null;
        // dump($event_name);
        // $event_data = $ga4Event[1] ?? null;
        // dump($event_data);

        // if($event_name !== "purchase") {
          // return;
        // }
      // }

      // if(Mage::getSingleton('customer/session')->isLoggedIn()) {
        // if($customer = Mage::getSingleton('customer/session')->getCustomer()) {
          // $email = $customer->getEmail();
          // $email = trim($email);
          // $email = strtolower($email);

          // $userData     = [
            // "email"       => $email,
          // ];

          // $result[] = ['set', 'user_data', $userData];
        // }
      // }

      // dump($ga4DataTransport->getData());
    // }
  }
}
