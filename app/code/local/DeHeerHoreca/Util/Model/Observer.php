<?php

// declare(strict_types=1); // @todo Not ready for this yet

use \Chefstore\Utils;
use \Elastic\Apm\ElasticApm;
use \Elastic\Apm\TransactionInterface;
use \Chefstore\Helper;
use \Chefstore\Html;
use \Chefstore\Observability;
use \Illuminate\Support\Arr;
use \Illuminate\Support\Str;

class DeHeerHoreca_Util_Model_Observer extends Varien_Event_Observer {
  
  /**
   * Setup Elastic APM.
   * 
   * Observes: controller_action_layout_load_before.
   *
   * @param  \Varien_Event_Observer $observer
   * @return void
   */
  public static function configureElasticApm(\Varien_Event_Observer $observer): void {
    static $initialized = false;
    if($initialized) {
      return;
    }
    // devLog(__METHOD__);
    if(!Observability::isElasticApmAvailable()) {
      devLog("Elastic APM not available");
      return;
    }
    
    // Careful execution with try block:
    try {
      /** @var TransactionInterface */
      $transaction = ElasticApm::getCurrentTransaction();
      if(is_object($transaction)) {
        
        // Set transaction.name to HTTP method + the name of the OpenMage action
        if($transaction_name = Observability::getApmTransactionName()) {
          $transaction->setName($transaction_name);
          // devLog("Set APM transaction name to '{$transaction_name}'");
        }
        
        // Set transaction.type to segregate the various request types
        if($transaction_type = Observability::getApmTransactionType()) {
          $transaction->setType($transaction_type);
          // devLog("Set APM transaction type to '{$transaction_type}'");
        }
        
        $transaction->context()->setLabel("store_id", Mage::app()->getStore()->getId());
        $transaction->context()->setLabel("sapi", PHP_SAPI);
        
        $initialized = true;
        $currentUrl = getOmDhhUtilHelper()->getCurrentUrl();
        // devLog("Elastic APM initialized: {$currentUrl}");
      } else {
        Mage::log("Failed to get current Elastic APM transaction, cannot initialize APM", Zend_Log::NOTICE);
        // devLog("Failed to get current Elastic APM transaction");
        return;
      }
    } catch(Exception $e) {
      Mage::logException($e);
      Mage::log("Failed to init Elastic APM: {$e->getMessage()}", Zend_Log::NOTICE);
      // devLog("Failed to init Elastic APM: {$e->getMessage()}");
      return;
    }
    
    return;
  }
  
  /**
   * Lock (disable) some attributes when editing a Product.
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
    $product->lockAttribute("news_from_date");
    $product->lockAttribute("is_recurring");
    
    // Because we disable custom design on product view pages (at least we killed the cache tag explosion), we should not use custom design at all:
    $product->lockAttribute("page_layout");
    $product->lockAttribute("custom_design");
    $product->lockAttribute("custom_design_from");
    $product->lockAttribute("custom_design_to");
    $product->lockAttribute("custom_layout_update");
    $product->lockAttribute("custom_apply_to_products");
    $product->lockAttribute("options_container");
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
    
    return;
  }
  
  /**
   * Log clicks to a JSONL file, while blocking some known bots.
   * Observes: core_app_run_after.
   *
   * @return void
   */
  public function logClick(): void {
    getOmDhhUtilHelper()->logClick();
  }
  
  /**
   * Log and profile all SQL queries if the profiler is enabled.
   * Observes: core_app_run_after.
   *
   * @return void
   */
  public function profileSqlQueries(): void {
    getOmDhhUtilHelper()->profileSqlQueries();
  }
  
  /**
   * Called during placement of an order, to clear tm_field fields
   * These fields need to be cleared during reorder (admin only)
   * Observes: sales_order_place_before
   *
   * @param  Varien_Event_Observer  $observer
   * @return void
   */
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
   * Sleep for 1 second after sending an email to avoid overwhelming the SMTP server.
   * Observes: email_send_after.
   * 
   * Data:
   * [
   *   'to'         => $this->getToEmail(),
   *   'subject'    => $this->getSubject(),
   *   'email_body' => $this->getBody()
   * ]
   *
   * @param  Varien_Event_Observer  $observer
   * @return void
   */
  public function emailSendAfter(Varien_Event_Observer $observer): void {
    sleep(1);
    Mage::log("Slept for 1 seconds after sending email");
  }
  
  /**
   * Place to alter GA4 data before writing to JS.
   * Observes: googleanalytics_ga4_send_data_before
   * 
   * Data:
   * ['ga4_data_transport' => $ga4DataTransport]
   *
   * @param  Varien_Event_Observer  $observer
   * @return void
   */
  public function alterGa4Data(Varien_Event_Observer $observer): void {
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
  
  /**
   * Change some product flat attribute column types to avoid issues with the maximum 
   * 
   * Observes catalog_product_flat_prepare_columns.
   *
   * @param  Varien_Event_Observer $observer
   */
  public static function alterProductFlatCols(Varien_Event_Observer &$observer): void {
    /** @var Varien_Db_Ddl_Table $columnsObject */
    $columnsObject  = $observer->getEvent()->getColumns();
    $columns        = $columnsObject->getColumns();
    ksort($columns);
    $columns        = Arr::mapWithKeys($columns,
      function($config, $attr_code): array {
        $config = Arr::prepend($config, null, "orig_type");
        $config = Arr::prepend($config, $attr_code, "attr_code");
        
        // Reduce Ja/Nee/N.v.t./Optioneel/NULL and other short values to a small varchar
        // => VARCHAR(255) => VARCHAR(16)
        if(in_array($attr_code, [
          "afsluitbaar_value",
          "aftap_value",
          "afvalgat_value",
          "bestelartikel_value",
          "collapsible_value",
          "disposable_value",
          "door_heating_value",
          "ean",
          "ean13",
          "eol_value",
          "eu_eco_label_value",
          "freezer_safe_value",
          "has_adjustable_shelves_value",
          "has_induction_value",
          "has_passthrough_value",
          "is_food_contact_safe_value",
          "is_roll_in_value",
          "is_tiltable_value",
          "motor_value",
          "opstaande_rand_value",
          "recommended_product_value",
          "self_closing_value",
          "verrijdbaar_value",
          "winter_control_value",
          "with_glass_top_value",
          "with_heating_value",
          "zeepdoseerpomp_value",
          "deur_omkeerbaar_value",
          "made_in_country_2code_value",
        ])) {
          $config["orig_type"] = $config["type"];
          if($config["type"] === "varchar(255)") {
            $config["type"] = "varchar(16)";
            // printr("{$attr_code} {$config["orig_type"]} => {$config["type"]}");
          }
        }
        if(in_array($attr_code, [
          "ip_rating_value",
          "energieklasse_value",
          "tableware_type_value",
          "energy_efficiency_class_a_g_value",
          "type_pizza_oven_value",
          "table_type_value",
          "chair_type_value",
          "bottom_shape_value",
          "stock_status_value",
          "cooling_motor_type_value",
          "icecube_type_value",
          "mpn",
          "levertijd_tmp_override_value",
          "worktable_construction_value",
          "type_toaster_value",
          "type_bain_marie_value",
          "sku_seller",
          "tray_material_value",
          "grill_tray_type_value",
          "supplier_value",
          "levertijd_value",
          "manufacturer_value",
          "garantie_value",
          "thumbnail_label",
          "image_label",
          "small_image_label",
          "msrp_display_actual_price_type",
        ])) {
          $config["orig_type"] = $config["type"];
          if($config["type"] === "varchar(255)") {
            $config["type"] = "varchar(64)";
            // printr("{$attr_code} {$config["orig_type"]} => {$config["type"]}");
          }
        }
        
        // TEXT for multiselects is overkill, use a smaller VARCHAR(128)
        // => TEXT => VARCHAR(128)
        elseif(in_array($attr_code, [
          "aisi_standard",
          "brand_series",
          "climate_class",
          "colors",
          "cooking_surface_shape",
          "door_hinge_side",
          "door_lid_material",
          "frequency_hertz",
          "gas_type_convertible_to",
          "gender",
          "capacity",
          "gn_capacity",
          "gn_options",
          "gn",
          "good_use_cases",
          "highlighted_company_types",
          "indoor_outdoor",
          "is_compliant_with",
          "knife_type",
          "material_group",
          "model",
          "pan_type",
          "power_mains",
          "product_type",
          "promos",
          "recurring_profile",
          "size",
          "temperature_class",
          "type_gas",
          "type_induction_unit",
          "type_koeling",
          "type_werkblad",
          "uitvoering_werktafel",
          "uitvoering",
          "voltage",
          "with_canopy",
        ])) {
          $config["orig_type"] = $config["type"];
          if($config["type"] === "text") {
            $config["type"] = "varchar(96)";
            // printr("{$attr_code} {$config["orig_type"]} => {$config["type"]}");
          }
        }
        
        // Print some uncaught attributes which might need attention
        elseif(
          Str::is("has_*_value", $attr_code) ||
          Str::is("with_*_value", $attr_code) ||
          Str::is("is_*_value", $attr_code) ||
          Str::is("*_safe*value", $attr_code) ||
          Str::is("*_value", $attr_code) ||
          $config["type"] === "text"
        ) {
          // printr("{$attr_code} {$config["type"]} unchanged");
        } elseif($config["type"] === "varchar(255)") {
          // printr("{$attr_code} {$config["type"]} unchanged");
        } else {
          // printr("{$attr_code} {$config["type"]} unchanged");
        }
        
        return [$attr_code => $config];
      }
    );
    
    // echo array_to_table($columns, true);
    $columns = Arr::mapWithKeys($columns, 
      function($config, $attr_code): array {
        unset($config["attr_code"]);
        unset($config["orig_type"]);
        return [$attr_code => $config];
      }
    );
    
    $columnsObject->setColumns($columns);
  }
  
  /**
   * Observes: http_response_send_before
   *
   * @param  Varien_Event_Observer $observer
   */
  public function minifyAjaxResponse(Varien_Event_Observer $observer): void {
    return; // Disabled for now -- Minifying is too expensive unless saved to cache (also needs deferred)
    
    if(Mage::app()->getRequest()->isAjax() !== true) {
      return;
    }
    
    if(!isDevIp()) {
      return;
    }
    
    /** @var Mage_Core_Controller_Response_Http */
    $response = $observer->getResponse();
    $body = $response->getBody();
    if(is_string($body)) {
      if(true || json_validate($body)) { // Disabled validation for speed
        try {
          $data = json_decode($body, true);
        } catch(Throwable $e) {
          Mage::logException($e);
          devLog("Failed to decode AJAX response body JSON: ".$e->getMessage());
          return;
        }
        if(is_array($data)) {
          $changed = false;
          $data = Arr::map($data, function($partContent, $partName) use (&$changed): mixed {
            $knownHtmlParts = [
              // Seen in amshopby AJAX responses
              // "page",      // Disabled for now -- Minifying is too expensive (~50 ms for a amasty shopby response for 10% reduction)
              // "blocks",    // Seen in amshopby AJAX responses; Content is another level deeper, @todo
              
              // Seen in adminhtml mage core
              "content",      // Minifying is expensive (~250 ms for a adminhtml categories tree response for 50% reduction)
            ];
            if(in_array($partName, $knownHtmlParts, true) && is_string($partContent) && Html::containsHtml($partContent)) {
              // $tmpFile = sys_get_temp_dir()."/dhh_util_minify_ajax_{$partName}_before.html";
              // file_put_contents($tmpFile, $partContent);
              // devLog("Wrote to {$tmpFile}");
              $origPartContent = $partContent;
              try {
                // $start = omStartTimer();
                $partContent = Html::minifyHtml($partContent);
                // $took = omStopTimer();
                // devLog("Minified AJAX response part '{$partName}': ".(strlen($origPartContent))." -> ".(strlen($partContent))." bytes in {$took}");
                // $tmpFile = sys_get_temp_dir()."/dhh_util_minify_ajax_{$partName}_after.html";
                // file_put_contents($tmpFile, $partContent);
                // devLog("Wrote to {$tmpFile}");
                $changed = true;
                return $partContent;
              } catch(Throwable $e) {
                Mage::logException($e);
                devLog("Failed to minify AJAX response part '{$partName}': ".$e->getMessage());
              }
              return $origPartContent;
            }
            return $partContent;  // Don't fail, don't change response
          });
          if($changed) {
            $body = json_encode($data);
            // devLog(var_export($body, true));
            devLog("AJAX Response body JSON minified");
            $response->setBody($body);
          } else {
            devLog("AJAX Response body JSON not changed");
          }
        } else {
          devLog("AJAX Response body JSON is not an array");
        }
      } else {
        devLog("AJAX Response body is not valid JSON");
      }
    } else {
      devLog("AJAX Response body is not a string");
    }
  }
  
  /**
   * Observes: core_app_run_after.
   *
   * @param  Varien_Event_Observer  $observer
   * @return void
   */
  public static function coreAppRunAfter(Varien_Event_Observer $observer): void {
    // Mage::log("core_app_run_after fired", null, "system.log", true);
    Utils::runDeferredClosures();
    // Mage::log("core_app_run_after done", null, "system.log", true);
  }
}
