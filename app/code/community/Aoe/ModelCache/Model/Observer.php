<?php

use \Chefstore\Utils;
use \Illuminate\Support\Arr;
use \Illuminate\Support\Str;

/**
 * Observer class for logging repeated model loads in Magento.
 *
 * This class tracks instances where models are loaded multiple times during a request,
 * recording the class name, identifier, and the file/line locations of each load.
 * At destruction, it writes a summary of repeated loads to the configured log file,
 * omitting models loaded only once.
 *
 * @property array<string, array<string, array<string|int, string>>> $data Stores model load information as [class][id][] = location.
 * @property int $loadedModels Counter for total loaded models.
 * @const string XML_PATH_MODEL_CACHE_ENABLED Config path to enable/disable logging.
 * @const string XML_PATH_MODEL_CACHE_LOG_FILE Config path for log file location.
 */

class Aoe_ModelCache_Model_Observer {
  /**
   * Stores model load information as [class][id][] = location.
   *
   * @var array<string, array<string, array<int, string>>>
   */
  protected array $data = [];
  
  /**
   * Counter for total loaded models.
   *
   * @var int
   */
  protected int $loadedModels = 0;
  
  /**
   * Config path to enable/disable logging.
   *
   * @var string
   */
  public const XML_PATH_MODEL_CACHE_ENABLED = 'dev/aoe_modelcache/log_active';
  
  /**
   * Config path for log file location.
   *
   * @var string
   */
  public const XML_PATH_MODEL_CACHE_LOG_FILE = 'dev/aoe_modelcache/log_file';
  
  /**
   * Records model load details if logging is enabled.
   *
   * @param Varien_Event_Observer $event
   * @return void
   */
  public function log(Varien_Event_Observer $event): void {
    $logActive = Mage::getStoreConfig(self::XML_PATH_MODEL_CACHE_ENABLED);
    if (!$logActive) {
      return;
    }
    
    $object = $event->getObject();
    $class = get_class($object);
    $id = $event->getValue();
    
    $this->data[$class][$id] ??= [];
    $trace = debug_backtrace();
    
    // The real caller is 5 levels up the stack
    $location = $trace[5]["file"] ?? "unknown";
    $location .= isset($trace[5]["line"]) ? ":" . $trace[5]["line"] : "";
    
    // DHH: Add max two more levels to get more context
    if(isset($trace[6])) {
      $location .= ", ".($trace[6]["file"] ?? "unknown");
      $location .= isset($trace[6]["line"]) ? ":" . $trace[6]["line"] : "";
    }
    if(isset($trace[7])) {
      $location .= ", ".($trace[7]["file"] ?? "unknown");
      $location .= isset($trace[7]["line"]) ? ":" . $trace[7]["line"] : "";
    }
    
    $this->data[$class][$id][] = $location;
    $this->loadedModels++;
  }
  
  /**
   * Processes and writes repeated load data to the log file.
   *
   * @return void
   */
  public function __destruct() {
    if(!dhh_profiler_enabled()) {
      return;
    }
    
    $logActive = Mage::getStoreConfig(self::XML_PATH_MODEL_CACHE_ENABLED);
    if (!$logActive) {
      return;
    }
    $logFile = Mage::getStoreConfig(self::XML_PATH_MODEL_CACHE_LOG_FILE);
    
    // remove every id that was called only once
    foreach ($this->data as $className => $classes) {
      foreach ($classes as $id => $lineAndFiles) {
        if (count($lineAndFiles) <= 1) {
          unset($this->data[$className][$id]);
          if (empty($this->data[$className])) {
            unset($this->data[$className]);
          }
        }
      }
    }
    
    if($this->data === []) {
      return;
    } else {
      $summary = "Repeated model loads:".PHP_EOL;
      $cwd = "/var/www/vhosts/chefstore.nl/httpdocs/deheerhoreca-magento/";
      foreach ($this->data as $className => $ids) {
        $summary .= "{$className}:".PHP_EOL;
        foreach ($ids as $id => $locations) {
          $locations = Arr::map($locations, fn($item) => Str::chopStart($item, $cwd));
          $summary .= "- ID: $id, Count: " . count($locations) . ", Locations:\n";
          $summary .= "  - ".implode(PHP_EOL."  - ", $locations) . PHP_EOL;
        }
      }
      
      // getCurrentUrl() is encoded...
      $currentUrl = htmlspecialchars_decode(Mage::helper("core/url")->getCurrentUrl(), ENT_COMPAT | ENT_HTML5 | ENT_HTML401);
      
      $report = [
        PHP_EOL.PHP_EOL,
        str_pad(" {$currentUrl} ", 220, "-", STR_PAD_BOTH),
        "Total number of loaded models: ".$this->loadedModels,
        $summary,
        // Utils::printr($this->data, true),
      ];
      
      $report = implode(PHP_EOL.PHP_EOL, $report);
      Mage::log($report, file: $logFile);
      
      // Mage::log(str_pad(" {$currentUrl} ", 130, "-", STR_PAD_BOTH), file: $logFile);
      // Mage::log($summary, file: $logFile);
      // Mage::log('Total number of loaded models: ' . $this->loadedModels, file: $logFile);
      // Mage::log(Utils::td($this->data, true, true), file: $logFile);
      // Mage::log(str_repeat("-", 120), file: $logFile);
    }
  }
}
