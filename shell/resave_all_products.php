<?php

shell_exec("/opt/plesk/php/7.3/bin/php indexer.php --mode-manual");

require_once 'abstract.php';

class Resave_Products extends Mage_Shell_Abstract
{
  public function run()
  {
    $collection = Mage::getModel('catalog/product')->getCollection()
      ->addAttributeToSelect('id')
      ->setPageSize(10)
      ->setCurPage(1);

    $i = 0;
    foreach($collection as $product) {
      $product = Mage::getModel('catalog/product')->load($product->getId());
      $product->setIsChanged(true);
      $product->save();
      $i++;
      echo ".";
    }
    
    echo PHP_EOL."Saved {$i} product(s)".PHP_EOL;
  }
}

$shell = new Resave_Products();
$shell->run();

shell_exec("/opt/plesk/php/7.3/bin/php indexer.php --mode-realtime");
shell_exec("/opt/plesk/php/7.3/bin/php indexer.php reindexall");
