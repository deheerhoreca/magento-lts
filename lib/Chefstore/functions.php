<?php

declare(strict_types=1);

use \Illuminate\Support\Arr;
use \Illuminate\Support\Collection;
use \Illuminate\Support\Number;
use \Illuminate\Support\Str;
use \Illuminate\Support\Stringable;
use \Illuminate\Support\Timebox;
use \Illuminate\Support\Uri;
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
