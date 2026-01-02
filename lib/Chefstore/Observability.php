<?php

declare(strict_types=1);

namespace Chefstore;

use \Mage;
use \Varien_Event_Observer;
use \Exception;
use \Zend_Log;
use \Chefstore\Utils;
use \Elastic\Apm\ElasticApm;
use \Elastic\Apm\TransactionInterface;
use \Chefstore\Helper;
use \Chefstore\Html;
use \Illuminate\Support\Arr;
use \Illuminate\Support\Str;

class Observability {
  
  /**
   * Check if Elastic APM is loaded to prevent fatal errors later.
   *
   * Observes:
   * - core_abstract_load_after (frontend)
   * - controller_front_init_before (adminhtml)
   *
   * @param  string|null $transaction_name
   * @return boolean
   */
  public static function isElasticApmAvailable(?string $transaction_name = null): bool {
    $transaction_name ??= "no_transaction_name";
    
    // Indicates PHP misconfiguration
    if(!extension_loaded("elastic_apm")) {
      Mage::log("Elastic APM extension not loaded [{$transaction_name}]: ".implode(", ", get_loaded_extensions()), Zend_Log::NOTICE);
      return false;
    }
    
    // After clearing opcache, there are a few requests that strangely make it past extension_loaded() but still don't have Elastic APM available, adding another check:
    if(!class_exists(ElasticApm::class)) {
      Mage::log("Elastic APM extension loaded but class does not exist yet [{$transaction_name}]", Zend_Log::INFO);
      return false;
    }
    
    return true;
  }
  
  /**
   * Get the name used for the APM root transaction, failing silently if not possible at this time.
   *
   * @return string
   */
  public static function getApmTransactionName(): string {
    return (string) trim(implode(" ", [
      $_SERVER["REQUEST_METHOD"] ?? "",
      Mage::app()?->getFrontController()?->getAction()?->getFullActionName() ?? "UNKNOWN_ACTION",
    ]));
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
      
      if(Mage::app()?->getStore()?->isAdmin()
        || Mage::getDesign()?->getArea() === "adminhtml"
        || str_contains(getOmDhhUtilHelper()->getCurrentUrl(), "/admin4JN0/")) {
        return "adminhtml";
      }
      
      return "frontend";
    }, null);
  }
}
