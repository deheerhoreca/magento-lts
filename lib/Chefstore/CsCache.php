<?php

declare(strict_types=1);

namespace Chefstore;

use \Closure;
use \Mage;
use \Stringable;
use \Symfony\Component\Cache\Adapter\AdapterInterface;
use \Symfony\Component\Cache\Adapter\ApcuAdapter;
use \Symfony\Component\Cache\Adapter\ArrayAdapter;
use \Symfony\Component\Cache\Adapter\ChainAdapter;
use \Symfony\Component\Cache\Adapter\FilesystemAdapter;
use \Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use \Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use \Symfony\Component\Cache\CacheItem;
use \Symfony\Component\Cache\Exception\CacheException;
use \Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use \Symfony\Component\Cache\Marshaller\DeflateMarshaller;
use \Symfony\Component\Cache\Marshaller\MarshallerInterface;
use \Symfony\Contracts\Cache\ItemInterface;

/**
 * BASIC OPENMAGE CACHE USAGE:
 * These calls make sure our modified Cache class is used (for stats and APCu two-level caching)
 * 
 * Mage::app()->loadCache($cache_key);
 * Mage::app()->saveCache($data, $cache_key, $cache_tags, $lifetime);
 * Mage::app()->removeCache($cache_key);
 * Mage::app()->cleanCache($cache_tags);
 * 
 * ********************************************************************************************************
 * PSR-6:
 * 
 * // create a new item by trying to get it from the cache
 * $productsCount = $cache->getItem('stats.products_count');
 *
 * // assign a value to the item and save it
 * $productsCount->set(4711);
 * $cache->save($productsCount);
 * 
 * // retrieve the cache item
 * $productsCount = $cache->getItem('stats.products_count');
 * if (!$productsCount->isHit()) {
 *   // ... item does not exist in the cache
 * }
 * 
 * // retrieve the value stored by the item
 * $total = $productsCount->get();
 * 
 * // remove the cache item
 * $cache->deleteItem('stats.products_count');
 * 
 * CACHE CONTRACTS:
 * 
 * $cache_key = "tm_richsnippets_product_json_{$product->getId()}";
 * 
 * if(Mage::helper("deheerhoreca_fpc/data")->is_read_cache_enabled(true, true, "tm_richsnippets")) {
 *   $json = Mage::app()->getCache()->load($cache_key);
 *   if(empty($json)) {
 *     DeHeerHoreca_Fpc_Helper_Data::log("MISS {$cache_key}");
 *   } else {
 *     DeHeerHoreca_Fpc_Helper_Data::log("HIT {$cache_key}");
 *     echo "<script type=\"application/ld+json\">{$json}</script>";
 *     return;
 *   }
 * }
 * 
 * if(Mage::helper("deheerhoreca_fpc/data")->is_write_cache_enabled(true, true, "tm_richsnippets")) {
 * 
 *   $cache_tags   = Mage::helper("deheerhoreca_fpc/data")->get_cache_tags();
 *   $cache_tags[] = "DHH_TM_RICHSNIPPETS";
 * 
 *   if(Mage::app()->getCache()->save($json, $cache_key, $cache_tags, 86400 * 7)) {
 *     DeHeerHoreca_Fpc_Helper_Data::log("SAVED {$cache_key}");
 *   }
 * }
 *
 * ********************************************************************************************************
 * SYMFONY CACHE SHORTCUTS:
 * 
 * $now = _cc()->get($cache_key, function($item) {
 *   return Carbon::now();
 * });
 * 
 * $now = _cc()->get($cache_key, function($item) {
 *   $item->expiresAfter(60);
 *   return Carbon::now();
 * });
 * 
 * $var = _cc()->get($cache_key, fn() => "bier");
 * $var = _arc()->get($cache_key, fn() => "bier");
 */
class CsCache {
  
  /**
   * Generate a PSR-6 cache key out of 1+ scalar values.
   * If the key gets too long, it is hashed with the fastest algo available.
   *
   * PSR-6 Cache Keys:
   * ------------------------------------------------------------------------------------------------------------------------------
   * PSR-6 cache key: Key - A string of at least one character that uniquely identifies a cached item. Implementing libraries
   * MUST support keys consisting of the characters A-Z, a-z, 0-9, _, and . in any order in UTF-8 encoding and a length of up to 64
   * characters. Implementing libraries MAY support additional characters and encodings or longer lengths, but must support at least
   * that minimum. Libraries are responsible for their own escaping of key strings as appropriate, but MUST be able to return the
   * original unmodified key string. The following characters are reserved for future extensions and MUST NOT be supported by
   * implementing libraries: {}()/\@:
   *
   * @param  mixed     ...$args
   * @return string
   */
  public static function key(...$args): string {
    $concatenated = implode(".", $args);
    if(strlen($concatenated) > 64) {
      $concatenated = fastHash($concatenated);
    }
    return self::ensureValidKey($concatenated);
  }
  
  /**
   * Replace invalid characters in a cache key.
   *
   * First fold to ASCII, then replace invalid ASCII chars with underscores.
   * Has a $GLOBALS cache for very frequent access.
   *
   * @param  string|Stringable $key
   * @return string
   */
  public static function ensureValidKey(string | Stringable $key): string {
    if($key instanceof Stringable) {
      $key = $key->toString();
    }
    $GLOBALS["validCacheKeys"][$key] ??= str($key)->ascii()->lower()->replace(str_split(" !\"#$%&\'()*+,-/:;<=>?@[\\]^_`{|}~"), "_", true)->toString();
    return $GLOBALS["validCacheKeys"][$key];
  }
  
  /**
   * APCu Cache Adapter -- Might not be available in CLI.
   * - NOT Pruneable
   *
   * @return ApcuAdapter
   */
  public static function _apc(): ApcuAdapter | null {
    static $adapter = null;
    return ($adapter ??= new ApcuAdapter(
      namespace : "openmage",
      defaultLifetime: 600,
      version: null,
      marshaller: self::getMarshaller("default")
    ));
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
      $adapters = (function_exists("apcu_enabled") && apcu_enabled()) ? [self::_apc()] : [self::_arc()];
      $GLOBALS["c_cache"] = new ChainAdapter($adapters);
    }
    
    return $GLOBALS["c_cache"];
  }
  
  /**
   * Get a Fast Cache Adapter -- APCu if available, otherwise Array Cache.
   * Instead of calling apc() or arc() directly, call fc().
   * - NOT Pruneable, NOT TagAware, in-memory
   *
   * @return ApcuAdapter|ArrayAdapter
   */
  public static function _fc(): ApcuAdapter | ArrayAdapter {
    static $adapter = null;
    return ($adapter ??= (function_exists("apcu_enabled") && apcu_enabled() ? self::_apc() : self::_arc()));
  }
  
  /**
   * Get the appropriate MarshallerInterface instance for this system.
   *
   * - Symfony uses extension_loaded(), so we do the same.
   * @todo Add TagAwareMarshaller support for FilesystemTagAwareAdapter
   *
   * @param  string|null                           $type The marshaller type: "deflate" or null for default
   * @return DefaultMarshaller|DeflateMarshaller
   */
  public static function getMarshaller(?string $type = null): DefaultMarshaller | DeflateMarshaller {
    // ! Setup igbinary in php.ini, not here -- Leads to issues where some parts of OpenMage address the cache outside of our control.
    $useIgbinary = false;
    // $useIgbinary = extension_loaded("igbinary");
    
    if($type === "deflate") {
      return new DeflateMarshaller(new DefaultMarshaller(useIgbinarySerialize: $useIgbinary));
    }
    return new DefaultMarshaller(useIgbinarySerialize: $useIgbinary);
  }
  
  /**
   * Get a cached value from a cache adapter, or generate it using a callback.
   * Arguments intentionally shortened to reduce distraction when reading code.
   *
   * @param  string|null           &$key      The cache key. If NULL, a key will be generated from the Closure.
   * @param  Closure               $closure   The Closure to generate the value if not cached
   * @param  object|null           $instance  The cache adapter instance to use (defaults to the best available fast cache)
   * @param  string|iterable|null  $tag       The tag or tags to assign to the cached item
   * @param  bool                  $fresh     Whether to force refresh the cached value
   * @param  int|null              $t         The lifetime of the cached item in seconds (null = default)
   *
   * @return mixed
   */
  function cached(?string &$key, Closure $closure, ?object $instance = null, string|iterable|null $tag = null, bool $fresh = false, ?int $t = null): mixed {
    $key      ??= spl_object_hash($closure);
    $beta       = $fresh ? INF : 0;
    $instance ??= self::_fc();
    return $instance->get($key, function(ItemInterface $item) use ($closure, $t, $tag): mixed {
      if (null !== $t) {
        $item->expiresAfter($t);
      }
      if(null !== $tag) {
        $item->tag($tag);
      }
      return $closure($item);
    }, $beta);
  }
	
  /**
   * Get a cached value from a cache adapter, or generate it using a callback, without specifying the key.
   * Arguments intentionally shortened to reduce distraction when reading code.
   *
   * @param  Closure               $closure   The Closure to generate the value if not cached
   * @param  object|null           $instance  The cache adapter instance to use (defaults to the best available fast cache)
   * @param  string|iterable|null  $tag       The tag or tags to assign to the cached item
   * @param  bool                  $fresh     Whether to force refresh the cached value
   * @param  int|null              $t         The lifetime of the cached item in seconds (null = default)
   *
   * @return mixed
   */
  function retain(Closure $closure, ?object $instance = null, string|iterable|null $tag = null, bool $fresh = false, ?int $t = null): mixed {
    $key = null;
    return cached($key, $closure, $instance, $tag, $fresh, $t);
  }
  
  /**
   * Prune the APC cache instance
   *
   * @return bool
   */
  public static function _prune_apc(): bool {
    return self::_apc()->prune();
  }
  
  /**
   * Prune the Array cache instance
   *
   * @return bool
   */
  public static function _prune_arc(): bool {
    return self::_arc()->prune();
  }
  
  /**
   * Prune the Filesystem cache instance
   *
   * @return bool
   */
  public static function _prune_fsc(): bool {
    return self::_fsc()->prune();
  }
  
  /** 
   * Delete a key (if exists) from a cache adapter. Usage: _c_delete(_fsc(), "foo");
   *
   * @param  AdapterInterface  $adapter
   * @param  string            $cache_key
   *
   * @return bool
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
	
	// Native OpenMage gateway functions:
	
  /**
   * Load a cache item by key via the native OpenMage method.
   * Will hit 2-level cache and CacheStats.
   *
   * @param  mixed  $id   The cache key, WITHOUT prefixes.
   * @return string|false The cached value, or false if not found.
   *
   * @see Mage_Core_Model_Cache::load() for more details.
   */
	public static function get($id): string|false {
		return Mage::app()->loadCache($id);
	}
	
	/**
	 * Save a cache by key via native OpenMage method.
	 * Will hit 2-level cache.
	 *
	 * @param  mixed  $data
	 * @param  mixed  $key
	 * @param  array  $tags
	 * @param  mixed  $lifetime
   *
	 * @return true
	 */
	public static function save($data, $key, $tags = [], $lifetime = null): true {
		Mage::app()->saveCache($data, $key, $tags, $lifetime);
		return true; // The OpenMage function does not report success.
	}
	
	/**
	 * Delete a cache by key via native OpenMage method.
	 * Will hit 2-level cache.
	 *
	 * @param  mixed $id
	 * @return true
	 */
	public static function delete($id): true {
		Mage::app()->removeCache($id);
		return true;
	}
	
	/**
	 * Clean cache by tags via native OpenMage method.
	 * Will hit 2-level cache.
	 *
	 * @param  array $tags
	 * @return true
	 */
	public static function clean($tags = []): true {
		Mage::app()->cleanCache($tags);
		return true;
	}
}
