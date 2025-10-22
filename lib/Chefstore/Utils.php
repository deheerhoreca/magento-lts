<?php

declare(strict_types=1);

namespace Chefstore;

use \Brick\VarExporter\VarExporter;
use \Illuminate\Support\Str;

class Utils {
  
  public static $dev_ips = [
    "5.132.21.238",
    "185.127.111.227",
    "185.127.111.251",
    "185.127.111.252",
    "87.210.61.235",
    "81.59.51.217",
  ];
  
  // \Chefstore\Utils::dump("foo");
  public static function dump($mixed, bool $return = false) {
    if (is_callable($mixed)) {
      self::printr($mixed(), $return);
    } else {
      self::printr($mixed, $return);
    }
  }
  
  // \Chefstore\Utils::is_serialized("foo");
  public static function is_serialized($data, $strict = true) {
    // If it isn't a string, it isn't serialized.
    if (! is_string($data)) {
      return false;
    }
    $data = trim($data);
    if ('N;' === $data) {
      return true;
    }
    if (strlen($data) < 4) {
      return false;
    }
    if (':' !== $data[1]) {
      return false;
    }
    if ($strict) {
      $lastc = substr($data, -1);
      if (';' !== $lastc && '}' !== $lastc) {
        return false;
      }
    } else {
      $semicolon = strpos($data, ';');
      $brace     = strpos($data, '}');
      // Either ; or } must exist.
      if (false === $semicolon && false === $brace) {
        return false;
      }
      // But neither must be in the first X characters.
      if (false !== $semicolon && $semicolon < 3) {
        return false;
      }
      if (false !== $brace && $brace < 4) {
        return false;
      }
    }
    $token = $data[0];
    switch ($token) {
      case 's':
        if ($strict) {
          if ('"' !== substr($data, -2, 1)) {
            return false;
          }
        } elseif (!str_contains($data, '"')) {
          return false;
        }
        // Or else fall through.
      case 'a':
      case 'O':
        return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
      case 'b':
      case 'i':
      case 'd':
        $end = $strict ? '$' : '';
        return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
    }
    return false;
  }
  
  // PHP var_export() with short array syntax (square brackets) indented 2 spaces.
  // NOTE: The only issue is when a string value has `=>\n[`, it will get converted to `=> [`
  // @link https://www.php.net/manual/en/function.var-export.php
  // \Chefstore\Utils::d()
  public static function d($expression, bool $return = false) {
    $export = var_export($expression, true);
    $patterns = [
      "/array \(/" => '[',
      "/^([ ]*)\)(,?)$/m" => '$1]$2',
      "/=>[ ]?\n[ ]+\[/" => '=> [',
      "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
    ];
    $export = preg_replace(array_keys($patterns), array_values($patterns), $export);

    if($return) {
      return $export;
    }

    self::printr($export);
  }

  // \Chefstore\Utils::printr("foo");
  public static function printr($expression, bool $return = false) {
    $ret = "";
    
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

  // \Chefstore\Utils::devdump("foo");
  public static function devdump($expression, bool $return = false): null|false {
    if(isset($_GET["nofpc"]) && isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], self::$dev_ips, true)) {
      d($expression, $return);
      return null;
    }

    return false;
  }

  // \Chefstore\Utils::td()
  // Tiny dump -- @url https://github.com/brick/varexporter
  public static function td(mixed $input, bool $return = true, bool $inline = false): string|null {
    $flags = $inline ? VarExporter::INLINE_ARRAY : VarExporter::INLINE_SCALAR_LIST;
    try {
      $var = Str::swap([
        "['"    => "[\"",
        "', '"  => "\", \"",
        "']"    => "\"]"
      ], VarExporter::export($input, $flags));
    } catch (ExportException $e) {
      \Mage::log("Failed to TinyDump value: {$e->getMessage()}");
      return null;
    }

    if($return) {
      return $var;
    }

    dump($var);
    return null;
  }
  
  /**
   * Micro sleep.
   *
   * @param  integer $time
   * @return void
   */
  public static function msleep(int $time): void {
    usleep($time * 1000);
  }
}
