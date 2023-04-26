<?php

// use dekor\ArrayToTextTable;

// @see https://www.atwix.com/magento/duplicated-product-url-keys-in-community-edition/

// Normal flow:
// php shell/rewrites_doctor.php update_keys (1-5 min)
// php shell/indexer.php --reindex catalog_url (5-10 min)
// For STORE_ID = 1: php shell/rewrites_doctor.php --remove_rewrites 10 --store 1
// For STORE_ID = 4: php shell/rewrites_doctor.php --remove_rewrites 1 --store 4

const DRYRUN = true;

require_once "abstract.php";

class Atwix_Shell_Rewrites_Doctor extends Mage_Shell_Abstract {
  
  const PAGE_SIZE = 1000;
  const LOG_MESSAGE_ROWS = 100;
  const MAX_SLUG_LENGTH = 60;
  
  public function run() {
    if($left = $this->getArg("remove_rewrites")) {
      define(STORE_ID, $this->getArg("store"));
      if(empty(STORE_ID)) {
        die("use --store X");
      }
      $this->clearExtraRewrites($left);
    } elseif($this->getArg("update_keys")) {
      $this->updateDuplicatedKeys();
    } elseif($this->getArg("remove_sku_spaces")) {
      $this->removeSkuSpaces();
    } else {
      echo $this->usageHelp();
    }
  }
  
  // Update duplicated url keys by adding product SKU to the duplicated key
  public function updateDuplicatedKeys() {
    
    $debug_data = [];
    
    try {
      $counter = 0;
      $start = time();
      $storeId = Mage::app()->getStore()->getId().PHP_EOL;

      //url key attriubte load for further use
      
      $entityType = Mage::getModel("eav/entity_type")->loadByCode("catalog_product");
      $attributes = $entityType->getAttributeCollection()
        ->addFieldToFilter("attribute_code", array("eq" => "url_key"))
      ;
      $urlKeyAttribute = $attributes->getFirstItem();
      $urlKeyAttributeTable = $attributes->getTable($entityType->getEntityTable());

      //loading collection with number of duplicated url keys
      $duplicatesCollection = Mage::getModel("catalog/product")->getCollection();
      $duplicatesCollection->getSelect()
        ->joinLeft(
          ["url_key" => $urlKeyAttributeTable . "_" . $urlKeyAttribute->getBackendType()],
          "e.entity_id" . " = url_key.entity_id AND url_key.attribute_id = " . $urlKeyAttribute->getAttributeId() . " AND url_key.store_id = " . $storeId,
          [$urlKeyAttribute->getAttributeCode() => "url_key.value"]
        )
        ->columns(["duplicates_calculated" => new Zend_Db_Expr("COUNT(`url_key`.`value`)")])
        ->group("url_key.value")
        ->order("duplicates_calculated DESC")
      ;

      foreach($duplicatesCollection as $item) {
        if($item->getData("duplicates_calculated") > 1) {
          // Loading product ids with duplicated url keys
          $duplicatedUrlKey = $item->getData("url_key");
          $productCollection = Mage::getModel("catalog/product")->getCollection()
            ->addAttributeToSelect("url_key")
            ->addAttributeToSelect("sku")
            ->addAttributeToFilter("url_key", array("eq" => $duplicatedUrlKey))
          ;
          $ids = $productCollection->getAllIds();
          $skus = $productCollection->getColumnValues("sku");
          
          echo "SKUs [".implode(", ", $skus)."] share the same URK key: {$duplicatedUrlKey}".PHP_EOL;

          foreach($ids as $id) {
            try {
              // update product url key
              $product = Mage::getModel("catalog/product")->load($id);
              $sku = $product->getData("sku");
              $urlKey = $product->getData("url_key");
              $name = $product->getData("name");
              $new_key = $this->slug($name, $sku);
              if(0 && $urlKey === $new_key) {
                $message = "{$sku} [{$product->getId()}] url_key {$urlKey} is the same as {$product->getData("url_key")}".PHP_EOL;
              } else {
                $product->setData("url_key", $new_key);
                if(DRYRUN === false) {
                  // $product->getResource()->saveAttribute($dataobject, "url_key");
                  if(!$product->save()) {
                    echo "Error: Failed to save {$product->getSku()}".PHP_EOL;
                  }
                }
                $counter++;
                $message = "{$sku} [{$product->getId()}] url_key was changed from {$urlKey} to {$product->getData("url_key")}".PHP_EOL;
                $debug_data[] = [
                  "sku"           => $sku,
                  "old_url_key"   => $urlKey,
                  "new_url_key"   => $product->getData("url_key"),
                ];
              }
              if($counter % self::LOG_MESSAGE_ROWS == 0) {
                Mage::log($message, null, "atwix_rewrites_doctor.log", true);
              }
              echo $message;
            } catch (Exception $e) {
              echo $e->getMessage() . PHP_EOL;
              Mage::log($e->getMessage(), null, "atwix_rewrites_doctor.log", true);
            }
          }
        } else {
          //we will break the cycle after all duplicates in query were processed
          break;
        }
      }

      if($counter % self::LOG_MESSAGE_ROWS != 0) {
        Mage::log($logMessage, null, "atwix_rewrites_doctor.log", true);
      }

      $end = time();
      $message = $counter . " products were updated, time spent: " . $this->timeSpent($start, $end);
      Mage::log($message, null, "atwix_rewrites_doctor.log", true);
      echo $message . PHP_EOL;

    } catch (Exception $e) {
      echo $e->getMessage() . PHP_EOL;
      Mage::log($e->getMessage(), null, "atwix_rewrites_doctor.log", true);
    }
    
    // echo (new ArrayToTextTable($debug_data))->render().PHP_EOL;
    // print_r($debug_data);
  }
  
  // Remove extra product url rewrites leaving $left of last
  // @var $left
  public function clearExtraRewrites($left) {
    
    $debug_data = [];
    
    echo "Store ID = ".STORE_ID.PHP_EOL;
    
    switch(STORE_ID) {
      case 1:
        $limit = 10;
        if($left < $limit) {
          echo "Refusing to run on store ID ".STORE_ID." with less than {$limit} rewrites left".PHP_EOL;
          return false;
        }
        break;
      case 4:
        $limit = 1;
        if($left < $limit) {
          echo "Refusing to run on store ID ".STORE_ID." with less than {$limit} rewrites left".PHP_EOL;
          return false;
        }
        break;
      default:
        echo "Refusing to run on store ID ".STORE_ID.PHP_EOL;
        return false;
    }
    
    $backup_table = $this->backupTable();    
    if($result === false) {
      echo "Error while backing up table, quiting...".PHP_EOL;
      return false;
    }
    
    try {
      $start = time();
      // Get product collection
      $productCollection = Mage::getModel("catalog/product")->getCollection();
      // $productCollection->setPageSize(self::PAGE_SIZE);
      // $pages = $productCollection->getLastPageNumber();
      // $currentPage = 1;
      $counter_deleted = 0;
      $counter_total = 0;
      
      // @todo add count query
      
      // $productCollection->setCurPage($currentPage);
      $productCollection->load();
      $ids = $productCollection->getAllIds();
      $counter_skus = sizeof($ids);
      echo "Doing {$counter_skus} SKUs...".PHP_EOL;
      foreach($ids as $id) {
        // Get rewrites collection for current product id
        $urlRewritesCollection = Mage::getModel("core/url_rewrite")->getCollection()
          ->addFieldToFilter("product_id",  ["eq" => $id])
          ->addFieldToFilter("is_system",   ["eq" => "0"])
          ->addFieldToFilter("store_id",    ["eq" => STORE_ID])
          ->setOrder("url_rewrite_id", "DESC")
        ;
        $urlRewritesCollection->getSelect()->limit(null, $left);
        
        foreach($urlRewritesCollection as $urlRewrite) {
          $counter_total++;
          try {
            if(DRYRUN === false) {
              $urlRewrite->delete();
            }
            $msg = "Deleted: [store_id {$urlRewrite->getStoreId()}, product_id {$urlRewrite->getProductId()}, rewrite_id {$urlRewrite->getUrlRewriteId()}]";
            echo $msg.PHP_EOL;
            Mage::log($msg, null, "atwix_rewrites_doctor.log", true);
            $counter_deleted++;
          } catch(Exception $e) {
            echo "An error was occurred: ".$e->getMessage().PHP_EOL;
            Mage::log($e->getMessage(), null, "atwix_rewrites_doctor.log", true);
          }
        }
      }
    } catch (Exception $e) {
      echo "An error was occurred: ".$e->getMessage().PHP_EOL;
      Mage::log($e->getMessage(), null, "atwix_rewrites_doctor.log", true);
    }
    
    if($counter_total > 0) {
      $pct_deleted = number_format(($counter_deleted / $counter_total * 100), 2);
    } else {
      $pct_deleted = 0;
    }
    $message = "Total URL rewrites deleted: {$counter_deleted}/{$counter_total} ({$pct_deleted}%), total number of SKUs: {$counter_skus}, time spent: ".$this->timeSpent($start, time());
    echo $message.PHP_EOL;
    Mage::log($message, null, "atwix_rewrites_doctor.log", true);
    
    if(DRYRUN !== true) {
      echo "Notice: If everything looks OK, you should remove the backup table \"{$backup_table}\"".PHP_EOL;
    }
  }
  
  public function timeSpent($start, $end) {
    $seconds = $end - $start;
    $hours = floor($seconds / 3600);
    $mins = floor(($seconds - ($hours*3600)) / 60);
    $secs = floor($seconds % 60);

    return $hours . " hours " . $mins . " minutes " . $secs . " seconds";
  }
  
  // Retrieve Usage Help Message
  public function usageHelp()  {
    return "
    \n
    \n Usage:  php -f fix_attributes -- [options]
    \n
    \n  --remove_rewrites number  Remove old product rewrites, leaving the 'number' of last ones
    \n  update_keys               Update duplicated product keys
    \n  remove_sku_spaces         Remove space from all product SKU's if they are present
    \n
    \n  help            This help
    \n
    ";
  }
  
  private function backupTable() {
    $resource = Mage::getSingleton('core/resource');
    $writeConnection = $resource->getConnection('core_write');
    $table = $resource->getTableName('core_url_rewrite');
    $new_table = $resource->getTableName('core_url_rewrite')."_".STORE_ID."_".date("Ymd_H");
    
    if(empty($writeConnection->fetchAll("SHOW TABLES LIKE '{$new_table}'")) === false) {
      echo "Backup table {$new_table} already exists, quiting...".PHP_EOL;
      return false;
    }
    
    $query1 = "CREATE TABLE `{$new_table}` LIKE `{$table}`;";
    $query2 = "INSERT INTO `{$new_table}` SELECT * FROM `{$table}` WHERE store_id = '".STORE_ID."';";
    echo $query1.PHP_EOL;
    if(DRYRUN === false) {
      $result = $writeConnection->query($query1);
    }
    echo $query2.PHP_EOL;
    if(DRYRUN === false) {
      $result = $writeConnection->query($query2);
    }
    echo "Copied {$table} to {$new_table}".PHP_EOL;
    
    return $new_table;
  }
  
  // Translate a product name and a SKU, and creates an URL slug without special characters and limited in length
  private function slug($name, $sku) {
    // Don't remove + signs, unique SKUs will collide
    $name = str_replace("+", "plus", $name);
    $sku = str_replace("+", "plus", $sku);
    
    $sku = strtolower(trim(preg_replace("~[^0-9a-z]~i", "-", html_entity_decode(preg_replace("~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i", "$1", htmlentities($sku, ENT_QUOTES, "UTF-8")), ENT_QUOTES, "UTF-8")), "-"));
    $sku = strtolower(str_replace(" ", "-", $sku));
    $name = strtolower(trim(preg_replace("~[^0-9a-z]~i", "-", html_entity_decode(preg_replace("~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i", "$1", htmlentities($sku, ENT_QUOTES, "UTF-8")), ENT_QUOTES, "UTF-8")), "-"));
    $name = strtolower(str_replace(" ", "-", $name));
    $name = substr($name, 0, (self::MAX_SLUG_LENGTH - strlen($sku)));
    $name = str_replace($sku, "", $name);
    $parts[] = $name;
    $parts[] = $sku;
    $parts = array_filter($parts);
    $string = implode("-", $parts);
    return $string;
  }
}

if(DRYRUN === true) {
  echo "DRYRUN is on".PHP_EOL;
} else {
  echo "DRYRUN is off".PHP_EOL;
  echo "Are you sure you want to do this? y/N: ";
  $handle = fopen("php://stdin","r");
  $line = fgets($handle);
  if(strtolower(trim($line)) !== 'y') {
    echo "ABORTING!\n";
    exit;
  }
  fclose($handle);
}

ini_set("memory_limit", "8G");

$shell = new Atwix_Shell_Rewrites_Doctor();
$shell->run();
