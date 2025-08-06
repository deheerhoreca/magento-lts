<?php

/**
 * Observer class for logging repeated model loads in Magento.
 *
 * This class tracks instances where models are loaded multiple times during a request,
 * recording the class name, identifier, and the file/line locations of each load.
 * At destruction, it writes a summary of repeated loads to the configured log file,
 * omitting models loaded only once.
 *
 * @property array<string, array<string, array<int, string>>> $data Stores model load information as [class][id][] = location.
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
    $location = $trace[5]['file'] ?? 'unknown';
    $location .= isset($trace[5]['line']) ? ':' . $trace[5]['line'] : '';

    $this->data[$class][$id][] = $location;
    $this->loadedModels++;
  }

  /**
   * Processes and writes repeated load data to the log file.
   *
   * @return void
   */
  public function __destruct() {
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
    $summary = "Repeated model loads:\n";
    foreach ($this->data as $className => $ids) {
      $summary .= "$className:\n";
      foreach ($ids as $id => $locations) {
        $summary .= "  ID: $id, Count: " . count($locations) . ", Locations: " . implode(', ', $locations) . "\n";
      }
    }
    
    $currentUrl = Mage::helper('core/url')->getCurrentUrl();
    
    Mage::log(str_pad(" {$currentUrl} ", 120, "-", STR_PAD_BOTH), null, $logFile);
    Mage::log($summary, null, $logFile);
    Mage::log('Total number of loaded models: ' . $this->loadedModels, null, $logFile);
    Mage::log(var_export($this->data, true), null, $logFile);
    Mage::log('Total number of loaded models: ' . $this->loadedModels, null, $logFile);
    Mage::log(str_repeat("-", 120), null, $logFile);
  }
}
