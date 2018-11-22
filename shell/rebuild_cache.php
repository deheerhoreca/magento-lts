<?php

$types = ["block_html", "layout"];

require_once '../app/Mage.php';
umask(0);
Mage::app('default');
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

try {
  $allTypes = Mage::app()->useCache();
  foreach($allTypes as $type => $value) {
    if(in_array($type, $types)) {
      Mage::app()->getCacheInstance()->cleanType($type);
      Mage::dispatchEvent('', array('type' => $type));
      echo PHP_EOL."Rebuilt {$type}".PHP_EOL;
    }
  }
  echo 'done';
} catch (Exception $e) {
  echo $e->getMessage();
}
