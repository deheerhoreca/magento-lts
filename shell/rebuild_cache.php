<?php

$types = ["block_html", "layout"];

require_once '../app/Mage.php';
umask(0);
Mage::app('default');
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

try {
  $invalidated_types = Mage::app()->getCacheInstance()->getInvalidatedTypes();
  $invalidated_ids = [];
  foreach($invalidated_types as $invalidated_type) {
    $invalidated_ids[] = $invalidated_type->getData('id');
  }

  $allTypes = Mage::app()->useCache();
  foreach($allTypes as $type => $value) {
    if(in_array($type, $types) && in_array($type, $invalidated_ids)) {
      Mage::app()->getCacheInstance()->cleanType($type);
      Mage::dispatchEvent('', array('type' => $type));
      echo "Rebuilt {$type}".PHP_EOL;
    } else {
      echo "Skipped {$type}".PHP_EOL;
    }
  }
  echo 'Done'.PHP_EOL;
} catch (Exception $e) {
  echo $e->getMessage();
}
