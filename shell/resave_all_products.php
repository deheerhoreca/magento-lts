<?php

shell_exec("/opt/plesk/php/7.3/bin/php indexer.php --mode-manual");

require_once 'abstract.php';

const DEBUG = true;

class Resave_Products extends Mage_Shell_Abstract
{
  public function run()
  {
    $collection = Mage::getModel('catalog/product')->getCollection()
      ->addAttributeToSelect('id')
      // ->addFieldToFilter('sku', array('eq' => 'C-105'))
      ->setPageSize(1000000)
      ->setCurPage(1);

    $i = 0;
    foreach($collection as $product) {
      $product = Mage::getModel('catalog/product')->load($product->getId());
      $product->setIsChanged(true);
      $product->save();
      $i++;
      if(DEBUG === true) {
        echo $product->getSku().PHP_EOL;
      } else {
        echo ".";
      }
    }
    
    echo PHP_EOL."Saved {$i} product(s)".PHP_EOL;
  }
}

$shell = new Resave_Products();
$shell->run();

echo "Reindexing...".PHP_EOL;
shell_exec("/opt/plesk/php/7.3/bin/php indexer.php --mode-realtime");
shell_exec("/opt/plesk/php/7.3/bin/php indexer.php reindexall");
