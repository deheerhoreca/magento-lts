<?php

declare(strict_types=1);

namespace Chefstore;

use Mage;
use Mage_Core_Model_Store;

class CacheBuster {
  
  /**
   * Add the filemtime to an FS path for cache busting.
   * Input: "./assets/bower-asset/font-awesome/css/all.min.css"
   * Output: "/assets/bower-asset/font-awesome/css/all.ts0123456789.min.css"
   *
   * @param   string  $path  MUST be a relative path, starting with the webroot
   * @return  string
   */
  public static function assetAddMtime(string $path): string {
    if(is_file($path)) {
      $path = ltrim(self::prependExtension($path, "ts".filemtime($path)), ".");
    }
    return $path;
  }  
  
  /**
   * Add timestamp to URL for cache busting.
   *
   * @param  string      $url
   * @param  string|null $baseUrl
   * @param  string|null $basePath
   *
   * @return string
   */
  public static function _addTimestampToUrl(string $url, ?string $baseUrl = null, ?string $basePath = null): string {
    $GLOBALS["dhh_om_fs_mapping"] ??= [
      ["value" => Mage_Core_Model_Store::URL_TYPE_JS,    "label" => "/js/"],
      ["value" => Mage_Core_Model_Store::URL_TYPE_MEDIA, "label" => "/media/"],
      ["value" => Mage_Core_Model_Store::URL_TYPE_SKIN,  "label" => "/skin/"],
    ];
    
    $url      = self::_sanitizeUrl($url);
    $baseUrl  = self::_sanitizeUrl($baseUrl);
    $path     = str_replace($baseUrl, $basePath, $url);
    $pathinfo = pathinfo($path);
    
    if (empty($pathinfo["extension"]) || empty($pathinfo["filename"]) || empty($pathinfo["basename"])
    || !in_array($pathinfo["extension"], ["png", "jpg", "gif", "jpeg", "webp", "svg", "ico"], true) || !file_exists($path)) {
      return $url;
    }
    
    $timestamp = filemtime($path);
    
    $final = [
      $pathinfo["filename"],
      $timestamp,
      $pathinfo["extension"],
    ];
    
    return str_replace($pathinfo["basename"], implode(".", $final), $url);
  }
  
  /**
   * Sanitize URL by removing query, fragment, user, or pass if found
   *
   * @param   string  $url
   * @return  string
   */
  protected static function _sanitizeUrl(string $url): string {
    $url    = parse_url($url);
    $scheme = isset($url["scheme"]) ? $url["scheme"] . "://" : "";
    $host   = isset($url["host"]) ? $url["host"] : "";
    $port   = isset($url["port"]) ? ":" . $url["port"] : "";
    $path   = isset($url["path"]) ? $url["path"] : "";
    
    return "$scheme$host$port$path";
  }
  
  /**
   * Prepend to file extension
   *
   * @param   string  $path
   * @param   string  $prepend
   *
   * @return  string
   */
  public static function prependExtension(string $path, string $prepend) {
    return self::replaceExtension($path, $prepend.".".self::getExtension($path));
  }
  
  /**
   * Replace file extension in a path or a URL.
   *
   * @param   string  $path
   * @param   string  $new_extension
   *
   * @return  string
   */
  public static function replaceExtension(string $path, string $new_extension): string {
    // If path is an existing file, replace extension
    if(is_file($path) && $info = pathinfo($path)) {
      return "{$info["dirname"]}/{$info["filename"]}.{$new_extension}";
    }
    
    // If path is a URL, or non-existing file, use a regex to replace extension
    $path = preg_replace("/(\.[a-zA-Z0-9]+)(\?.*)?$/", ".{$new_extension}$2", $path);
    
    return $path;
  }
  
  /**
   * Get file extension of a path or URL.
   *
   * @param   string  $path
   * @return  string
   */
  public static function getExtension(string $path): string {
    return pathinfo($path, PATHINFO_EXTENSION);
  }
  
  /**
   * Get the root directory of the OpenMage installation.
   *
   * @return string
   */
  public static function root(): string {
    return \BP;
  }
  
  /**
   * Convert an OpenMage URL to a file system path.
   * Input: "https://www.chefstore.nl/skin/frontend/enterprise/default/css/styles.css"
   * Output: "/var/www/vhosts/chefstore.nl/workspace/openmage/skin/frontend/enterprise/default/css/styles.css"
   *
   * @param  string  $url
   * @return string
   */
  public static function pathByUrl(string $url): string {
    static $baseUrl  = null;
    static $basePath = null;
    $baseUrl  ??= Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    $basePath ??= self::root().DS;
    $path       = str_replace($baseUrl, $basePath, $url);
    
    return $path;
  }
}
