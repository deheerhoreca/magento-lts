<?php

declare(strict_types=1);

use \Chefstore\Helper as ChefstoreHelper;
use \Brick\VarExporter\VarExporter;
use \Illuminate\Support\Arr;
use \Illuminate\Support\Number;
use \Illuminate\Support\Str;
use \Illuminate\Support\Stringable;
use \MathieuViossat\Util\ArrayToTextTable;

require_once __DIR__."/Loader.php";

/**
 * !!! Attention !!!
 * - Use !function_exists() to avoid conflicts when running as an Intel plugin
 */

/* ---------------------------------------------------------- Chefstore\Helper ---------------------------------------------------------- */

/**
 * Get the DeHeerHoreca Util helper.
 *
 * @return \DeHeerHoreca_Util_Helper_Util
 */
function getOmDhhUtilHelper(): \DeHeerHoreca_Util_Helper_Util {
  return ChefstoreHelper::loadOmHelperDhhUtil();
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
  function rescue(callable $callback, $rescue = null, $report = true) {
    try {
        return $callback();
    } catch (Throwable $exception) {
      if (value($report, $exception)) {
        if (is_string($exception)) {
          $exception = new Exception($exception);
        }
        // logger("Rescued exception: {$exception->getMessage()}", "ERROR");
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
  function value($value, ...$args) {
    return $value instanceof Closure ? $value(...$args) : $value;
  }
}

// Laravel aliases

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
  function ds(array|ArrayAccess &$target, $key, $value, bool $overwrite = true): mixed {
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
  function dp(array|ArrayAccess &$array, string $key, $value): mixed {
    return data_push($array, $key, $value);
  }
}

/**
 * Data yank. Supports key with dots.
 *
 * @param  array|ArrayAccess $array
 * @param  mixed             $key
 * @param  mixed      ss       $default
 * 
 * @return mixed
 */
if(!function_exists("dy")) {
  function dy(array|ArrayAccess &$array, $key, $default = null): mixed {
    return Arr::pull($array, $key, $default);
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
  function data_push(array &$array, string $key, $value): array {
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
    
    if(!is_iterable($value) || is_associative($value)) {
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
  function data_add(array &$array, $key, $value): array {
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
  function data_filled(Iterable $array, $key): bool {
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
  function data_pull(Iterable &$array, $key, $default = null): mixed {
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
  function data_coalesce(Iterable $data, mixed $default, ...$keys): mixed {
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
      "185.127.111.252",
      "31.201.36.137",
    ];
  }
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

/* ------------------------------------------------------------ Intel Picks ------------------------------------------------------------- */

if(!function_exists('printr')) {
  function printr($expr, $return = false) {
    $ret = null;
    if(is_array($expr) && !count($expr)) {
      return;
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
      return $return;
    }
    echo $ret;
  }
}

/**
 * Wrapper for the ArrayToTextTable class to print an array as a table.
 *
 * @param  array|object $data
 * @param  boolean      $return
 * @param  array        $render_options
 * @param  int          $col_wrap
 * @return string|null|false
 */
if(!function_exists("array_to_table")) {
  function array_to_table(array|object $data, bool $return = true, array $render_options = [], int $col_wrap = 60): string|null|false {
    if(blank($data)) {
      return null;
      return null;
    }
    
    // Make sure no subarrays exist -- @todo Arr::flatten() ?
    foreach($data as $key => &$value) {
      if(is_array($value)) {
        $value = \Arr::map($value, fn($item, $key) => implode("", (array) $item));
      }
    }
    
    // Cannot cache formatter as it uses $col_wrap
    $formatter = function(&$value, $key, $renderer) use ($col_wrap) {
      if($value === true)       $value = "TRUE";
      elseif($value === false)  $value = "FALSE";
      elseif($value === null)   $value = "NULL";
      
      if(is_string($value) && mb_strlen($value) > $col_wrap) {
        $value = wordwrap($value, ($col_wrap - 5), cut_long_words: true);
      }
    };
    
    $renderer = new ArrayToTextTable($data);
    $renderer->setUpperKeys(true);
    $renderer->setFormatter($formatter);
    
    try {
      $output = $renderer->getTable();
    } catch(TypeError $e) {
      // logger("TypeError while printing array to table: ".$e->__toString(), "ERROR");
      return false;
    } catch(Exception $e) {
      // logger("Exception while printing array to table: ".$e->__toString(), "ERROR");
      return false;
    }
    
    if($return) {
      return $output;
    }
    
    printr($output);
    
    return null;
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
    $flags = $inline ? VarExporter::INLINE_ARRAY : VarExporter::INLINE_SCALAR_LIST;
    try {
      $var = Str::swap([
        "  '"     => "  \"",
        "', '"    => "\", \"",
        "' => '"  => "\" => \"",
        "' => "   => "\" => ",
        " => '"   => " => \"",
        "['"      => "[\"",
        "']"      => "\"]",
        "null"    => "NULL",
      ], VarExporter::export($input, $flags));
    } catch (ExportException $e) {
      logger("Failed to TinyDump: {$e->getMessage()}", "ERROR");
      return null;
    }
    
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

if(function_exists("_get_product_attribute") === false) {
  function _get_product_attribute($_product, string $attribute_code, bool $implode_arrays = true) {
    if(is_object($_product) === false) {
      return null;
    }
    
    $attribute = $_product->getResource()->getAttribute($attribute_code);
    if(!$attribute) {
      // if(_dhh_debug()) {
      //   echo "Attribute '{$attribute_code}' does not exist";
      // }
      Mage::log("_get_product_attribute: Attribute '{$attribute_code}' does not exist", null, "exception.log", true);
      return null;
    }
    
    // $value = $attribute->getFrontend()->getValue($_product);
    $value = $_product->getResource()->getAttribute($attribute_code)->getFrontend()->getValue($_product);
    if($implode_arrays && is_array($value)) {
      $value = implode(", ", $value);
    }
    
    return $value;
  }
}

// @deprecated -- move to sanitize_alphanumeric()
if(function_exists("sanitizeForFilename") === false) {
  function sanitizeForFilename($string) {
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

if(function_exists("sanitize_alphanumeric") === false) {
  function sanitize_alphanumeric($string, string $replacement = "-") {
    $string = strtolower((string) preg_replace("/[^a-zA-Z0-9]+/", $replacement, (string) $string));
    return $string;
  }
}

if(function_exists("_dhh_debug") === false) {
  function _dhh_debug() {
    if(isset($_GET["nofpc"])
    && isset($_SERVER["REMOTE_ADDR"])
    && in_array($_SERVER["REMOTE_ADDR"], _dhh_ips())) {
      return true;
    }
    return false;
  }
}

if(function_exists("_dhh_reflect") === false) {
  function _dhh_reflect($function, $class = null) {
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

function _dhh_getselect($collection) {
  return $collection->getSelect()->__toString();
}

function in_range($number, $min, $max, $inclusive = false) {
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
 * @param  mixed $var
 * @return void
 */
function devDump(mixed $var): void {
  if(_dhh_debug()) {
    echo "<!-- DHH: ".var_export($var, true)." -->";
  }
}

if(function_exists("_getAlternativeEans") === false) {
  function _getAlternativeEans($ean) {
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

if(!function_exists("_cdn_img")) {
  /**
   * Generates an <img> tag or just the URL for an image served via a CDN with specified transformations.
   *
   * Exists in OpenMage and Intel, keep aligned:
   * - openmage/lib/Chefstore/functions.php
   * - intel/lib/intel.inc.php
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
   *
   * Named transformations are preferred at all times, for higher cache hit rates in CDN and ImageKit and browsers.
   * ----------------------------------------------------------------------------------------------------------------------------
   * Named transform	        Actual transform string
   * ----------------------------------------------------------------------------------------------------------------------------
   * ik_ml_thumbnail          tr:w-440,h-440,fo-center,cm-pad_resize
   * omcatprdlstfr            tr:w-125,h-125,q-75,c-at_max
   * omcatprdlst              tr:w-200,h-200,q-75,c-at_max
   * omcatctglst              tr:w-140,h-140,q-80,c-at_max
   * omcatprddtlt             tr:w-172,h-400,q-75,c-at_max_enlarge
   * omcatprddtlf             tr:w-2048,h-2048,q-80,c-at_max_enlarge
   * ombrndlgos               tr:w-140,h-40,cm-pad_resize
   * omexfull                 tr:w-1536,h-1536,q-80,c-at_max_enlarge
   * logosmall                tr:w-210,h-60,c-at_max
   *
   * @param array $options
   *
   * @return string|false
   */
  function _cdn_img(array $options): string|false {
    $url = htmlspecialchars((string) ($options["url"] ?? ""));
    
    if(blank($url)) {
      return false;
    }
    
    $identifier       = $options["identifier"] ?? "NO_ID";
    $fs_path        = (string) data_get($options, "fs_path", "");
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
    $lazy           = $options["lazy"]          ?? false;
    $class          = $options["class"]         ?? "";
    $style          = $options["style"]         ?? "";
    $namedXform     = dg($options, "xform", null);
    
    $cdn            = (string)  dg($options, "cdn", "imagekit");
    $alt            = (string)  $alt;
    $id             = (string)  $id;
    $fit            = (string)  $fit;
    $format         = (string)  $format;
    $quality        = (int)     $quality;
    $url_only       = (bool)    $url_only;
    $relative_url   = (bool)    $relative_url;
    $include_2x     = (bool)    $include_2x;
    $lazy           = (bool)    $lazy;
    $class          = (string)  $class;
    $style          = (string)  $style;
    $id_html        = "";
    $lazy_html      = "";
    $class_html     = "";
    $style_html     = "";
    $html           = "";
    
    // Pre-process settings
    if($cdn === "imagekit" || $cdn === "imagekit_custom") {
      $relative_url = true; // Required
    }
    
    // Applies to all CDNs
    if($lazy) {
      $lazy_html = " loading=\"lazy\"";
    }
    if(strlen($id) > 0) {
      $id_html = " id=\"{$id}\"";
    }
    if($fit === "contain" || $fit === "scale-down" || $fit === "scale-up") {
      $class .= " object-fit-contain";
    }
    if(strlen($class) > 0) {
      $class_html = " class=\"{$class}\"";
    }
    if(strlen($style) > 0) {
      $style_html .= " style=\"{$style}\"";
    }
    if($relative_url) {
      $url = str_replace(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB), "", $url);
    }
    if($add_mod_time && strlen((string) $fs_path) > 0) {
      if(defined("APP_SHORT") && APP_SHORT === "intel" && is_file($fs_path) && $mtime = filemtime($fs_path)) {
        $url = Chefstore\CacheBuster::prependExtension($url, "ts{$mtime}");
      } else {
        if(function_exists("_add_file_v_param")) {
          $url = _add_file_v_param($url, $fs_path, $identifier);
        } else {
          $url = Mage::helper("deheerhoreca_util/util")->_add_file_v_param($url, $fs_path, $identifier);
        }
      }
    }
    
    $src_url = "";
    
    $widthAttr  = ($width > 0) ? "width=\"{$width}\"" : "";
    $heightAttr = ($height > 0) ? "height=\"{$height}\"" : "";
    
    switch($cdn) {
      case "none":
        $src_url = $url;
        $html = "<img src=\"{$url}\" {$widthAttr} {$heightAttr} alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
        break;
      
      // @see https://docs.imagekit.io/features/image-transformations
      case "imagekit":
      case "imagekit_custom":
        $cdn_base = "//images.chefstore.nl";
        
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
        
        devDump($cdn_options_string);
        
        $url      = str_ireplace(["https://www.chefstore.nl/"], "", $url); // url comes in as "https://www.chefstore.nl/media/..."
        $src_url  = "{$cdn_base}/{$cdn_options_string}/{$url}";
        
        // Either use 2x the resolution -- only when no named transformation is used
        if(blank($namedXform) && $include_2x && is_numeric($cdn_options["w"]) && is_numeric($cdn_options["h"])) {
          $cdn_options["w"]   *= 2;
          $cdn_options["h"]   *= 2;
          $cdn_options_string = implode_array_with_keys($cdn_options, ",", "-");
          $src_url_2x         = "{$cdn_base}/{$cdn_options_string}/{$url}";
          $srcset             = "srcset=\"{$src_url_2x} 2x\" ";
        } else {
          $srcset = "";
        }
        
        $html = "<img src=\"{$src_url}\" srcset=\"{$srcset}\" {$widthAttr} {$heightAttr} alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
        break;
    }
    
    if($url_only) {
      return $src_url;
    }
    
    return $html;
  }
}

// Adds a ?v={timestamp} param to the URL
// Exists in OpenMage and Intel
if(function_exists("_add_file_v_param") === false) {
  function _add_file_v_param(string $url, string $fs_path, string $identifier): string {
    if(is_file($fs_path)) {
      if($mod_time = filemtime($fs_path)) {
        // https://stackoverflow.com/questions/5809774/manipulate-a-url-string-by-adding-get-parameters
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

// ["foo" => "bar", "beer" => "fest"] --> "foo=bar, beer=fest"
if(function_exists("implode_array_with_keys") === false) {
  function implode_array_with_keys($array, $separator = ", ", $glue = "=") {
    $ret = "";
    foreach($array as $key => $val) {
      $ret .= $key.$glue.$val.$separator;
    }
    $ret = substr($ret, 0, -(strlen((string) $separator)));
    return $ret;
  }
}

// @see // https://stackoverflow.com/questions/5809774/manipulate-a-url-string-by-adding-get-parameters
if(function_exists("add_url_param") === false) {
  function add_url_param(string $url, $key, $value): string {
    $url .= (parse_url($url, PHP_URL_QUERY) ? "&" : "?") . "{$key}={$value}";
    return $url;
  }
}

// Remove one or more parameters from a URL
if(function_exists("remove_url_param") === false) {
  function remove_url_param(string $url, $params): string {
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

// Try to convert an XML string to an array, fail quietly with logging
if(function_exists("xml_string_to_array") === false) {
  function xml_string_to_array(string $string) {
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
if(function_exists("dhh_get_quote_id") === false) {
  function dhh_get_quote_id(): string {
    if($_SESSION) {
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

/* ---------------------------------------------------------- OpenMage Helpers ---------------------------------------------------------- */

/**
 * @see Mage_Core_Helper_Abstract
 * => No need for function_exists(): If Intel wants to use these, it should use this file directly.
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
  return Mage::helper("core")->htmlEscape($string);
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
