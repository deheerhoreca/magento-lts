<?php

declare(strict_types=1);

require_once __DIR__."/abstract.php";

class OM_Shell extends Mage_Shell_Abstract {
  
  /**
   * Initialize application with code (store, website code)
   *
   * @var string
   */
  protected $_appCode = 'admin';

  /**
   * Initialize application code type (store, website, store_group)
   *
   * @var string
   */
  protected $_appType = 'store';
  
  /**
   * Initialize application and parse input parameters.
   * Replace parent constructor to allow passing appCode and appType.
   */
  private function __construct(string|int|null $appCode = null, string|int|null $appType = null) {
    if($appCode !== null) {
      $this->_appCode = (string)$appCode;
    }
    if($appType !== null) {
      $this->_appType = (string)$appType;
    }
    
    if($this->_includeMage) {
      require_once $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
      Mage::app($this->_appCode, $this->_appType);
    }
    
    $this->_factory = new Mage_Core_Model_Factory();
    $this->_applyPhpVariables();
    $this->_parseArgs();
    $this->_construct();
    $this->_validate();
    $this->_showHelp();
    
    return true;
  }
  
  /**
   * Close actions for OpenMage app.
   *
   * @return bool
   */
  public function __destruct() {
    if(!om_is_init()) {
      return false;
    }
    
    if(LOGQUERIES) {
      _m1_select("SET SESSION general_log=0");
      logger("Disabled general_log for MariaDB", "VERBOSE");
    }
    
    // @todo:
    // restore_error_handler();
    // restore_exception_handler();
    // ini_set("display_errors", "1");
  }
  
  /**
   * Basic run function required for the abstract.
   *
   * @return bool
   */
  public function run(): bool {
    logger("OpenMage Shell ready", "VERBOSE");
    return true;
  }
  
  /**
   * Get singleton instance of Mage_Shell.
   *
   * @param  string|int|null|null $appCode
   * @param  string|int|null|null $appType
   *
   * @return self
   */
  public static function getInstance(string|int|null $appCode = null, string|int|null $appType = null): self {
    static $instance = null;
    if($instance === null) {
      $instance = new self($appCode, $appType);
    }
    return $instance;
  }
}
