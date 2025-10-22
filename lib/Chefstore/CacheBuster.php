<?php

declare(strict_types=1);

namespace Chefstore;

class CacheBuster {
  
  // function addTs(string $relative_path): string {
  //   if(!is_file($relative_path)) {
  //     return $relative_path;
  //   }
    
  //   $pathinfo = pathinfo($relative_path);
    
  //   if(empty($pathinfo['extension']) || empty($pathinfo['filename']) || empty($pathinfo['basename']) || !in_array($pathinfo['extension'], ["png", "jpg", "gif"], true)) {
  //     return $relative_path;
  //   }
    
  //   $mtime    = filemtime($path);
  //   $ts_path  = implode(".", [$pathinfo["filename"], $timestamp, $pathinfo['extension']]);
    
  //   return str_replace($pathinfo["basename"], $ts_path, $url);
  // }
  
  // Add the filemtime to an FS path
  // - $path MUST be a relative path, starting with the webroot
  // - "./assets/bower-asset/font-awesome/css/all.min.css" => "/assets/bower-asset/font-awesome/css/all.ts0123456789.min.css"
  public static function assetAddMtime(string $path): string {
    if(is_file($path)) {
      $path = ltrim(prependExtension($path, "ts".filemtime($path)), ".");
    }
    
    return $path;
  }  
  
  public static function _addTimestampToUrl(string $url, ?string $baseUrl = null, ?string $basePath = null) {
    $GLOBALS["dhh_om_fs_mapping"] ??= [
      ['value' => Mage_Core_Model_Store::URL_TYPE_JS,    'label' => "/js/"],
      ['value' => Mage_Core_Model_Store::URL_TYPE_MEDIA, 'label' => "/media/"],
      ['value' => Mage_Core_Model_Store::URL_TYPE_SKIN,  'label' => "/skin/"],
    ];
    
    $url      = self::_sanitizeUrl($url);
    $baseUrl  = self::_sanitizeUrl($baseUrl);
    $path     = str_replace($baseUrl, $basePath, $url); 
    $pathinfo = pathinfo($path);
    
    if (empty($pathinfo['extension']) || empty($pathinfo['filename']) || empty($pathinfo['basename'])
    || !in_array($pathinfo['extension'], ["png", "jpg", "gif"], true) || !file_exists($path)) {
      return $url;
    }
    
    $timestamp = filemtime($path);
    
    $final = [
      $pathinfo['filename'],
      $timestamp,
      $pathinfo['extension'],
    ];
    
    return str_replace($pathinfo['basename'], implode('.', $final), $url);
  }
  
  /**
   * Sanitize URL by removing query, fragment, user, or pass if found
   *
   * @param $url
   * @return string
   */
  protected static function _sanitizeUrl(string $url): string {
    $url    = parse_url($url);
    $scheme = isset($url['scheme']) ? $url['scheme'] . '://' : '';
    $host   = isset($url['host']) ? $url['host'] : '';
    $port   = isset($url['port']) ? ':' . $url['port'] : '';
    $path   = isset($url['path']) ? $url['path'] : '';
    return "$scheme$host$port$path";
  }
  
  // \Chefstore\CacheBuster::prependExtension()
  public static function prependExtension(string $path, string $prepend) {
    return self::replaceExtension($path, $prepend.".".self::getExtension($path));
  }
  
  // \Chefstore\CacheBuster::replaceExtension()
  public static function replaceExtension(string $path, string $new_extension): string {
    if(is_file($path) && $info = pathinfo($path)) {
      return "{$info["dirname"]}/{$info["filename"]}.{$new_extension}";
    }
    
    return $path;
  }
  
  // \Chefstore\CacheBuster::getExtension()
  public static function getExtension(string $path): string {
    return pathinfo($path, PATHINFO_EXTENSION);
  }
}
