<?php

declare(strict_types=1);

namespace Chefstore;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

const DHH_DEV_IPS = ["5.132.21.238", "185.127.111.251", "185.127.111.252", "87.210.61.235", "185.127.111.227", "81.59.51.217"];

// Setup global aliases to prevent "use" statements all over -- Needs test because this file might get included multiple times by Composer
// Cannot be executed multiple times between our apps
if(!defined("DHH_CLASS_ALIASES_APPLIED")) {
  if(!is_callable("Arr"))           class_alias(Arr::class, "Arr", true);
  if(!is_callable("Collection"))    class_alias(Collection::class, "Collection", true);
  if(!is_callable("Number"))        class_alias(Number::class, "Number", true);
  if(!is_callable("Str"))           class_alias(Str::class, "Str", true);
  if(!is_callable("ItemInterface")) class_alias(ItemInterface::class, "ItemInterface", true);
  define("DHH_CLASS_ALIASES_APPLIED", true);
}

class Html {
  
  // \Chefstore\Html\addEncodedJsStatement("var x=1;");
  // Add an already-encoded JavaScript statement for echo'ing just before </body>
  public static function addEncodedJsStatement(string $statement): void {
    $GLOBALS["footer_js_statements"] ??= [];
    $GLOBALS["footer_js_statements"][] = $statement;
  }
  
  // Echo any queued JavaScript statements that could wait until just before </body>
  // Statements should be encoded before storing them!
  public static function writeJsStatements(): void {
    if(isset($GLOBALS["footer_js_statements"])) {
      foreach($GLOBALS["footer_js_statements"] as $statement) {
        echo "<script>".$statement."</script>".PHP_EOL;
      }
    }
  }
  
}

class Utils {
  
  public static $dev_ips = [
    "5.132.21.238",
    "185.127.111.227",
    "185.127.111.251",
    "185.127.111.252",
    "87.210.61.235",
    "81.59.51.217",
  ];
  
  // \Chefstore\Utils\dump("foo");
  public static function dump($mixed, bool $return = false) {
    // if(!isset($_GET['nofpc']) || !isset($_SERVER["REMOTE_ADDR"]) || !in_array($_SERVER["REMOTE_ADDR"], self::$dev_ips, true)) {
      // return;
    // }
    
    if(is_callable($mixed)) {
      self::printr($mixed(), $return);
    } else {
      self::printr($mixed, $return);
    }
  }
  
  public static function is_serialized( $data, $strict = true ) {
    // If it isn't a string, it isn't serialized.
    if ( ! is_string( $data ) ) {
      return false;
    }
    $data = trim( $data );
    if ( 'N;' === $data ) {
      return true;
    }
    if ( strlen( $data ) < 4 ) {
      return false;
    }
    if ( ':' !== $data[1] ) {
      return false;
    }
    if ( $strict ) {
      $lastc = substr( $data, -1 );
      if ( ';' !== $lastc && '}' !== $lastc ) {
        return false;
      }
    } else {
      $semicolon = strpos( $data, ';' );
      $brace     = strpos( $data, '}' );
      // Either ; or } must exist.
      if ( false === $semicolon && false === $brace ) {
        return false;
      }
      // But neither must be in the first X characters.
      if ( false !== $semicolon && $semicolon < 3 ) {
        return false;
      }
      if ( false !== $brace && $brace < 4 ) {
        return false;
      }
    }
    $token = $data[0];
    switch ( $token ) {
      case 's':
        if ( $strict ) {
          if ( '"' !== substr( $data, -2, 1 ) ) {
            return false;
          }
        } elseif ( !str_contains( $data, '"' ) ) {
          return false;
        }
        // Or else fall through.
      case 'a':
      case 'O':
        return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
      case 'b':
      case 'i':
      case 'd':
        $end = $strict ? '$' : '';
        return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
    }
    return false;
  }
  
  // PHP var_export() with short array syntax (square brackets) indented 2 spaces.
  // NOTE: The only issue is when a string value has `=>\n[`, it will get converted to `=> [`
  // @link https://www.php.net/manual/en/function.var-export.php
  // \Chefstore\Utils::d()
  public static function d($expression, bool $return = false) {
    $export     = var_export($expression, true);
    $patterns   = [
      "/array \(/" => '[',
      "/^([ ]*)\)(,?)$/m" => '$1]$2',
      "/=>[ ]?\n[ ]+\[/" => '=> [',
      "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
    ];
    $export     = preg_replace(array_keys($patterns), array_values($patterns), $export);
    
    if($return) {
      return $export;
    }
    
    printr($export);
  }
  
  public static function printr($expression, bool $return = false) {
  
    if(is_object($expression) && get_class($expression) === "Illuminate\Support\Stringable") {
      $expression = $expression->toString();
    }
    
    if(!is_scalar($expression) && (is_array($expression) && !sizeof($expression))) {
      return;
    }
    
    if(php_sapi_name() !== "cli") {
      $ret .= "<pre style='white-space: pre-wrap; word-wrap:break-word;'>";
    }
    
    $ret .= print_r($expression, true);
    
    if(php_sapi_name() !== "cli") {
      $ret .= "</pre>";
    }
    $ret .= PHP_EOL;
    
    if($return) {
      return $ret;
    }
    
    echo $ret;
  }
  
  public static function devdump($expression, bool $return = false): bool {
    if(isset($_GET["nofpc"]) && isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], DHH_DEV_IPS, true)) {
      d($expression, $return);
      return null;
    }
    
    return false;
  }
  
  function msleep(int $time): void {
    usleep($time * 1000);
  }
}

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
    $GLOBALS["apcu_cache"] ??= (function_exists("apcu_enabled") && apcu_enabled()) ? new ApcuAdapter(namespace: "om_symfony_apcu", defaultLifetime: 3600, version: 1) : null;
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
    if($slow_only) {
      $GLOBALS["cs_cache"] ??= new ChainAdapter([_fsc()]);
      return $GLOBALS["cs_cache"];
    }
    
    // Fast cache chain
    if(!isset($GLOBALS["c_cache"])) {
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
    
    if($result = $adapter->delete($cache_key)) {
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
