<?php

declare(strict_types=1);

// DHH extension of Mage_Catalog_Model_Resource_Product_Collection
// To enable FLAT tables in ADMIN when needed
// See: app/code/core/Mage/Catalog/Model/Resource/Product/Collection.php

class DeHeerHoreca_Util_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection {
  
  protected $_flatEnabled_override = [];
  
  // DHH: Overrides FlatEnabled per store
  public function hardcodeFlatEnabled($storeId, ?bool $enabled) {
    $this->_flatEnabled[$storeId] = $enabled;
  }
  
  /**
   * @return bool
   */
  public function isEnabledFlat() {
    $storeId = $this->getStoreId();
    
    if(!isset($this->_flatEnabled[$storeId])) {
      $flatHelper = $this->getFlatHelper();
      $this->_flatEnabled[$storeId] = $flatHelper->isAvailable() && $flatHelper->isBuilt($storeId);
    }
    
    return $this->_flatEnabled[$storeId];
  }
}
