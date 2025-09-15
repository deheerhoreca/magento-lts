<?php

declare(strict_types=1);

use \Illuminate\Support\Arr;
use \Illuminate\Support\Collection;
use \Illuminate\Support\Number;
use \Illuminate\Support\Str;
use \Illuminate\Support\Stringable;
use \Illuminate\Support\Timebox;
use \Illuminate\Support\Uri;

// Taken from laravel/framework, did not want to install full package

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
  function rescue(callable $callback, $rescue = null, bool|callable $report = true) {
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

if(!function_exists("ds")) {
  /**
   * Shorthand for data_set(). Supports key with dots.
   *
   * @param  array|ArrayAccess $target
   * @param  mixed             $key
   * @param  mixed             $value
   * @param  boolean           $overwrite
   * @return mixed
   */
  function ds(array|ArrayAccess &$target, $key, $value, bool $overwrite = true): mixed {
    return data_set($target, $key, $value, $overwrite);
  }
}

if(!function_exists("dp")) {
  /**
   * Stuff another key in there. MIGHT NOT SUPPORT KEYS WITH DOTS!
   *
   * @param  array|ArrayAccess $array
   * @param  string            $key
   * @param  mixed             $value
   * @return mixed
   */
  function dp(array|ArrayAccess &$array, string $key, $value): mixed {
    return data_push($array, $key, $value);
  }
}

if(!function_exists("dy")) {
  /**
   * Data yank. Supports key with dots.
   *
   * @param  array|ArrayAccess $array
   * @param  mixed             $key
   * @param  mixed             $default
   * @return mixed
   */
  function dy(array|ArrayAccess &$array, $key, $default = null): mixed {
    return Arr::pull($array, $key, $default);
  }
}

if(!function_exists("clampNumber")) {
  /**
   * Clamp the given number between the given minimum and maximum.
   *
   * @param  integer|float $min
   * @param  integer|float $number
   * @param  integer|float $max
   * @return integer|float
   */
  function clampNumber(int|float $min, int|float $number, int|float $max): int|float {
    return Number::clamp($number, $min, $max);
  }
}

if(!function_exists("data_push")) {
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

if(!function_exists("data_add")) {
  /**
   * data_fill() but one that overwrites existing values if they are blank().
   *
   * @param  array $array
   * @param  mixed $key
   * @param  mixed $value
   *
   * @return array
   */
  function data_add(array &$array, $key, $value): array {
    if(data_blank($array, $key)) {
      Arr::set($array, $key, $value);
    }
    
    return $array;
  }
}

if(!function_exists("data_filled")) {
  /**
   * Like filled() but for arrays and supports dot notation.
   *
   * @param  Iterable $array
   * @param  mixed    $key
   * @return boolean
   */
  function data_filled(Iterable $array, $key): bool {
    if(Arr::has($array, $key)) {
      return filled(data_get($array, $key, null));
    }
    
    return false;
  }
}

if(!function_exists("data_pull")) {
  /**
   * Get a value from the array, and remove it
   *
   * @param  Iterable $array
   * @param  mixed    $key
   * @param  mixed    $default
   * @return mixed
   */
  function data_pull(Iterable &$array, $key, $default = null): mixed {
    return Arr::pull($array, $key, $default);
  }
}

if(!function_exists("data_blank")) {
  /**
   * Like blank() but for arrays.
   *
   * @param  Iterable $array
   * @param  mixed    $key
   * @return boolean
   */
  function data_blank(Iterable $array, $key): bool {
    if(\Arr::has($array, $key)) {
      return blank(data_get($array, $key, null));
    }
    
    return true;
  }
}

if(!function_exists("data_coalesce")) {
  /**
   * Returns the first not blank() value.
   *
   * @param  Iterable $data
   * @param  mixed    $default
   * @param  mixed    ...$keys
   * @return mixed
   */
  function data_coalesce(Iterable $data, mixed $default, ...$keys): mixed {
    foreach($keys as $key) {
      if(data_filled($data, $key)) {
        return data_get($data, $key);
      }
    }
    
    return $default;
  }
}
