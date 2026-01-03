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
  
  /** @var int|float|null */
  static $startedAt = null;
  
  /**
   * Starts measuring time. Only one benchmark can be active at a time if internal storage is used.
   *
   * @param  bool  $store  Whether to store the start time internally (default: TRUE). If FALSE, the current hrtime() is returned.
   * @return null|float|int
   */
  public static function start(bool $store = true): null|float|int {
    if(!$store) {
      return hrtime(true);
    }
    if(self::$startedAt !== null) {
      Mage::log("Benchmark had already started (".self::$startedAt."), and only one benchmark can be active at a time.");
    } else {
      self::$startedAt = hrtime(true);
    }
    return null;
  }
  
  /**
   * Stops measuring time and returns the elapsed time in formatted milliseconds since startTimer() was called, or null if startTimer() was not called.
   * Renamed from stopTimer() to avoid conflicts with Intel.
   *
   * @param  bool              $formatted  Whether to return formatted string (default: TRUE)
   * @param  float|int|null    $start      Optional start time to calculate elapsed time from. If null, uses the internally stored start time.
   *
   * @return string|float|null
   */
  public static function stop(bool $formatted = true, float|int|null $start = null): string|float|null {
    if($start !== null) {
      $tookNs = hrtime(true) - $start;
    } else {
      if(self::$startedAt === null) {
        return null;
      }
      $tookNs = hrtime(true) - self::$startedAt;
      self::$startedAt = null;
    }
    
    return $formatted ? self::formatMillis($tookNs / 1_000_000) : ($tookNs / 1_000_000);
  }
  
  /**
   * Format milliseconds into human readable string with variable-length decimal places.
   *
   * @param  int|float|null  $milliseconds  Milliseconds to format. Must be non-negative.
   * @param  string          $format        Not used
   *
   * @return string|null     Human-readable formatted milliseconds with variable-length decimal places.
   */
  public static function formatMillis(int|float|null $milliseconds, string $format = "H:i:s.u"): ?string {
    if($milliseconds === null) {
      return null;
    }
    
    $milliseconds = max(0, $milliseconds);
    $digits = match(true) {
      $milliseconds < 1     => 2,
      $milliseconds < 10    => 1,
      $milliseconds < 100   => 0,
      $milliseconds < 1000  => 0,
      default               => 0,
    };
    
    return number_format($milliseconds, $digits, ".", ",")." ms";
  }
  
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
