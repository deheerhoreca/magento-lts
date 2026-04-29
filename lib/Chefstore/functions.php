<?php

declare(strict_types=1);

use \Brick\VarExporter\ExportException;
use \Brick\VarExporter\VarExporter;
use \Chefstore\CacheBuster;
use \Chefstore\CsCache;
use \Chefstore\Helper as ChefstoreHelper;
use \Chefstore\Observability;
use \Illuminate\Support\Arr;
use \Illuminate\Support\Benchmark as LaravelBenchmark;
use \Illuminate\Support\Number;
use \Illuminate\Support\Str;
use \Illuminate\Support\Stringable;
use \Illuminate\Support\Uri;
use \Symfony\Component\VarDumper\Cloner\VarCloner;
use \Symfony\Component\VarDumper\Dumper\CliDumper;
use \Symfony\Component\VarDumper\Dumper\HtmlDumper;

require_once __DIR__."/Loader.php";

/**
 * !!! Attention !!!
 * @todo  Review duplicated functions between Intel and OpenMage and consider removing them from Intel (when OpenMage-specific).
 * @todo  For all remaining conficting functions, use `!function_exists()` to avoid conflicts when running as an Intel plugin.
 */

/* ---------------------------------------------------------- Chefstore\Helper ---------------------------------------------------------- */

/**
 * Get the DeHeerHoreca Util helper.
 *
 * @return \DeHeerHoreca_Util_Helper_Util
 */
function getOmDhhUtilHelper(): \DeHeerHoreca_Util_Helper_Util {
  return \Mage::helper("deheerhoreca_util/util");
}

/**
 * Get the DeHeerHoreca Util helper.
 *
 * @return \DeHeerHoreca_Fpc_Helper_Data
 */
function getOmDhhFpcHelper(): \DeHeerHoreca_Fpc_Helper_Data {
  return \Mage::helper("deheerhoreca_fpc/data");
}

// Laravel picks

if(!function_exists("rescue")) {
  /**
   * Catch a potential exception and return a default value.
   *
   * @template TValue
   * @template TFallback
   *
   * @param  callable(): TValue  $callback
   * @param  (callable(\Throwable): TFallback)|TFallback  $rescue
   * @param  bool|callable(\Throwable): bool  $report
   * @return TValue|TFallback
   */
  function rescue(callable $callback, $rescue = null, $report = true): mixed {
    try {
        return $callback();
    } catch (Throwable $exception) {
      if (value($report, $exception)) {
        if (is_string($exception)) {
          $exception = new Exception($exception);
        }
        logger("Rescued exception: {$exception->getMessage()}", "ERROR");
        logThrowable($exception);
      }
      
      return value($rescue, $exception);
    }
  }
}

if(!function_exists("value")) {
  /**
   * Return the default value of the given value.
   *
   * @template TValue
   * @template TArgs
   *
   * @param  TValue|\Closure(TArgs): TValue  $value
   * @param  TArgs  ...$args
   * @return TValue
   */
  function value($value, ...$args): mixed {
    return $value instanceof Closure ? $value(...$args) : $value;
  }
}

// Laravel aliases, gateways, shortcuts, etc.

/**
 * Data get. Supports key with dots. WARNING: will return NULL instead of $default.
 *
 * @param  array|ArrayAccess $target
 * @param  mixed $key
 * @param  mixed $default
 * 
 * @return mixed
 */
if(!function_exists("dg")) {
  function dg(array|ArrayAccess $target, mixed $key, mixed $default = null): mixed {
    return data_get($target, $key, $default);
  }
}

/**
 * Data get. Supports key with dots. WARNING: will return NULL instead of $default.
 *
 * @param  array|ArrayAccess $target
 * @param  mixed $key
 * @param  mixed $default
 * 
 * @return mixed
 */
if(!function_exists("dg")) {
  function dg(array|ArrayAccess $target, mixed $key, mixed $default = null): mixed {
    return data_get($target, $key, $default);
  }
}

/**
 * Shorthand for data_set(). Supports key with dots.
 *
 * @param  array|ArrayAccess $target
 * @param  mixed             $key
 * @param  mixed             $value
 * @param  boolean           $overwrite
 * 
 * @return mixed
 */
if(!function_exists("ds")) {
  function ds(array|ArrayAccess &$target, mixed $key, mixed $value, bool $overwrite = true): mixed {
    return data_set($target, $key, $value, $overwrite);
  }
}

/**
 * Stuff another key in there. MIGHT NOT SUPPORT KEYS WITH DOTS!
 *
 * @param  array|ArrayAccess $array
 * @param  string            $key
 * @param  mixed             $value
 * 
 * @return mixed
 */
if(!function_exists("dp")) {
  function dp(array|ArrayAccess &$array, string $key, mixed $value): mixed {
    return data_push($array, $key, $value);
  }
}

/**
 * Data yank. Supports key with dots.
 *
 * @param  array|ArrayAccess $array
 * @param  mixed             $key
 * @param  mixed             $default
 * 
 * @return mixed
 */
if(!function_exists("dy")) {
  function dy(array|ArrayAccess &$array, mixed $key, mixed $default = null): mixed {
    return Arr::pull($array, $key, $default);
  }
}

/**
 * Yank a value from the array by key, or $default, or NULL if blank or cannot be converted. Supports key with dots.
 * > Watch out for by-reference issues.
 *
 * @param  array        $array
 * @param  string|int   $key
 * @param  string|null  $default  Defaults to NULL.
 *
 * @return string|null
 */
if(!function_exists("dyAsNullStr")) {
  function dyAsNullStr(array &$array, string|int $key, string|null $default = null): string|null {
    $value = dy($array, $key, $default);
    return asNullStr($value);
  }
}

/**
 * Yank a value from the array by key, or $default, coerced to string by asString(). Supports key with dots.
 * > Watch out for by-reference issues.
 * > Checks for Stringable, __toString(), etc. and attempts to cast.
 *
 * @param  array       $array
 * @param  string|int  $key
 * @param  string      $default  Defaults to an empty string.
 *
 * @return string
 */
if(!function_exists("dyAsStr")) {
  function dyAsStr(array &$array, string|int $key, string $default = ""): string {
    $value = dy($array, $key, $default);
    return asString($value);
  }
}

/**
 * Yank a value from the array by key, or $default, coerced to bool by JBzoo/Filter. Supports key with dots.
 * > Watch out for by-reference issues.
 * > Supports various string representations of booleans (e.g. "yes", "no", "1", "0", "true", "false", etc.)
 *
 * @param  array       $array
 * @param  string|int  $key
 * @param  bool        $default  A default MUST be provided.
 *
 * @return bool
 */
if(!function_exists("dyAsBool")) {
  function dyAsBool(array &$array, string|int $key, bool $default): bool {
    $value = dy($array, $key, $default);
    return filled($value) ? bool($value) : null;
  }
}

/**
 * Yank a value from the array by key, or $default, coerced to bool by JBzoo/Filter. Supports key with dots.
 * > Watch out for by-reference issues.
 * > Supports various string representations of booleans (e.g. "yes", "no", "1", "0", "true", "false", etc.)
 *
 * @param  array       $array
 * @param  string|int  $key
 * @param  bool|null   $default  Defaults to NULL.
 *
 * @return bool|null
 */
if(!function_exists("dyAsNullBool")) {
  function dyAsNullBool(array &$array, string|int $key, bool|null $default = null): bool|null {
    $value = dy($array, $key, $default);
    return filled($value) ? bool($value) : null;
  }
}

/**
 * Yank a value from the array by key, or $default, coerced to int by JBzoo/Filter. Supports key with dots.
 * > Watch out for by-reference issues.
 * > Smart convert any string to int.
 *
 * @param  array       $array
 * @param  string|int  $key
 * @param  int         $default  A default MUST be provided.
 *
 * @return int
 */
if(!function_exists("dyAsInt")) {
  function dyAsInt(array &$array, string|int $key, int $default): int {
    $value = dy($array, $key, $default);
    return int($value);
  }
}

/**
 * Yank a value from the array by key, or $default, coerced to int by JBzoo/Filter, or NULL if blank. Supports key with dots.
 * > Watch out for by-reference issues.
 * > Smart convert any string to int.
 *
 * @param  array       $array
 * @param  string|int  $key
 * @param  int|null    $default  Defaults to NULL.
 *
 * @return int|null
 */
if(!function_exists("dyAsNullInt")) {
  function dyAsNullInt(array &$array, string|int $key, int|null $default = null): int|null {
    $value = dy($array, $key, $default);
    if(blank($value)) {
      return null;
    }
    return int($value);
  }
}

/**
 * Get a value as a string, or NULL if blank or cannot be converted.
 *
 * @param   mixed  $value
 * @return  string|null
 */
if(!function_exists("asNullStr")) {
  function asNullStr(mixed $value): string|null {
    if(blank($value)) {
      return null;
    }
    return asString($value);
  }
}

/**
 * Coerce a value into a string, or NULL. Will never or throw, just return "".
 * Checks for Stringable, __toString(), etc. and attempts to cast.
 * @todo Support all string-ish things that we use in Intel, (Laravel, Symfony, JBZoo, AppUtils, Stringy, Arrayy, ...).
 *
 * @param  mixed        $value
 * @return string
 */
if(!function_exists("asString")) {
  function asString(mixed $value): string {
    if(blank($value)) {
      return "";
    }
    
    try {
      if(is_object($value) && method_exists($value, "toString")) {
        return $value->toString();
      }
      return (string) $value;
    } catch(Throwable) {
      return "";
    }
  }
}

/**
 * Clamp the given number between the given minimum and maximum.
 *
 * @param  integer|float $min
 * @param  integer|float $number
 * @param  integer|float $max
 * 
 * @return integer|float
 */
if(!function_exists("clampNumber")) {
  function clampNumber(int|float $min, int|float $number, int|float $max): int|float {
    return Number::clamp($number, $min, $max);
  }
}

/**
 * Append a value by dot notation.
 * 
 * - If the key does not exist or is NULL, it will be set to the value.
 * - If the key exists and is an indexed array, the value(s) will be appended to it.
 * - If a value exists and is scalar or an associative array, it will be converted to an indexed array, then merged with the new value.
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $value
 *
 * @return array
 */
if(!function_exists("data_push")) {
  function data_push(array &$array, string $key, mixed $value): array {
    // Tried but broke product.attributes push:
    // if(!Arr::has($array, $key) || blank($tmp = dg($array, $key))) {
    //   return ds($array, $key, $value);
    // }
    
    $tmp = dg($array, $key, []);
    
    if(!is_iterable($tmp)) {
      $tmp = [$tmp];
    } else {
      $tmp = (array) $tmp;
    }
    
    if(!is_iterable($value) || Arr::isAssoc($value)) {
      $tmp[] = $value;
    } else {
      $tmp = array_merge($tmp, $value);
    }
    
    return ds($array, $key, $tmp, overwrite: true);
  }
}

/**
 * Alias of data_push()
 * 
 * @deprecated Use data_push() instead
 *
 * @param  array  $array
 * @param  string $key
 * @param  mixed  $value
 *
 * @return mixed
 */
if(!function_exists("data_append")) {
  function data_append(array|object &$array, string $key, mixed $value): mixed {
    return data_push($array, $key, $value);
  }
}

/**
 * data_fill() but one that overwrites blank() values.
 *
 * @param  array $array
 * @param  mixed $key
 * @param  mixed $value
 *
 * @return array
 */
if(!function_exists("data_add")) {
  function data_add(array &$array, mixed $key, mixed $value): array {
    if(data_blank($array, $key)) {
      Arr::set($array, $key, $value);
    }
    return $array;
  }
}

/**
 * Like filled() but for arrays.
 *
 * @param  Iterable $array
 * @param  mixed    $key
 * @return boolean
 */
if(!function_exists("data_filled")) {
  function data_filled(Iterable $array, mixed $key): bool {
    if(Arr::has($array, $key)) {
      return filled(data_get($array, $key, null));
    }
    return false;
  }
}

/**
 * Get a value from the array, and remove it
 *
 * @param  Iterable $array
 * @param  mixed    $key
 * @param  mixed    $default
 * 
 * @return mixed
 */
if(!function_exists("data_pull")) {
  function data_pull(Iterable &$array, mixed $key, mixed $default = null): mixed {
    return Arr::pull($array, $key, $default);
  }
}

/**
 * Like blank() but for arrays.
 *
 * @param  Iterable $array
 * @param  mixed    $key
 * 
 * @return boolean
 */
if(!function_exists("data_blank")) {
  function data_blank(Iterable $array, mixed $key): bool {
    if(Arr::has($array, $key)) {
      return blank(dg($array, $key, null));
    }
    return true;
  }
}

/**
 * Coalesce until filled()
 *
 * @param  Iterable $data
 * @param  mixed    $default
 * @param  mixed    ...$keys
 * 
 * @return mixed
 */
if(!function_exists("data_coalesce")) {
  function data_coalesce(Iterable $data, mixed $default, mixed ...$keys): mixed {
    foreach($keys as $key) {
      if(data_filled($data, $key)) {
        return data_get($data, $key);
      }
    }
    return $default;
  }
}

/**
 * Return an array of trusted IPs.
 * 
 * Used to enable debug logging and ad-hoc printing of debug info on the frontend.
 * 
 * @return array
 */
if(!function_exists("_dhh_ips")) {
  function _dhh_ips(): array {
    return [
      "185.127.111.252",    // HQ
      "31.201.36.137",      // HQ2
      "141.138.142.200",    // voyager
      "136.144.183.232",    // prod
    ];
  }
}

/* ---------------------------------------------------------- Chefstore\CsCache --------------------------------------------------------- */

/**
 * Load a cache item by key via the native OpenMage method.
 * Will hit 2-level cache and CacheStats.
 *
 * @param  string        $id  The cache key, WITHOUT prefixes.
 * @return string|false  The cached value, or false if not found.
 *
 * @see Mage_Core_Model_Cache::load() for more details.
 */
function omCacheGet(string $id): string|false {
  return CsCache::get($id);
}

/**
 * Save a cache by key via native OpenMage method.
 * Will hit 2-level cache and CacheStats.
 *
 * @param  mixed           $data
 * @param  string          $key
 * @param  array           $tags
 * @param  null|false|int  $lifetime
 *
 * @return true
 */
function omCacheSave(mixed $data, string $key, array $tags = [], null|false|int $lifetime = null): true {
  return CsCache::save($data, $key, $tags, $lifetime);
}

/**
 * Delete a cache by key via native OpenMage method.
 *
 * @param  string $id
 * @return true
 */
function omCacheDelete(string $id): true {
  return CsCache::delete($id);
}

/**
 * Clean cache by tags via native OpenMage method. Will hit 2-level cache and CacheStats.
 *
 * @param  array $tags
 * @return true
 */
function omCacheClean(array $tags = []): true {
  return CsCache::clean($tags);
}

/* ---------------------------------------------------------- Symfony\VarDumper --------------------------------------------------------- */

/**
 * OpenMage Dump -- Opinionated wrapper around Symfony VarDumper for OpenMage.
 *  - Switches between CliDumper and HtmlDumper based on the SAPI.
 *  - Limits the number of items, depth, and string length for better readability.
 *  - Does not support customization of the dump, it's a shorthand.
 *
 * @param  mixed ...$vars
 * @return void
 */
function omd(mixed ...$vars): void {
  static $cloner = null;
  if($cloner === null) {
    $cloner = new VarCloner();
    $cloner->setMaxItems(50);
    $cloner->setMinDepth(3);
    $cloner->setMaxString(500);
  }
  
  static $dumper = null;
  if($dumper === null) {
    if(!is_cli()) {
      $dumper = new HtmlDumper();
      $dumper->setDisplayOptions([
        'maxDepth'        => 3,
        'maxStringLength' => 250,
      ]);
    } else {
      $dumper = new CliDumper();
    }
  }
  
  $strings = [];
  foreach($vars as $var) {
    $strings[] = $dumper->dump($cloner->cloneVar($var), true);
  }
  
  if(PHP_SAPI !== "cli") {
    $separator = "<br>";
  } else {
    $separator = PHP_EOL.PHP_EOL;
  }
  
  echo implode($separator, $strings);
}

/* ----------------------------------------------------------- Laravel Picks ------------------------------------------------------------ */

/**
 * Checks if any of the values match any of the patterns.
 * 
 * Supports passing multiple values as an array or iterable. Case insensitive by default. Supports wildcards in patterns.
 *
 * @param  string|iterable<string>|float|int|null                        $pattern     The pattern to match against. Can be a
 *                                                                                    string or an array of strings.
 * 
 * @param  float|string|Stringable|null|iterable<string|Stringable>|bool $values      Can be a single value or an iterable
 *                                                                                    of values, one level deep. `mixed` to
 *                                                                                    allow for a variety of types.
 * 
 * @param  bool                                                          $ignoreCase  Defaults to TRUE
 *
 * @return bool                                                          TRUE if any value matches the pattern.
 */
if(!function_exists("sis")) {
  function sis(string|iterable|float|int|null $pattern, float|string|Stringable|null|array|bool $values, bool $ignoreCase = true): bool {
    if($values === null || $pattern === null || is_bool($values)) {
      return false;
    }
    if(!is_iterable($values)) {
      $values = [$values];
    }
    
    $pattern = array_map(fn($p) => strval($p), is_iterable($pattern) ? (array) $pattern : [$pattern]);
    
    foreach($values as $value) {
      if($value instanceof Stringable) {
        $value = $value->toString();
      }
      
      if(Str::is($pattern, $value, $ignoreCase)) {
        return true;
      }
    }
    
    return false;
  }
}

/**
 * Checks if any of the values match any of the patterns, always wrapping all patterns in wildcards.
 *
 * @param  string|iterable<string>                             $pattern     The pattern to match against. Can be a string or an array of strings.
 * @param  float|string|Stringable|null|iterable<string>|bool  $values      Can be a single value or an iterable of values, one level deep. `mixed` to allow for a variety of types.
 * @param  bool                                                $ignoreCase  Defaults to TRUE
 * 
 * @return bool
 */
if(!function_exists("wild_sis")) {
  function wild_sis(string|iterable $patterns, float|string|Stringable|null|array $values, bool $ignoreCase = true): bool {
    if($values === null) {
      return false;
    }
    if(is_bool($values)) {
      return false;
    }
    if(!is_iterable($values)) {
      $values = [$values];
    }
    
    $values = Arr::map($values, fn($v) => strval($v));
    
    if(!is_iterable($patterns)) {
      $patterns = [$patterns];
    }
    
    $patterns = Arr::map($patterns, fn($p) => Str::wrap($p, "*"));
    
    foreach($values as $value) {
      if($value instanceof Stringable) {
        $value = $value->toString();
      }
      
      if(Str::is($patterns, $value, $ignoreCase)) {
        return true;
      }
    }
    
    return false;
  }
}

/**
 * Measure the execution time of a callback in milliseconds.
 *
 * @param   Closure       $callback
 * @param   float|null   &$millis
 *
 * @return  mixed
 */
function millis(Closure $callback, ?float &$millis = null): mixed {
  [$result, $millis] = LaravelBenchmark::value($callback);
  $millis = round($millis, 2);
  return $result;
}

/* ------------------------------------------------------------ Intel Picks ------------------------------------------------------------- */

if(!function_exists('printr')) {
  /**
   * 
   *
   * @param  mixed       $expr
   * @param  bool        $return
   * @return string|null
   */
  function printr($expr, $return = false): string|null {
    $ret = null;
    if(is_array($expr) && !count($expr)) {
      return null;
    }
    if(php_sapi_name() !== "cli") {
      $ret .= "<pre style='white-space: pre-wrap; word-wrap:break-word;'>";
    }
    $ret .= print_r($expr, true);
    if(php_sapi_name() !== "cli") {
      $ret .= "</pre>";
    }
    $ret .= PHP_EOL;
    if($return) {
      return $ret;
    }
    echo $ret;
    return null;
  }
}

/**
 * Wrapper for \cli\Table() to print an array as a table.
 *
 * @param  array|object $data
 * @param  boolean      $return
 * @param  array        $render_options
 * @param  int          $col_wrap
 *
 * @return string|null
 */
if(!function_exists("array_to_table")) {
  function array_to_table(array|object $data, bool $return = true, array $render_options = [], int $col_wrap = 60): string|null {
    if(blank($data)) {
      return null;
    }
    
    $table = new \cli\Table($data);
    
    if(!$return) {
      $table->display();
      echo PHP_EOL;
      return null;
    }
    
    return implode("\n", $table->getDisplayLines()).PHP_EOL;
  }
}

/**
 * Tiny dumps a variable in a condensed format.
 *
 * @see https://github.com/brick/varexporter
 *
 * @param  mixed    $input
 * @param  boolean  $return
 * @param  boolean  $inline
 *
 * @return string|null
 */
if(!function_exists("td")) {
  function td(mixed $input, bool $return = false, bool $inline = false): string|null {
    $options = $inline ?
      (VarExporter::INLINE_ARRAY | VarExporter::INLINE_LITERAL_LIST)
      : VarExporter::INLINE_LITERAL_LIST; // Was: VarExporter::INLINE_SCALAR_LIST
    try {
      $var = Str::swap([
        "',\n"    => "\",\n",
        "  '"     => "  \"",
        "', '"    => "\", \"",
        "' => '"  => "\" => \"",
        "' => "   => "\" => ",
        " => '"   => " => \"",
        "['"      => "[\"",
        "']"      => "\"]",
        "null"    => "NULL",
        ], VarExporter::export($input, $options, indentLevel: 0));
    } catch (ExportException $e) {
      logger("Failed to TinyDump: {$e->getMessage()}", "ERROR");
      return null;
    }
    
    // Replace escaped newlines with actual newlines for better readability.
    $var = str_replace("\\n", "\n", $var);
    
    // Replace 4 spaces with 2 for better readability in the console. -- Might need tweaking based on the output of VarExporter.
    $var = str_replace("    ", "  ", $var);
    
    if($return) {
      return $var;
    }
    printr($var);
    
    return null;
  }
}

/**
 * Return compact dump inline as a string. Supports direct values or a key to mimic data_*() functions.
 *
 * @param mixed        $input    The variable to dump, or an array/object to get the value from via $key
 * @param string|null  $key      Optional key to get from $input if it's an array or ArrayAccess, supports dot notation
 * @param mixed        $default  Optional value if the key is not found, defaults to NULL
 * 
 * @return string
 */
if(!function_exists("di")) {
  function di(mixed $input, ?string $key = null, mixed $default = null): string {
    if(filled($key) && ($input instanceof ArrayAccess || is_array($input))) {
      $input = dg($input, $key, $default);
    }
    return td($input, true, true);
  }
}

/* ---------------------------------------------------------- Utils ----------------------------------------------------------- */

if(!function_exists("_get_product_attribute")) {
  /**
   * Product attributes only.
   * @deprecated use om_attr_val() instead.
   *
   * @todo Add null coalescing variant of this function.
   * @todo look at _selectAttributes
   * @todo look at Mage_Catalog_Helper_Output::getAttributeValue()
   * @todo Investigate usage of $implode_arrays and perhaps update om_attr_val() to support it as well.
   *
   * @param  Mage_Catalog_Model_Product|null $_product
   * @param  string                          $attribute_code
   * @param  bool                            $implode_arrays  Whether to implode array values into a comma-separated string. Defaults to TRUE.
   *
   * @return mixed
   */
  function _get_product_attribute(Mage_Catalog_Model_Product|null $_product, string $attribute_code, bool $implode_arrays = true): mixed {
    if(!is_object($_product)) {
      return null;
    }
    
    // Check if the product has data for the attribute code.
    // @todo Turn on logging and investiage. Can we actually tell that we're trying to access an attribute that doesn't exist or wasn't loaded?
    if(!$_product->hasData($attribute_code)) {
      $caller = whoCalledMe(3); // Skip 3 to go up 1 from here
      // Mage::log("_get_product_attribute: Product does not have data for attribute '{$attribute_code}'. Caller: ".di($caller), Zend_Log::WARN);
      return null;
    }
    
    $attribute = $_product->getResource()->getAttribute($attribute_code);
    if(!$attribute) {
      $caller = whoCalledMe(3); // Skip 3 to go up 1 from here
      Mage::log("_get_product_attribute: Attribute '{$attribute_code}' does not exist. Caller: ".di($caller), Zend_Log::WARN);
      return null;
    }
    
    $value = $_product->getResource()->getAttribute($attribute_code)->getFrontend()->getValue($_product);
    if($implode_arrays && is_array($value)) {
      $value = implode(", ", $value);
    }
    
    return $value;
  }
}

if(!function_exists("sanitizeForFilename")) {
  /**
   * Sanitize a string to be safe for use as a filename.
   * @deprecated Import Intel's cleanString() and use that, or URL slug, or...
   *
   * @param  mixed $string
   * @return string
   */
  function sanitizeForFilename($string): string {
    // Remove anything which is not a word, whitespace, number
    // or any of the following caracters -_~,;[]().
    // If you do not need to handle multi-byte characters
    // you can use preg_replace rather than mb_ereg_replace
    // Thanks @Łukasz Rysiak!
    $output = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", "", (string) $string);
    // Remove any runs of periods (thanks falstro!)
    $output = mb_ereg_replace("([\.]{2,})", "", (string) $string);
    return strtolower((string) $output);
  }
}

if(!function_exists("sanitize_alphanumeric")) {
  /**
   * @deprecated Import Intel's cleanString() and use that.
   *
   * @param  mixed  $string
   * @param  string $replacement
   * @return string
   */
  function sanitize_alphanumeric($string, string $replacement = "-"): string {
    $string = strtolower((string) preg_replace("/[^a-zA-Z0-9]+/", $replacement, (string) $string));
    return $string;
  }
}

if(!function_exists("_dhh_debug")) {
  /**
   * Check if debugging is enabled for the current user/IP/_ENV.
   * @return bool
   */
  function _dhh_debug(): bool {
    static $result = null;
    $result ??= isset($_ENV["DHH_DEBUG_ENABLED"]) || (isset($_GET["nofpc"]) && isDevIp());
    return $result;
  }
}

/**
 * Regardless of Apache config and used modules, get the client IP through the Cloudflare proxy headers.
 *
 * @return string|null
 */
function dhhEffectiveIp(): string|null {
  return $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? null;
}

/**
 * Check if the current user/IP is a DHH developer IP. For allowing debug dumps and logs in production.
 *
 * @return bool
 */
function isDevIp(): bool {
  static $result = null;
  if($result !== null) {
    return $result;
  }
  
  $effective_ip = dhhEffectiveIp();
  $result = isset($effective_ip) && in_array($effective_ip, _dhh_ips(), true);
  return $result;
}

if(!function_exists("_dhh_reflect")) {
  /**
   * Reflect a function or method to get its file and line number.
   *
   * @param  string      $function  The function name
   * @param  string|null $class     The class name (optional)
   *
   * @return array|false            Array with "file" and "line" keys, or FALSE on failure
   */
  function _dhh_reflect($function, $class = null): array|false {
    if($class === null) {
      if($r = new ReflectionFunction($function)) {
        return [
          "file"  => $r->getFileName(),
          "line"  => $r->getStartLine(),
        ];
      }
    } else {
      if($class = new ReflectionClass($class)) {
        if($r = $class->getMethod($function)) {
          return [
            "file"  => $r->getFileName(),
            "line"  => $r->getStartLine(),
          ];
        }
      }
    }
    return false;
  }
}

/**
 * Get the SQL SELECT statement from a collection.
 *
 * @param  Mage_Core_Model_Resource_Db_Collection_Abstract  $collection
 * @return string
 */
function _dhh_getselect(Mage_Core_Model_Resource_Db_Collection_Abstract $collection): string {
  return $collection->getSelect()->__toString();
}

/**
 * Check if a number is in a given range.
 *
 * @param  float|int  $number
 * @param  float|int  $min
 * @param  float|int  $max
 * @param  bool       $inclusive
 *
 * @return bool
 */
function in_range(float|int $number, float|int $min, float|int $max, bool $inclusive = false): bool {
  if(is_numeric($number) && is_numeric($min) && is_numeric($max)) {
    return $inclusive
      ? ($number >= $min && $number <= $max)
      : ($number >= $min && $number < $max) ;
  }
  return false;
}

/**
 * Dump a variable for development purposes in a production system.
 * Check if the IP is a DHH IP, check if nofpc is set and dump the variable in a HTML comment.
 *
 * @param   mixed  $var
 * @return  void
 */
function devDump(mixed ...$var): void {
  if(_dhh_debug()) {
    echo "<!-- DHH: ".var_export($var, true)." -->".PHP_EOL;
  }
}

/**
 * Write a log while in production. Checks if the IP is a DHH IP and log the message to verbose.txt.
 * > Does not require ?nofpc.
 *
 * @param  array|object|string  $message   The message to log
 * @param  ?int                 $level     Log level, defaults to Zend_Log::DEBUG
 * @param  string|null          $file      Log file name, defaults to "verbose.txt". Logging to *.log is discouraged as it will be picked up by monitoring.
 * @param  bool                 $forceLog  Whether to force logging even if logging is disabled in Magento configuration (default: TRUE)
 *
 * @return void
 */
function devLog(string $message, int $level = null, ?string $file = "verbose.txt", bool $forceLog = true): void {
  if(isDevIp()) {
    $level ??= Zend_Log::DEBUG;
    Mage::log($message, $level, $file, $forceLog);
  }
}

if(!function_exists("_getAlternativeEans")) {
  /**
   * Given an EAN, return an array of alternative EANs by adding/removing leading zeros.
   *
   * @param  string|int $ean
   * @return array
   */
  function _getAlternativeEans(string|int|null $ean): array {
    if($ean === null) {
      return [];
    }
    $eans = (array) $ean;
    if(strlen((string) $ean) === 13) {
      $eans[] = sprintf("%014d", $ean);
    }
    if(str_starts_with((string) $ean, "0")) {
      $eans[] = substr((string) $ean, 1);
    }
    if(str_starts_with((string) $ean, "00")) {
      $eans[] = substr((string) $ean, 2);
    }
    if(str_starts_with((string) $ean, "000")) {
      $eans[] = substr((string) $ean, 3);
    }
    
    return $eans;
  }
}

/**
 * Get the URL of a product image, given the value of $row->getData("image") or similar.
 *
 * Example:
 * - Input:  "i/m/image.jpg"
 * - Output: "https://yourstore.com/media/catalog/product/i/m/image.jpg"
 *
 * @param  mixed  $path
 * @return string
 */
function omGetProductImageUrl($path): string {
  return rtrim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA), "/")."/catalog/product/".ltrim($path, "/");
}

if(!function_exists("_cdn_img")) {
  /**
   * Generates an <img> tag or just the URL for an image served via a CDN with specified transformations.
   *
   * Exists in OpenMage and Intel, keep aligned:
   * - openmage/lib/Chefstore/functions.php
   * - intel/lib/intel.inc.php
   *
   * @todo Add support for loading=eager, fetchpriority=high. Change "lazy" to "loading" with options "lazy", "eager", "auto".
   * @todo Support (bool) "lazy" for backward compatibility. If both "lazy" and "loading" are set, "loading" takes precedence. Log a NOTICE to Mage::log() for openmage $context, or logger() otherwise
   * @todo Support fetchpriority attribute. Default to "auto" which falls back to the "lazy" attribute and derives an appropriate value.
   * @todo If no fetchpriority and no loading attribute are given, do not add any attribute to the HTML.
   * @todo If $url_only is true, forego creating HTML output
   *
   * HTML generation options:
   * ----------------------------------------------------------------------------------------------------------------------------
   * "url"           : (string)    The base URL of the image. (required)
   * "identifier"    : (string)    An identifier used for cache busting.
   * "cdn"           : (string)    The CDN to use. Options: "none", "imagekit", "imagekit_custom". Default: "imagekit_custom"
   * "fs_path"       : (string)    The filesystem path to the image, used for adding modification time as cache buster.
   * "add_mod_time"  : (bool)      Whether to add the file modification time as a cache buster. Default: false.
   * "alt"           : (string)    The alt text for the image. Default: the URL.
   * "id"            : (string)    The id attribute for the <img> tag.
   * "format"        : (string)    The desired image format. Default: "auto".
   * "url_only"      : (bool)      If true, only the URL is returned instead of an <img> tag. Default: false.
   * "relative_url"  : (bool)      If true, the base URL (domain name) is removed from the image URL. Default: false.
   * "2x"            : (bool)      Whether to include a 2x resolution image in the srcset attributes
   * "lazy"          : (bool)      Whether to add loading="lazy" attribute. Default: false.
   * "fetchpriority" : (string)    The fetchpriority attribute value. Options: "high", "low", "auto". Default: "auto". Only "high" and "low" are added to the HTML.
   * "class"         : (string)    Additional CSS classes for the <img>
   * "style"         : (string)    Additional inline styles for the <img>
   *
   * ImageKit URL options:
   * ----------------------------------------------------------------------------------------------------
   * "xform"         : (string)    Named transformation to use (overrides other ImageKit URL options)
   * "width"         : (int)       The desired width of the image.
   * "height"        : (int)       The desired height of the image.
   * "fit"           : (string)    How the image should fit within the specified dimensions.
   *                               Options: "scale-down", "contain", "scale-up". Default: "scale-down".
   * "quality"       : (int)       The desired image quality (1-100). Default: 75.
   *
   * Named transformations are preferred at all times, for higher cache hit rates in CDN and ImageKit and browsers.
   * Named transformations can be defined in the ImageKit dashboard, and are defined in this function as well.
   *
   * @param array $options
   * @return string|null
   */
  function _cdn_img(array $options): string|null {
    $url = htmlspecialchars((string) ($options["url"] ?? ""));
    
    if(blank($url)) {
      return null;
    }
    
    $identifier     = $options["identifier"] ?? "NO_ID";
    $fs_path        = dyAsNullStr($options, "fs_path", null);
    $add_mod_time   = $options["add_mod_time"]  ?? false; // Requires fs_path
    $width          = $options["width"]         ?? null;
    $height         = $options["height"]        ?? null;
    $alt            = $options["alt"]           ?? $url;
    $id             = $options["id"]            ?? "";
    $fit            = $options["fit"]           ?? "scale-down";
    $format         = $options["format"]        ?? "auto";
    $quality        = $options["quality"]       ?? 75;
    $url_only       = $options["url_only"]      ?? false;
    $relative_url   = $options["relative_url"]  ?? false; // Remove the base url (domain name) from the image url
    $include_2x     = $options["2x"]            ?? false; // Should not be needed if we send the "Dpr" header
    $lazy           = $options["lazy"]          ?? null;
    $fetchpriority  = dyAsStr($options, "fetchpriority") ?? "auto";
    $class          = dyAsStr($options, "class");
    $style          = $options["style"]         ?? "";
    $namedXform     = dyAsNullStr($options, "xform", null);
    $seo            = dyAsNullStr($options, "seo", null);
    
    $cdn            = (string)  dg($options, "cdn", "imagekit");
    $alt            = (string)  $alt;
    $id             = (string)  $id;
    $fit            = (string)  $fit;
    $format         = (string)  $format;
    $quality        = (int)     $quality;
    $url_only       = (bool)    $url_only;
    $relative_url   = (bool)    $relative_url;
    $include_2x     = (bool)    $include_2x;
    $lazy           = ($lazy !== null) ? (bool) $lazy : null;
    $class          = (string)  $class;
    $style          = (string)  $style;
    $id_html        = "";
    $lazy_html      = "";
    $class_html     = "";
    $style_html     = "";
    $context        = defined("APP_SHORT") ? "intel" : "openmage"; // intel|openmage
    
    // Process settings
    if($cdn === "imagekit" || $cdn === "imagekit_custom") {
      $relative_url = true; // Required
    }
    
    // Add file modification time as cache buster
    if($add_mod_time) {
      // Fill fs_path when cachebusting is enabled without an explicit fs_path -- BEFORE adding cache buster
      if(blank($fs_path)) {
        $fs_path ??= CacheBuster::pathByUrl($url);
      }
      
      // Add modification time as cache buster
      if(filled($fs_path)) {
        if(is_file($fs_path) && $mtime = filemtime($fs_path)) {
          $url = CacheBuster::prependExtension($url, "ts{$mtime}");
        } else {
          // @todo Remove this, stop adding query params for cache busting and use CacheBuster::prependExtension() instead
          // @todo Generally, reduce is_file() calls on networked filesystems in non-admin/non-cron (so user facing online) runs.
          $url = _add_file_v_param($url, $fs_path, $identifier);
        }
      }
    }
    
    // Make URL relative
    if($relative_url) {
      $url = str_replace(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB), "", $url);
    }
    
    // URL modifications based on CDN
    switch($cdn) {
      // @see https://docs.imagekit.io/features/image-transformations
      case "imagekit":
      case "imagekit_custom":
        $cdn_base = "https://images.chefstore.nl"; // Remove "http://" only at the very last to not hinder pathinfo()
        
        // A named transformation takes precedence over individual options
        if(filled($namedXform)) {
          $cdn_options_string = "tr:n-{$namedXform}";
        } else {
          $cdn_options = ["w" => $width, "h" => $height];
          if($quality > 0) $cdn_options["q"] = $quality;
          if($fit === "contain" || $fit === "scale-down") {
            $cdn_options["c"] = "at_max";                     // max-size crop
          }
          if($fit === "scale-up") {
            $cdn_options["c"] = "at_max_enlarge";             // max-size crop
          }
          $cdn_options_string = "tr:".implode_array_with_keys($cdn_options, ",", "-");
        }
        $url      = str_ireplace(["https://www.chefstore.nl/"], "", $url); // url comes in as "https://www.chefstore.nl/media/..."
        $src_url  = "{$cdn_base}/{$cdn_options_string}/{$url}";
        
        // Add SEO part to filename -- AFTER building the CDN URL
        // Add "ik-seo" prefix of the path. Use the filename as a directory postfix. Use the SEO tag as the new filename. Keep the extension.
        // Example:
        // SEO tag: buffaflo-ad446-motor
        // Input:   https://www.chefstore.nl/media/catalog/product/g/a/gastronoble-AD446-00-b-786e/buffaflo-ad446-motor.jpg
        // Output:  https://images.chefstore.nl/ik-seo/tr:n-omcatprdlstfr/media/catalog/product/g/a/gastronoble-AD446-00-b-786e/buffaflo-ad446-motor.jpg
        if(filled($seo)) {
          $seo            = Str::slug($seo, "-");
          $uri            = Uri::of($src_url);
          $filenameNoExt  = pathinfo($uri->path(), PATHINFO_FILENAME);
          $path           = $uri->pathSegments()->prepend("ik-seo")->slice(0, -1)->push($filenameNoExt)->all();
          $dirname        = implode("/", $path);
          $extension      = pathinfo($src_url, PATHINFO_EXTENSION);
          $src_url        = "{$cdn_base}/{$dirname}/{$seo}.{$extension}";
        }
        break;
    }
    
    // Return just the URL when requested, don't spend time on HTML generation
    if($url_only && isset($src_url)) {
      return $src_url;
    }
    
    // Lookup the named transformation definition to take decisions on output HTML
    // @see https://docs.imagekit.io/features/image-transformations
    $namedXformDefinition = match($namedXform) {
      "ik_ml_thumbnail" => "tr:w-440,h-440,fo-center,cm-pad_resize",            // ImageKit ML thumbnail, immutable.
      "omcatprdlstfr"   => "tr:w-125,h-125,q-80,cm-pad_resize,dpr-auto",        // OpenMage catalog product list page (frontend) + Spotler Search results
      "omcatprdlst"     => "tr:w-200,h-200,q-80,c-at_max_enlarge,dpr-auto",     // OpenMage catalog product list page (backend)
      "omcatctglst"     => "tr:w-140,h-140,q-80,c-at_max_enlarge,dpr-auto",     // OpenMage catalog category list page
      "omcatprddtlt"    => "tr:w-172,h-400,q-80,c-at_max_enlarge,dpr-auto",     // OpenMage catalog product detail page (thumbnail)
      "omcatprddtlf"    => "tr:w-2048,h-2048,q-80,c-at_max_enlarge,dpr-auto",   // OpenMage catalog product detail page (full)
      "omcatprddtlh"    => "tr:w-1200,h-1200,q-80,c-at_max_enlarge,dpr-auto",   // OpenMage catalog product detail page (half)
      "ombrndlgos"      => "tr:w-140,h-40,cm-at_max_enlarge,dpr-auto",          // OpenMage brand logos
      "omexfull"        => "tr:w-1536,h-1536,q-80,c-at_max_enlarge",            // OpenMage example full size
      "logosmall"       => "tr:w-210,h-60,c-at_max_enlarge,dpr-auto,dpr-auto",  // Small logos
      default           => "",
    };
    
    // Adjust HTML attributes based on settings
    
    $fetchpriority = strtolower($fetchpriority);
    if($fetchpriority === "auto") {
      $fetchpriority = $lazy ? "low" : "auto";
    }
    
    if(str_contains($namedXformDefinition, "c-at_max_enlarge") || str_contains($namedXformDefinition, "cm-pad_resize")) {
      $fit = "scale-up";
    } elseif(str_contains($namedXformDefinition, "c-at_max")) {
      $fit = "contain";
    }
    if($lazy) {
      $lazy_html = " loading=\"lazy\"";
    }
    if($fetchpriority === "high" || $fetchpriority === "low") {
      $lazy_html .= " fetchpriority=\"{$fetchpriority}\"";
    }
    if(filled($id)) {
      $id_html = " id=\"{$id}\"";
    }
    if($fit === "contain" || $fit === "scale-down" || $fit === "scale-up") {
      $class .= " object-fit-contain";
    }
    if(filled($class)) {
      $class_html = " class=\"{$class}\"";
    }
    if(filled($style)) {
      $style_html .= " style=\"{$style}\"";
    }
    
    $widthAttr  = ($width > 0) ? "width=\"{$width}\"" : "";
    $heightAttr = ($height > 0) ? "height=\"{$height}\"" : "";
    
    // For IDE analyzers:
    $cdn_options ??= [];
    $src_url ??= $url;
    
    switch($cdn) {
      case "none": return "<img src=\"{$url}\" {$widthAttr} {$heightAttr} alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
      
      case "imagekit":
      case "imagekit_custom":
        $cdn_base = "//images.chefstore.nl";
        // Use 2x the resolution when no named transformation is used
        if(blank($namedXform) && $include_2x && is_numeric($cdn_options["w"]) && is_numeric($cdn_options["h"])) {
          $cdn_options["w"]   *= 2;
          $cdn_options["h"]   *= 2;
          $cdn_options_string = implode_array_with_keys($cdn_options, ",", "-");
          $src_url_2x         = "{$cdn_base}/{$cdn_options_string}/{$url}";
          $srcset             = "srcset=\"{$src_url_2x} 2x\" ";
          $srcsetAttr         = "srcset=\"{$srcset}\"";
        } else {
          $srcsetAttr = "";
        }
        
        return "<img src=\"{$src_url}\" {$srcsetAttr} {$widthAttr} {$heightAttr} alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
    }
    
    Mage::log("_cdn_img: Unknown CDN type '{$cdn}' for identifier '{$identifier}'", Zend_Log::ERR, "exception.log", true);
    
    return null;
  }
}

if(!function_exists("_add_file_v_param")) {
  /**
   * Adds a ?v={timestamp} param to a URL. Exists in OpenMage and Intel.
   * @todo Remove from intel, use OpenMage's function directly.
   *
   * @param  string  $url
   * @param  string  $fs_path
   * @param  string  $identifier
   *
   * @return string
   */
  function _add_file_v_param(string $url, string $fs_path, string $identifier): string {
    if(is_file($fs_path)) {
      if($mod_time = filemtime($fs_path)) {
        $url .= (parse_url($url, PHP_URL_QUERY) ? "&" : "?") . "v={$mod_time}";
      }
    } else {
      if(function_exists("logger")) {
        logger("{$identifier} Cannot add file modification time of {$fs_path} because it does not exist", "NOTICE");
      } else {
        Mage::log("{$identifier} Cannot add file modification time of {$fs_path} because it does not exist");
      }
    }
    
    return $url;
  }
}

if(!function_exists("implode_array_with_keys")) {
  /**
   * Implode an array into a string with both keys and values, with customizable separators.
   * Example: implode_array_with_keys(["w" => 100, "h" => 200], ",", "-") returns "w-100,h-200"
   *
   * @param  array   $array      The array to implode
   * @param  string  $separator  The separator between key-value pairs (default: ", ")
   * @param  string  $glue       The glue between keys and values (default: "=")
   *
   * @return string
   */
  function implode_array_with_keys($array, $separator = ", ", $glue = "="): string {
    $ret = "";
    foreach($array as $key => $val) {
      $ret .= $key.$glue.$val.$separator;
    }
    $ret = substr($ret, 0, -(strlen((string) $separator)));
    return $ret;
  }
}

if(!function_exists("add_url_param")) {
  /**
   * Add a parameter to a URL, correctly handling existing query parameters.
   * @see // https://stackoverflow.com/questions/5809774/manipulate-a-url-string-by-adding-get-parameters
   *
   * @param  string  $url   The original URL
   * @param  string  $key   The parameter name to add
   * @param  string  $value The parameter value to add
   *
   * @return string  The modified URL with the new parameter
   */
  function add_url_param(string $url, string $key, string $value): string {
    $url .= (parse_url($url, PHP_URL_QUERY) ? "&" : "?") . "{$key}={$value}";
    return $url;
  }
}

if(!function_exists("remove_url_param")) {
  /**
   * Remove one or more parameters from a URL. Parameters should be given as an array of parameter names, or a single parameter name as a string.
   *
   * @param  string        $url     The URL to remove parameters from
   * @param  iterable|null $params  A single parameter name or an array of parameter names to remove from the URL
   * @return string
   */
  function remove_url_param(string $url, iterable|null $params): string {
    $params     = (array) $params;
    $base_url   = strtok($url, "?");             // Get the base url
    $parsed_url = parse_url($url);              // Parse it
    $query      = $parsed_url["query"];         // Get the query string
    parse_str($query, $parameters);             // Convert Parameters into array
    foreach($params as $param) {
      if(isset($parameters[$param])) {
        unset($parameters[$param]);             // Delete the one you want
      }
    }
    $new_query = http_build_query($parameters); // Rebuilt query string
    return "{$base_url}?{$new_query}";          // Finally url is ready
  }
}

if(!function_exists("xml_string_to_array")) {
    /**
    * Convert an XML string to an associative array. Returns FALSE if the string is not valid XML or if JSON encoding/decoding fails.
    *
    * @param  string       $string  The XML string to convert
    * @return array|false  The resulting associative array, or FALSE on failure
    */
  function xml_string_to_array(string $string): array|false {
    if(($object = simplexml_load_string($string)) !== false) {
      try {
        $json = json_encode($object, 0, 512);
      } catch (Exception $e) {
        Mage::log("xml_string_to_array: JSON encoding failed. {$e->__toString()}", null, "exception.log", true);
        return false;
      }
      try {
        $array = json_decode($json, $associative = true, $depth = 512, JSON_THROW_ON_ERROR);
        return $array;
      } catch (Exception $e) {
        Mage::log("xml_string_to_array: JSON decoding failed. {$e->__toString()}", null, "exception.log", true);
        return false;
      }
    } else {
      Mage::log(var_export($string, true), null, "exception.log", true);
      Mage::log("xml_string_to_array: Invalid XML string given", null, "exception.log", true);
      return false;
    }
  }
}

/**
 * Get the current quote ID, or "NO_QUOTE_ID" if not available.
 * 
 * @return string
 */
if(!function_exists("dhh_get_quote_id")) {
  function dhh_get_quote_id(): string {
    if(isset($_SESSION) && $_SESSION !== null) {
      if(!empty($GLOBALS["dhh_current_quote_id"])) {
        return $GLOBALS["dhh_current_quote_id"];
      }
      $id = (string) (Mage::getSingleton("checkout/session")?->getQuote()?->getId() ?? "");
      if(!empty($id)) {
        $GLOBALS["dhh_current_quote_id"] = $id;
        return $GLOBALS["dhh_current_quote_id"];
      }
    }
    
    return "NO_QUOTE_ID";
  }
}

/**
 * Alias for OpenMage's getCurrentUrl(), but decoded.
 * 
 * @return string
 */
function getDecodedCurrentUrl(): string {
  return omDecodeUrl(Mage::helper("core/url")->getCurrentUrl());
}

/**
 * OpenMage's getCurrentURL() returns it encoded. This function reverses it.
 * > It still retains &amp; and %2C etc.
 * 
 * @param   string  $url
 * @return  string
 */
function omDecodeUrl(string $url): string {
  return htmlspecialchars_decode($url, ENT_COMPAT | ENT_HTML5 | ENT_HTML401);
}

if(!function_exists("fastHash")) {
  /**
   * Generate a fast hash, using xxHash algorithm. Not cryptographically secure.
   * > Note: Only one of [seed, secret] can be used at a time.
   *
   * @param  string|Stringable  $input  The input string.
   * @param  string|null        $secret Optional secret key (32 bytes). If null, a default secret is used.
   *
   * @return string             The generated fast hash (32 bytes).
   */
  function fastHash(string|Stringable $input, ?string $secret = null): string {
    static $secret = "d9057eeb452a94261937c511274201be20c11f56bff1bd2c592ff37481d7a221cf7cad9859fe5f05d5bfc05d1e8d94132f6a1c783f891edd0f43206bc54f84f30415eaff1a";
    $options = ["secret" => $secret];
    return hash("xxh128", (string) $input, options: $options);
  }
}

/**
 * Check critical PHP settings against the current state. Log at WARNING level when this run approached within 20%.
 * @return void
 */
function omCheckCriticalPhpSettings(): void {
  // Do not run when embedded in Intel or Tools
  if(defined("APP_SHORT")) {
    return;
  }
  
  $critical_settings = [
    "memory_limit" => [
      "current" => memory_get_peak_usage(),
      "limit"   => ini_parse_quantity(strtolower(ini_get("memory_limit"))), // ini_parse_quantity() requires lowercase input
    ],
    "max_execution_time" => [
      "current" => round((hrtime(true) - START_HRTIME) / 1000000000),
      "limit"   => ini_get("max_execution_time"),
    ],
  ];
  
  foreach($critical_settings as $setting => $values) {
    $current  = $values["current"];
    $limit    = $setting === "memory_limit" ? humanReadableSizeToBytes($values["limit"]) : (int) $values["limit"];
    $unit     = $setting === "memory_limit" ? "bytes" : "seconds";
    
    // A time limit of 0 is fine, or memory limit of -1
    if($limit > 0) {
      if($current >= 0.8 * $limit) {
        Mage::log("Approaching {$setting} limit: current usage is {$current} {$unit}, limit is {$limit} {$unit}", Zend_Log::WARN);
      } else {
        // Mage::log("{$setting} usage is within limits: current usage is {$current} {$unit}, limit is {$limit} {$unit}", Zend_Log::INFO);
      }
    }
  }
}

/**
 * Convert a human-readable file size (e.g., "10K", "5M") to bytes.
 *
 * @param   string|int $value
 * @return  int
 */
if(!function_exists("humanReadableSizeToBytes")) {
  function humanReadableSizeToBytes(string|int $value): int {
    $matches = [];
    \preg_match('/^\s*(?P<number>\d+)\s*(?:(?P<prefix>[kmgt]?)b?)?\s*$/i', (string) $value, $matches);
    $bytes = \intval($matches['number'] ?? 0);
    $prefix = \strtolower(\strval($matches['prefix'] ?? ''));
    switch ($prefix) {
        case 't': $bytes *= 1024;
        // no break
        case 'g': $bytes *= 1024;
        // no break
        case 'm': $bytes *= 1024;
        // no break
        case 'k': $bytes *= 1024;
    }
    
    return $bytes;
  };
}

if(!function_exists("msleep")) {
  /**
   * Sleep for a given time in milliseconds.
   *
   * @param  int  $time
   * @return void
   */
  function msleep(int $time): void {
    usleep($time * 1000);
  }
}

/* ---------------------------------------------------------- OpenMage Helpers ---------------------------------------------------------- */

/**
 * @see Mage_Core_Helper_Abstract
 * => No need for function_exists(): If Intel wants to use these, it should define them itself.
 */

/**
 * Escape quotes inside html attributes.
 * Uses $addSlashes = FALSE by default for escaping JavaScript inside HTML attribute (onClick, onSubmit etc).
 *
 * @param  string  $string      The string
 * @param  bool    $addSlashes  Whether to add slashes or not (default: FALSE)
 *
 * @return string
 */
function omHtmlAttrEscape(string $string, bool $addSlashes = false): string {
  return Mage::helper("core")->quoteEscape($string, $addSlashes);
}

/**
 * Escape quotes for JavaScript.
 *
 * @param  string|string[]  $string  The string or array of strings
 * @return string|string[]
 */
function omJsQuoteEscape(string|array $string): string|array {
  return Mage::helper("core")->jsQuoteEscape($string);
}

/**
 * Escape HTML special characters.
 *
 * @param  string  $string  The string
 * @return string
 */
function omHtmlEscape(string $string): string {
  return Mage::helper("core")->escapeHtml($string);
}

/**
 * Strip newlines and extra spaces from a string.
 *
 * @param  string|null $string
 * @return string|null
 */
function omStrStripNewlines(?string $string): string|null {
  if(is_null($string)) return null;
  return str_replace(["\r\n", "\n", "\r"], " ", trim($string));
}

/**
 * Starts measuring time. Only one benchmark can be active at a time if internal storage is used.
 *
 * @param  bool            $store  Whether to store the start time internally (default: TRUE). If FALSE, the current hrtime() is returned.
 * @return null|float|int
 */
function omStartTimer(bool $store = true): null|float|int {
  return Observability::start($store);
}

/**
 * Stops measuring time and returns the elapsed time in formatted milliseconds since startTimer() was called, or null if startTimer() was not called.
 * Renamed from stopTimer() to avoid conflicts with Intel.
 *
 * @param  bool              $formatted  Whether to return formatted string (default: TRUE)
 * @param  float|int|null    $start      Optional start time to calculate elapsed time from. If null, uses the internally stored start time.
 *
 * @return string|float|null
 */
function omStopTimer(bool $formatted = true, float|int|null $start = null): string|float|null {
  return Observability::stop($formatted, $start);
}

if(!function_exists("whoCalledMe")) {
  /**
   * Print a backtrace from anywhere. Returns an array of callers.
   *
   * @param   int  $levels  The amount of levels to go back. Default 1 (the direct caller), 0 for the entire stack.
   * @return  array<array{file: string, line: int, function: string|null, class: string|null, type: string|null}>|false
   */
  function whoCalledMe(int $levels = 1): array|false {
    return Observability::whoCalledMe($levels);
  }
}

if(!function_exists("printWhoCalledMe")) {
  /**
   * Print a backtrace from anywhere.
   *
   * @param  int  $levels  The max amount of levels to go back.
   * @return void
   */
  function printWhoCalledMe(int $levels = 1): string {
    return Observability::printWhoCalledMe($levels);
  }
}

if(!function_exists("om_attr_val")) {
  /**
   * Retrieve a product attribute value.
   * => Don't make $_product an (object) type hint only, accept NULL
   *
   * @todo Add null coalescing variant of this function.
   * @todo look at _selectAttributes
   * @todo look at Mage_Catalog_Helper_Output::getAttributeValue()
   *
   * @param  ?Mage_Catalog_Model_Product  $_product
   * @param  string                       $attribute_code
   * @param  ?string                      $as              "" | "string" | "int"
   * @param  array                        $options         Unused
   *
   * @return mixed
   */
  function om_attr_val(?Mage_Catalog_Model_Product $_product, string $attribute_code, ?string $as = "", array $options = []): mixed {
    if(!is_object($_product)) {
      return null;
    }
    
    // Check if the product has data for the attribute code.
    // @todo Turn on logging and investiage. Can we actually tell that we're trying to access an attribute that doesn't exist or wasn't loaded?
    if(!$_product->hasData($attribute_code)) {
      $caller = whoCalledMe(3); // Skip 3 to go up 1 from here
      // Mage::log("_get_product_attribute: Product does not have data for attribute '{$attribute_code}'. Caller: ".di($caller), Zend_Log::WARN);
      return null;
    }
    
    if($_attribute = $_product->getResource()->getAttribute($attribute_code)) {
      $value = $_attribute->getFrontend()->getValue($_product);
      if(!blank($as)) {
        if($as === "array") {
          if(!is_scalar($value)) {
            return [$value];
          }
          return (array) $value;
        }
        if($as === "string") {
          if(is_iterable($value)) {
            return implode(", ", (array) $value);
          }
          return (string) $value;
        }
        if($as === "int") {
          return (int) $value;
        }
      }
      return $value;
    }
    $dhh_sku = $_product->getSku("dhh_sku") ?? "NO_DHH_SKU";  
    Mage::log("{$dhh_sku} Unknown attribute requested: {$attribute_code}", Zend_Log::NOTICE);
    
    return false;
  }
}

if(!function_exists("om_attr_val_as_array")) {
  /**
   * Get OM attribute as an array.
   * => Don't make $_product an (object) type hint only, accept NULL
   * 
   * @param  ?Mage_Catalog_Model_Product  $_product
   * @param  string                       $attribute_code
   * @param  array                        $options         Unused
   * 
   * @return array|null
   */
  function om_attr_val_as_array(?Mage_Catalog_Model_Product $_product, string $attribute_code, array $options = []): array|null {
    if($_product === null) {
      return [];
    }
    
    $_attribute = $_product->getResource()->getAttribute($attribute_code);
    if($_attribute->getFrontendInput() === "multiselect") {
      $frontend = $_attribute->getFrontend();
      $values   = explode(",", $_product->getData($attribute_code));
      $values   = Arr::map($values, fn($id) => $frontend->getOption($id));
      return $values;
    } else {
      $values = om_attr_val($_product, $attribute_code, "array");
    }
    $ret = !empty($values) ? (array) $values : [];  // empty(), not blank()
    
    return $ret;
  }
}

if(!function_exists("om_attr_val_as_string")) {
  /**
   * Get attribute value as string.
   * => Don't make $_product an (object) type hint only, accept NULL
   * 
   * @param  ?Mage_Catalog_Model_Product  $_product
   * @param  string                       $attribute_code
   * @param  ?string                      $as              "" | "string" | "int"
   * @param  array                        $options         Unused
   * 
   * @return int|null
   */
  function om_attr_val_as_string(?Mage_Catalog_Model_Product $_product, string $attribute_code, array $options = []): string {
    return (string) om_attr_val($_product, $attribute_code, "string");
  }
}

if(!function_exists("om_attr_val_as_float")) {
  /**
   * Get attribute value as float.
   * => Don't make $_product an (object) type hint only, accept NULL
   * 
   * @param  ?Mage_Catalog_Model_Product  $_product
   * @param  string                       $attribute_code
   * @param  ?string                      $as              "" | "string" | "int"
   * @param  array                        $options         Unused
   * 
   * @return int|null
   */
  function om_attr_val_as_float(?Mage_Catalog_Model_Product $_product, string $attribute_code, array $options = []): float|null {
    $value = om_attr_val($_product, $attribute_code);
    if(is_numeric($value)) {
      return float(round($value));
    }
    
    return null;
  }
}

if(!function_exists("om_attr_val_as_int")) {
  /**
   * Get attribute value as int.
   * => Don't make $_product an (object) type hint only, accept NULL
   * 
   * @param  ?Mage_Catalog_Model_Product  $_product
   * @param  string                       $attribute_code
   * @param  ?string                      $as              "" | "string" | "int"
   * @param  array                        $options         Unused
   * 
   * @return int|null
   */
  function om_attr_val_as_int(?Mage_Catalog_Model_Product $_product, string $attribute_code, array $options = []): int|null {
    $value = om_attr_val_as_float($_product, $attribute_code, $options);
    if(is_numeric($value)) {
      return int(round($value));
    }
    
    return null;
  }
}

/**
 * Get cacheable product object.
 * 
 * Built to tame qquote module.
 *
 * @param  integer|string|Mage_Catalog_Model_Product|null $product
 * @return Mage_Catalog_Model_Product|false|null
 */
function dhh_get_cached_om_product(int|string|Mage_Catalog_Model_Product|null $product): Mage_Catalog_Model_Product|false|null {
  // COMMENT THIS to enable the cache (untested)
  // return Mage::getModel('catalog/product')->load($product); // Run the original code
  
  $GLOBALS["dhh_get_cached_om_product"] ??= [];
  
  if($product === null) {
    // Mage::log("dhh_get_cached_om_product::NULL", Zend_Log::DEBUG, "verbose.txt", true);
    return null;
  }
  
  if($product instanceof Mage_Catalog_Model_Product) {
    // Mage::log("dhh_get_cached_om_product::{$product->getId()} called with existing Mage_Catalog_Model_Product ", Zend_Log::DEBUG, "verbose.txt", true);
    return $product;
  }
  
  $product_id = $product;
  
  if(!is_numeric($product_id) || intval($product_id) < 1) {
    // Mage::log("dhh_get_cached_om_product::".json_encode($product_id).": Not a numeric product_id", Zend_Log::DEBUG, "verbose.txt", true);
    return Mage::getModel('catalog/product')->load($product_id); // Run the original code
  }
  
  $product_id = (int) $product_id;
  
  if(!isset($GLOBALS["dhh_get_cached_om_product"][$product_id])) {
    // Mage::log("dhh_get_cached_om_product::{$product_id}, SAVE", Zend_Log::DEBUG, "verbose.txt", true);
    if($_product = Mage::getModel('catalog/product')->load($product_id)) {
      $GLOBALS["dhh_get_cached_om_product"][$product_id] = $_product;
      destruct($_product);
    }
  }
  
  // Mage::log("dhh_get_cached_om_product::".json_encode($product_id).", HIT", Zend_Log::DEBUG, "verbose.txt", true);
  
  return $GLOBALS["dhh_get_cached_om_product"][$product_id];
}

/**
 * Get cacheable category object.
 *
 * @param  integer|string                   $id
 * @param  boolean                          $forceRefresh
 * @return Mage_Catalog_Model_Category|null
 */
function dhh_get_cached_category(int|string $id, bool $forceRefresh = false): Mage_Catalog_Model_Category|null {
  $id         = (int) $id;
  $currentUrl = getDecodedCurrentUrl();
  $field      = null;
  
  static $modelcacheHelper = null;
  if($modelcacheHelper === null) {
    /** @var Aoe_ModelCache_Helper_Data $modelcacheHelper */
    $modelcacheHelper = Mage::helper("aoe_modelcache");
  }
  
  // Defensive: During high load and a cache clear, the model cache helper might not be available. 
  if($modelcacheHelper) {
    if(_dhh_debug()) {
      if(!$forceRefresh && $modelcacheHelper->exists("catalog/category", $id)) {
        Mage::log(__FUNCTION__."::{$id} HIT   [{$currentUrl}]", Zend_Log::DEBUG, "verbose.txt", true);
      } else {
        Mage::log(__FUNCTION__."::{$id} SAVE  [{$currentUrl}]", Zend_Log::DEBUG, "verbose.txt", true);
      }
    }
    
    return $modelcacheHelper->get("catalog/category", $id, $field, $forceRefresh);
  }
  
  // Fallback to a native OpenMage load.
  return Mage::getModel("catalog/category")->load($id);
}

/**
 * Get cacheable product object.
 *
 * @param  integer|string                  $id
 * @param  boolean                         $forceRefresh
 * @return Mage_Catalog_Model_Product|null
 */
function dhh_get_cached_product(int|string $id, bool $forceRefresh = false): Mage_Catalog_Model_Product|null {
  $id         = (int) $id;
  $currentUrl = getDecodedCurrentUrl();
  $field      = null;
  
  static $modelcacheHelper = null;
  if($modelcacheHelper === null) {
    /** @var Aoe_ModelCache_Helper_Data $modelcacheHelper */
    $modelcacheHelper = Mage::helper("aoe_modelcache");
  }
  
  // Defensive: During high load and a cache clear, the model cache helper might not be available. 
  if($modelcacheHelper) {
    if(_dhh_debug()) {
      if(!$forceRefresh && $modelcacheHelper->exists("catalog/product", $id)) {
        Mage::log(__FUNCTION__."::{$id} HIT   [{$currentUrl}]", Zend_Log::DEBUG, "verbose.txt", true);
      } else {
        Mage::log(__FUNCTION__."::{$id} SAVE  [{$currentUrl}]", Zend_Log::DEBUG, "verbose.txt", true);
      }
    }
    
    return $modelcacheHelper->get("catalog/product", $id, $field, $forceRefresh);
  }
  
  // Fallback to a native OpenMage load.
  return Mage::getModel("catalog/product")->load($id);
}

/**
 * Returns whether the OM profiler is enabled.
 * Note that the downstream function caches its result, so this is not expensive to call multiple times.
 *
 * @return boolean
 */
function dhh_profiler_enabled(): bool {
  return Varien_Profiler::isEnabled();
}

/**
 * Normalize a human supplier name to its system name.
 *
 * @param  string|Stringable|null $supplier
 * @return string|null
 */
function dhhNormalizeSupplier(string|null $supplier): string|null {
  if(blank($supplier)) {
    return null;
  }
  static $cache = [];
  if(isset($cache[$supplier])) {
    return $cache[$supplier];
  }
  $supplierSys = strtolower((string) $supplier);
  $supplierSys = match($supplierSys) {
    "de heer horeca"        => "deheerhoreca",
    "de jong luchttechniek" => "dejongluchttechniek",
    "orionstar robotics"    => "orionstar",
    default                 => $supplierSys,
  };
  $cache[$supplier] = $supplierSys;
  return $supplierSys;
}

/**
 * Get an empty product collection.
 *
 * @return Mage_Catalog_Model_Resource_Product_Collection
 */
function getProductCollection(): Mage_Catalog_Model_Resource_Product_Collection {
  /** @var Mage_Catalog_Model_Resource_Product_Collection */
  $_products = Mage::getResourceModel("catalog/product_collection");
  
  return $_products;
}

/**
 * Get a product URL by product entity ID, efficiently.
 *
 * @param  integer      $productId
 * @return string|false
 */
function getProductUrlById(int $id): string|false {
  $return = false;
  
  if($_products = getProductCollection()
    ->addAttributeToSelect("product_url")
    ->addIdFilter($id)
    ->addUrlRewrite()->load()) {
    
    /** @var Mage_Catalog_Model_Product */
    if($_product = $_products->getFirstItem()) {
      $return = $_product->getProductUrl();
      destruct($_product);
    }
    
    destruct($_products);
  }
  
  if(dhh_profiler_enabled()) {
    Mage::log(__FUNCTION__."(".json_encode(func_get_args()).") => ".json_encode($return), Zend_Log::DEBUG, "verbose.txt", true);
  }
  
  return $return;
}

/**
 * Get a product URL by product SKU, efficiently.
 *
 * @param  integer      $productId
 * @return string|false
 */
function getProductUrlBySku(string $sku): string|false {
  $return = false;
  
  if($_products = getProductCollection()
    ->addAttributeToSelect("product_url")
    ->addAttributeToFilter("sku", $sku)
    ->addUrlRewrite()->load()) {
    
    /** @var Mage_Catalog_Model_Product */
    if($_product = $_products->getFirstItem()) {
      $return = $_product->getProductUrl();
      destruct($_product);
    }
    
    destruct($_products);
  }
  
  if(dhh_profiler_enabled()) {
    Mage::log(__FUNCTION__."(".json_encode(func_get_args()).") => ".json_encode($return), Zend_Log::DEBUG, "verbose.txt", true);
  }
  
  return $return;
}

/**
 * Determine whether this script is running in a CLI context.
 *
 * @return bool
 */
function omIsCli(): bool {
  return \PHP_SAPI === 'cli' && \defined('STDOUT') && \defined('STDERR');
}
