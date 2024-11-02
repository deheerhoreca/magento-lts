<?php

declare(strict_types=1);

namespace Chefstore;

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
  
  public static function printr($expr, bool $return = false) {
    $ret = "";
    if(php_sapi_name() !== "cli") {
      $ret .= "<code style='white-space: pre-wrap; word-wrap:break-word;'>";
    }
    $ret .= var_export($expr, true);
    if(php_sapi_name() !== "cli") {
      $ret .= "</code>";
    }
    $ret .= PHP_EOL;
    
    if($return) {
      return $return;
    }
    
    echo $ret;
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
        } elseif ( false === strpos( $data, '"' ) ) {
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
  
}
