<?php

declare(strict_types=1);

use \Illuminate\Support\Arr;
use \Illuminate\Support\Number;
use \Illuminate\Support\Str;
use \Illuminate\Support\Stringable;
use \MathieuViossat\Util\ArrayToTextTable;

/**
 * !!! Attention !!!
 * - Use !function_exists() to avoid conflicts when running as an Intel plugin
 */

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

// LARAVEL HELPERS

/**
 * Checks if any of the values match the pattern, case insensitive.
 *
 * @param  string                             $pattern
 * @param  float|string|null|iterable<string> $values
 * @param  bool                               $ignoreCase
 * 
 * @return bool
 */
if(!function_exists("sis")) {
  function sis(mixed $pattern, float|string|null|array $values, bool $ignoreCase = true): bool {
    if($values === null) {
      return false;
    }
    
    if(!is_iterable($values)) {
      $values = [$values];
    }
    
    foreach($values as $value) {
      if($value instanceof Stringable) {
        $value = (string) $value;
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
 * @param  string|iterable<string>            $patterns
 * @param  float|string|null|iterable<string> $values
 * @param  bool                               $ignoreCase
 * 
 * @return bool
 */
if(!function_exists("wild_sis")) {
  function wild_sis(string|array $patterns, float|string|null|array $values, bool $ignoreCase = true): bool {
    if($values === null) {
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
      if(Str::is($patterns, $value, $ignoreCase)) {
        return true;
      }
    }
    
    return false;
  }
}

// Intel picks

if(!function_exists('printr')) {
  function printr($expr, $return = false) {
    $ret = null;
    if(is_array($expr) && !sizeof($expr)) {
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

// Miscellaneous


if(function_exists("_get_product_attribute") === false) {
  function _get_product_attribute($_product, string $attribute_code, bool $implode_arrays = true) {
    if(is_object($_product) === false) {
      return null;
    }
    
    $attribute = $_product->getResource()->getAttribute($attribute_code);
    if(!$attribute) {
      if(_dhh_debug()) {
        echo "Attribute '{$attribute_code}' does not exist";
      }
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

if(function_exists("printr") === false) {
  function printr($expr, $return = false) {
    $ret = null;
    if(is_array($expr) && !sizeof($expr)) {
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

// Generate a HTML img tag for a cloudflare image
// Exists in OpenMage and Intel:
// - app/code/local/DeHeerHoreca/Util/Helper/Util.php
// - lib/intel.inc.php
if(function_exists("_cdn_img") === false) {
  function _cdn_img(array $options) {
    $url        = $options["url"]       ?? false;
    $url        = (string) htmlspecialchars((string) $url);
    
    if($url === false) {
      return false;
    }
    
    // $options["cm"]
    
    $identifier     = $options["identifier"]    ?? "NO_ID";
    $options["cdn"] ??= "imagekit_custom";
    $fs_path        = $options["fs_path"]       ?? null;
    $add_mod_time   = $options["add_mod_time"]  ?? false; // Requires fs_path
    $width          = $options["width"]         ?? 0;
    $height         = $options["height"]        ?? 0;
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
    
    $cdn            = (string)  $options["cdn"];
    $width          = (int)     $width;
    $height         = (int)     $height;
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
      // $url = _add_file_v_param($url, $fs_path, $identifier);
      if(is_file($fs_path) && $mtime = filemtime($fs_path)) {
        $url = Chefstore\CacheBuster::prependExtension($url, "ts{$mtime}");
      }
    }
    
    switch($cdn) {
      
      case "none":
        $src_url = $url;
        $html = "<img src=\"{$url}\" width=\"{$width}\" height=\"{$height}\" alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
        break;
      
      // @see https://docs.imagekit.io/features/image-transformations
      // https://ik.imagekit.io/vzc6xuj9l/tr:w-175,h-175,q-75,c-at_max/media/catalog/product/c/o/combisteel-7450.0320-00-b-2f36.jpg?v=1639661476
      case "imagekit":
        $cdn_base = "//ik.imagekit.io/vzc6xuj9l";
        $cdn_options  = [
          "w"           => $width,
          "h"           => $height,
        ];
        if($quality > 0) {
          $cdn_options["q"] = $quality;
        }
        if($fit === "contain" || $fit === "scale-down") {
          $cdn_options["c"] = "at_max";                     // max-size crop
        }
        if($fit === "scale-up") {
          $cdn_options["c"] = "at_max_enlarge";             // max-size crop
        }
        $cdn_options_string   = "tr:".implode_array_with_keys($cdn_options, ",", "-");
        $url                  = str_ireplace(["https://www.chefstore.nl/"], "", $url); // url comes in as "https://www.chefstore.nl/media/..."
        $src_url              = "{$cdn_base}/{$cdn_options_string}/{$url}";
        
        // Either use 2x the resolution
        if($include_2x && is_numeric($cdn_options["w"]) && is_numeric($cdn_options["h"])) {
          $cdn_options["w"]   *= 2;
          $cdn_options["h"]   *= 2;
          $cdn_options_string = implode_array_with_keys($cdn_options, ",", "-");
          $src_url_2x         = "{$cdn_base}/{$cdn_options_string}/{$url}";
          $srcset             = "srcset=\"{$src_url_2x} 2x\" ";
        } else {
          $srcset = "";
        }
        
        $html = "<img src=\"{$src_url}\" srcset=\"{$srcset}\" width=\"{$width}\" height=\"{$height}\" alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
        break;
      
      // @see https://docs.imagekit.io/features/image-transformations
      // "https://images.chefstore.nl/media/catalog/product/haha/saro-423-1400-00-a-933c.jpg?tr=w-326,h-400,q-75,c-at_max_enlarge&v=1656629340"
      case "imagekit_custom":
        $cdn_base     = "//images.chefstore.nl";
        $cdn_options  = [
          "w"           => $width,
          "h"           => $height,
        ];
        if($quality > 0) {
          $cdn_options["q"] = $quality;
        }
        if($fit === "contain" || $fit === "scale-down") {
          $cdn_options["c"] = "at_max";                     // max-size crop
        }
        if($fit === "scale-up") {
          $cdn_options["c"] = "at_max_enlarge";             // max-size crop
        }
        
        // cm overrides c
        if(!empty($options["cm"])) {
          $cdn_options["cm"] = $options["cm"];
          if(isset($cdn_options["c"])) {
            unset($cdn_options["c"]);
          }
        }
        $cdn_options_string   = implode_array_with_keys($cdn_options, ",", "-");
        $url                  = str_ireplace(["https://www.chefstore.nl/"], "", $url); // url comes in as "https://www.chefstore.nl/media/..."
        $src_base_url         = "{$cdn_base}/{$url}";
        $src_url              = add_url_param($src_base_url, "tr", $cdn_options_string);
        
        // Either use 2x the resolution
        if($include_2x && is_numeric($cdn_options["w"]) && is_numeric($cdn_options["h"])) {
          $cdn_options["w"]   *= 2;
          $cdn_options["h"]   *= 2;
          $cdn_options_string = implode_array_with_keys($cdn_options, ",", "-");
          $src_url_2x         = add_url_param($src_base_url, "tr", $cdn_options_string);
          $srcset             = "srcset=\"{$src_url_2x} 2x\" ";
        } else {
          $srcset = "";
        }
        
        $html = "<img src=\"{$src_url}\" {$srcset}width=\"{$width}\" height=\"{$height}\" alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
        break;
      
      // @see https://docs.optimole.com/article/1872-how-to-use-the-custom-integration-in-optimole
      case "optimole":
        $skip_js = true;
        if($skip_js) {
          // https://mlwes2arpcu4.i.optimole.com/w:800/h:600/q:85/https://andreeacristinaradacina.github.io/image.png
          $cdn_base     = "https://mlwes2arpcu4.i.optimole.com/";
          $cdn_options  = "w:{$width}/h:{$height}/q:{$quality}";
          $src_url      = "{$cdn_base}{$cdn_options}/{$url}";
          $html         = "<img src=\"{$src_url}\" width=\"{$width}\" height=\"{$height}\" alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
        } else {
          $src_url = $url;
          $html = "<img data-opt-src=\"{$src_url}\" width=\"{$width}\" height=\"{$height}\" alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
        }
        break;
      
      case "cloudflare":
        // If desiring a relative URL, take out the BaseUrl
        if($relative_url) {
          $cdn_base         = "/cdn-cgi/image/";
        } else {
          $cdn_base         = rtrim((string) Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB), "/")."/cdn-cgi/image/";
        }
        $cdn_options_base = "metadata=none,q={$quality},fit={$fit},format={$format}";
        $cdn_options      = "{$cdn_options_base},width={$width},height={$height}";
        $src_url          = "{$cdn_base}{$cdn_options}/{$url}";
        
        // Either use 2x the resolution, or set dpr=2
        if($include_2x) {
          // $width_2x       = $width * 2;
          // $height_2x      = $height * 2;
          $width_2x       = $width;
          $height_2x      = $height;
          $dpr            = 2;
          $cdn_options_2x = "{$cdn_options_base},width={$width_2x},height={$height_2x},dpr={$dpr}";
          $srcset         = "{$cdn_base}{$cdn_options_2x}/{$url} 2x";
        }
        $html = "<img src=\"{$src_url}\" srcset=\"{$srcset}\" width=\"{$width}\" height=\"{$height}\" alt=\"{$alt}\"{$lazy_html}{$class_html}{$style_html}{$id_html}>";
        break;
    }
    
    // foreach(array_keys($options) as $option) {
      // printr("{$option}: ".var_export($$option, true));
    // }
    // printr("src_url: ".var_export($src_url, true));
    // printr(str_repeat("-", 100));
    
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
