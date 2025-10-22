<?php

declare(strict_types=1);

namespace Chefstore;

use \Symfony\Component\Cache\Adapter\ApcuAdapter;
use \Symfony\Component\Cache\Adapter\ArrayAdapter;
use \Symfony\Component\Cache\Adapter\ChainAdapter;
use \Symfony\Component\Cache\Adapter\FilesystemAdapter;

class Cache {
  
  // PSR-6:
  
  /*
  // create a new item by trying to get it from the cache
  $productsCount = $cache->getItem('stats.products_count');
  
  // assign a value to the item and save it
  $productsCount->set(4711);
  $cache->save($productsCount);
  
  // retrieve the cache item
  $productsCount = $cache->getItem('stats.products_count');
  if (!$productsCount->isHit()) {
  // ... item does not exist in the cache
  }
  
  // retrieve the value stored by the item
  $total = $productsCount->get();
  
  // remove the cache item
  $cache->deleteItem('stats.products_count');
  */
  
  // CACHE CONTRACTS:
  
  // $cache_key = "tm_richsnippets_product_json_{$product->getId()}";
  //
  // if(Mage::helper("deheerhoreca_fpc/data")->is_read_cache_enabled(true, true, "tm_richsnippets")) {
  //   $json = Mage::app()->getCache()->load($cache_key);
  //   if(empty($json)) {
  //     DeHeerHoreca_Fpc_Helper_Data::log("MISS {$cache_key}");
  //   } else {
  //     DeHeerHoreca_Fpc_Helper_Data::log("HIT {$cache_key}");
  //     echo "<script type=\"application/ld+json\">{$json}</script>";
  //     return;
  //   }
  // }
  //
  // if(Mage::helper("deheerhoreca_fpc/data")->is_write_cache_enabled(true, true, "tm_richsnippets")) {
  //
  //   $cache_tags   = Mage::helper("deheerhoreca_fpc/data")->get_cache_tags();
  //   $cache_tags[] = "DHH_TM_RICHSNIPPETS";
  //
  //   if(Mage::app()->getCache()->save($json, $cache_key, $cache_tags, 86400 * 7)) {
  //     DeHeerHoreca_Fpc_Helper_Data::log("SAVED {$cache_key}");
  //   }
  // }
  
  // ************* //
  
  // $now = _cc()->get($cache_key, function($item) {
  // return Carbon::now();
  // });
  
  // $now = _cc()->get($cache_key, function($item) {
  // $item->expiresAfter(60);
  // return Carbon::now();
  // });
  
  // $var = _cc()->get($cache_key, fn() => "bier");
  // $var = _arc()->get($cache_key, fn() => "bier");
  
  // Makes no sense for CLI
  public static function _apc() {
    $GLOBALS["apcu_cache"] ??= (function_exists("apcu_enabled") && apcu_enabled()) ? new ApcuAdapter(namespace: "om_symfony_apcu", defaultLifetime: 3600, version: "1") : null;
    return $GLOBALS["apcu_cache"];
  }
  
  // Note: Unserialized cache -- Wiped after every run
  public static function _arc() {
    $GLOBALS["ar_cache"] ??= new ArrayAdapter(defaultLifetime: 120, storeSerialized: false, maxLifetime: 0, maxItems: 0);
    return $GLOBALS["ar_cache"];
  }
  
  // Filesystem Cache Adapter -- Not as fast but should be on the RAM disk then it's okay
  public static function _fsc() {
    $GLOBALS["fs_cache"] ??= new FilesystemAdapter(namespace: "om_symfony_fsc", defaultLifetime: 3600, directory: sys_get_temp_dir());
    return $GLOBALS["fs_cache"];
  }
  
  // Creates a cache chain, APCu for CLI and Array otherwise
  // Filesystem is too slow unless it's big or on a RAM disk. $slow_only = true enables the file system cache.
  public static function _cc(bool $slow_only = false) {
    
    // Slow-only cache chain
    if ($slow_only) {
      $GLOBALS["cs_cache"] ??= new ChainAdapter([_fsc()]);
      return $GLOBALS["cs_cache"];
    }
    
    // Fast cache chain
    if (!isset($GLOBALS["c_cache"])) {
      $adapters = (function_exists("apcu_enabled") && apcu_enabled()) ? [_apc()] : [_arc()];
      $GLOBALS["c_cache"] = new ChainAdapter($adapters);
    }
    
    return $GLOBALS["c_cache"];
  }
  
  // Get a fast, single cache adapter. Depends on support of the environment.
  public static function _fc() {
    return $GLOBALS["fast_cache"] ??= (function_exists("apcu_enabled") && apcu_enabled()) ? _apc() : _arc();
  }
  
  public static function _prune_apc() {
    return _apc()->prune();
  }
  
  public static function _prune_arc() {
    return _arc()->prune();
  }
  
  public static function _prune_fsc() {
    return _fsc()->prune();
  }
  
  /* 
  * Delete a key (if exists) from a cache adapter
  * Usage: _c_delete(_fsc(), "foo");
  */
  public static function _c_delete($adapter, string $cache_key) {
    
    // if(DRYRUN) {
    //   if(VERBOSE) {
    //     verbose("Dryrun: Remove cache key: {$cache_key}");
    //   }
    //   return true;
    // }
    
    if ($result = $adapter->delete($cache_key)) {
      // im("cache_delete_key_ok");
      // if(VERBOSE) {
      //   verbose("Removed cache key: {$cache_key}");
      // }
    } else {
      // im("cache_delete_key_nok");
      // notice("Failed to remove cache key: {$cache_key}");
    }
    
    return $result;
  }
}
