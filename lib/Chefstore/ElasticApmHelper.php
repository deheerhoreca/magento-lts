<?php

declare(strict_types=1);

namespace Chefstore;

use \Mage;

class ElasticApmHelper {
  
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
    } catch(\Exception $e) {
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
  
  public static function get_apm_transaction_name(): string {
    return (string) trim(implode(" ", [
      ($_SERVER["REQUEST_METHOD"] ?? ""),
      (Mage::app()->getFrontController()->getAction()->getFullActionName() ?? "UNKNOWN_ACTION"),
    ]));
  }
}
