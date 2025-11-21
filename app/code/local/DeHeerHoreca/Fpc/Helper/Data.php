<?php

// declare(strict_types=1); // @todo

use \Illuminate\Support\Arr;
use \Illuminate\Support\Collection;
use \Illuminate\Support\Str;

// require_once __DIR__."/TinyHtmlMinifier.class.php";

class DeHeerHoreca_Fpc_Helper_Data extends Mage_Core_Helper_Abstract {
  public static $om_action_whitelist  = [
    "catalog_product_view", "catalog_category_view", "blog_post_view", "blog_index_list",
    "cms_page_view", "cms_index_index",
    // "amshopby_index_index", // Disabled because we need to tag it properly first
  ];
  
  public static $request_is_anonymous = null;
  
  public function clearCache() {
    $cache_tags = [
      "DHH_FPC",
      "DHH_LISTVIEW_PRODUCT",
      "DHH_cms_index_index",
      "DHH_EKE_OGMETA",
      "DHH_TM_RICHSNIPPETS",
      "AMSHOPBY",
    ];
    $response = Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $cache_tags);
    $url = Mage::helper("adminhtml")->getUrl("adminhtml/cache/index");
    echo "<span><a href='{$url}'>Back</a></span><br><br>";
    
    return true;
  }
  
  // Revalidate all currently cached entries
  public function revalidateCache() {
    return;
    
    $cache = Varien_Autoload::getCache();
    Varien_Autoload::setCache(array());
    foreach($cache as $className => $path) {
      Varien_Autoload::getFullPath($className);
    }
    self::log("Revalidated ".count($cache)." classes");
  }
  
  /**
   * Check url
   *
   * @return bool
   */
  public function checkUrl() {
    $k = base64_decode((string) Mage::app()->getRequest()->getParam("k"));
    $v = base64_decode((string) Mage::app()->getRequest()->getParam("v"));
    $ek = Mage::helper("core")->decrypt($v);
    return $k && $v && ($ek == $k);
  }
  
  /**
   * Get url
   *
   * @return bool
   */
  public function getUrl() {
    $k = Mage::helper("core")->getRandomString(16);
    return Mage::getUrl(
      "deheerhorecafpc/index/clear",
      array(
        "k"      => base64_encode((string) $k),
        "v"      => base64_encode((string) Mage::helper("core")->encrypt($k)),
        "_store" => Mage::app()->getDefaultStoreView()->getCode()
      )
    );
  }
  
  // Cleans the cached URL by removing URL parameters that do not affect the payload
  // Otherwise we would cache index.html?sqr=x separately from index.html
  // Optionally takes a URL for debug/dev
  public static function get_cache_url(?string $url = null): string {
    if(empty($url)) {
      $url = html_entity_decode((string) Mage::helper("core/url")->getCurrentUrl());
    }
    
    // List of query parameters that have no consequences for the rendered HTML
    // @todo Keep this up to date to prevent cache fragmentation and blowup
    // - csredir: chefstore.nl redirect indicator
    // - multipass: Added by LayeredNav if >X values selected to control bots and caching
    $ignored_url_query_keys = [
      "sqr", "profile", "___store", "refreshfpc", "__cf_chl_jschl_tk__",
      "utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term",
      "gclid", "gbraid", "wbraid", "cfhtmlcache", "mc_cid", "mc_eid",
      "cstag", "title", "srsltid", "csredir", "multipass", "opi", "sa", "ved",
      "usg", "msclkid",
      
      // Cloudflare
      "forcepreload", "forcepreloadonly",
    ];
    $url = self::strip_param_from_url($url, $ignored_url_query_keys);
    
    // Remove things that can be ignored safely
    $url = rtrim((string) $url, "&?/");
    
    return $url;
  }
  
  // @see https://stackoverflow.com/questions/4937478/strip-off-url-parameter-with-php
  public static function strip_param_from_url($url, $params, $sort = true) {
    $url        = strtok($url, "#");            // Remove the fragment
    $base_url   = strtok($url, "?");            // Get the base url

    if($base_url === $url) {                    // Shortcut if there are no parameters
      return $url;
    }

    $parsed_url = parse_url($url);              // Parse it
    $query      = $parsed_url["query"];         // Get the query string
    parse_str($query, $parameters);             // Convert Parameters into array

    foreach($params as $param) {
      if(isset($parameters[$param])) {
        unset($parameters[$param]);             // Delete the one you want
      }
    }

    if($sort) {
      ksort($parameters);                       // Sort remaining params
    }

    $new_query = http_build_query($parameters); // Rebuilt query string
    $url = "{$base_url}?{$new_query}";

    return rtrim($url, "?");                    // Trim possible trailing ?
  }
  
  // Logic also exists in DeHeerHoreca_Fpc_Model_Observer
  public static function get_cache_prefix(): string {
    $cache_key_prefix = (string) Mage::app()->getFrontController()->getAction()->getFullActionName();
    
    if($cache_key_prefix === "catalog_product_view") {
      $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
      $cache_key_prefix .= "_".$id;
    } elseif($cache_key_prefix === "catalog_category_view") {
      $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
      $cache_key_prefix .= "_".$id;
    }
    
    return $cache_key_prefix;
  }
  
  public static function get_cache_tags(): array {
    $cache_tags = [];
    
    if($om_action = Mage::app()->getFrontController()->getAction()->getFullActionName()) {
      $cache_tags[] = "DHH_{$om_action}";
    }
    
    if($om_action === "catalog_product_view") {
      $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
      $cache_tags[] = "DHH_PRODUCT_{$id}";
    } elseif($om_action === "catalog_category_view") {
      $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
      $cache_tags[] = "DHH_CATEGORY_{$id}";
    }
    
    return $cache_tags;
  }
  
  // Determine of the current request is anonymous or logged in
  public static function is_request_anonymous(): bool {
    
    if(!is_null(self::$request_is_anonymous)) {
      return self::$request_is_anonymous;
    }
    
    // No cookie? No login
    if(!isset($_SERVER["HTTP_COOKIE"]) || $_SERVER["HTTP_COOKIE"] === null) {
      self::log("Request is anonymous (no cookies)");
      self::$request_is_anonymous = true;
    }
    
    // More expensive checks
    elseif(Mage::helper("checkout/cart")->getItemsCount() > 0 || Mage::getSingleton("customer/session")->isLoggedIn()) {
      self::log("Request is not anonymous (cart items or logged-in status)");
      self::$request_is_anonymous = false;
    }
    
    // Otherwise, assume it's anonymous
    else {
      self::log("Request is anonymous (assumed))");
      self::$request_is_anonymous = true;
    }
    
    return self::$request_is_anonymous;
  }
  
  /**
   * Check if loading from cache should be bypassed for this request
   *
   * @return bool
   * @throws Zend_Controller_Request_Exception
   */
  protected static function request_has_no_cache_headers(): bool {
    return (
      strstr(strtolower(Mage::app()->getRequest()->getHeader("PRAGMA")), "no-cache") ||
      strstr(strtolower(Mage::app()->getRequest()->getHeader("CACHE_CONTROL")), "no-cache")
    );
  }
  
  /**
   * Determine if the FPC cache is enabled for reading.
   * 
   * This does NOT check if the cache contains the requested page, only if reading from cache is allowed.
   * 
   * @param non_anonymous_okay  bool  Switch to check for anonymous requests (cart block, etc.)
   * @param html_block_mode     bool  For HTML block caching, the controller action is not taken into account
   *
   * @return                    bool  TRUE if reading from cache is allowed
   */
  public static function is_read_cache_enabled(bool $non_anonymous_okay = false, bool $html_block_mode = false, string $debug_name = ""): bool {
    if(!DHH_FPC_ENABLED) {
      self::log("Read cache disabled (DHH_FPC_ENABLED): {$debug_name}");
      return false;
    }
    
    // Protect the cache against bots in layered navigation labyrinths: allow max 2 GET params
    // ath = Aoe_TemplateHints flag
    // is_ajax is by amasty layered nav, and right now we cannot save that HTML (does not pass the page/ phtmls)
    if(isset($_GET["nofpc"]) || isset($_GET["refreshfpc"]) || isset($_GET["is_ajax"]) || isset($_GET["ath"])
    || (!$html_block_mode && is_countable($_GET) && count($_GET) > 2) || self::request_has_no_cache_headers()) {
      self::log("Read cache disabled (by request header or URL param: {$debug_name}");
      return false;
    }
    
    if(PHP_SAPI !== "cli" && ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD")) {
      self::log("Read cache disabled (REQUEST_METHOD): {$debug_name}");
      return false;
    }
    
    if(!$html_block_mode) {
      $om_action = (string) Mage::app()->getFrontController()->getAction()->getFullActionName();
      if(!in_array($om_action, self::$om_action_whitelist, true)) {
        self::log("Read cache disabled (OM action): {$debug_name}");
        return false;
      }
    }
    
    // Temporarily block non-FPC caching
    // if($debug_name !== "fpc") {
    //   return false;
    // }
    
    if(!$non_anonymous_okay && self::is_request_anonymous()) {
      self::log("Read cache disabled (Anonymous not allowed and request is not anonymous): {$debug_name}");
      return false;
    }
    
    self::log("Read cache enabled: {$debug_name}");
    return true;
  }
  
  /*
   * @param non_anonymous_okay  bool    Switch to check for anonymous requests (cart block, etc.)
   * @param html_block_mode     bool    For HTML block caching, the controller action is not taken into account
   */
  public static function is_write_cache_enabled($non_anonymous_okay = false, $html_block_mode = false, $debug_name = ""): bool {
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    if(!DHH_FPC_ENABLED) {
      self::log("Write cache disabled (DHH_FPC_ENABLED): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    // - Allow max 2 GET params
    // - ath      = Aoe_TemplateHints flag
    // - bf       = ???
    // - is_ajax  = amasty layered nav, and right now we cannot save that HTML (does not pass the page/ phtmls)
    if(isset($_GET["nofpc"]) || isset($_GET["is_ajax"]) || isset($_GET["ath"]) || isset($_GET["bf"])
    || (!$html_block_mode && is_countable($_GET) && count($_GET) > 2)) {
      self::log("Write cache disabled (URL parameter): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    if(PHP_SAPI !== "cli" && ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD")) {
      self::log("Write cache disabled (REQUEST_METHOD): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    if($html_block_mode !== true) {
      $action = (string) Mage::app()->getFrontController()->getAction()->getFullActionName();
      if(!in_array($action, self::$om_action_whitelist, true)) {
        self::log("Write cache disabled (OpenMage action): {$debug_name}");
        Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
        return false;
      }
    }
    
    if(!$non_anonymous_okay && !self::is_request_anonymous()) {
      self::log("Write cache disabled (Anonymous not allowed and request is not anonymous): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    self::log("Write cache enabled: {$debug_name}");
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    
    return true;
  }
  
  /**
   * Create an obfuscated, repeatable, Redis-safe key with optional prefix.
   *
   * @param  ?string  $cache_key_prefix  Optional prefix for the cache key
   * @param  ?string  $url               Optional URL overwrite for debug/development
   *
   * @return string
   */
  public static function get_cache_key(?string $cache_key_prefix = null, ?string $url = null): string {
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    $cache_key_url = self::get_cache_url($url);
    if(empty($cache_key_prefix)) {
      $cache_key_prefix = self::get_cache_prefix();
    }
    
    $cache_key_url_hash = substr(base_convert(md5($cache_key_url), 16, 32), 0, 12);
    $_cacheKey = "FPC_{$cache_key_prefix}_".base64_encode($cache_key_url_hash);
    
    self::log("Cache URL: {$cache_key_url}");
    self::log("Cache URL hash: {$cache_key_url_hash}, Cache Key Prefix: {$cache_key_prefix}, Cache Key: zc:k:e6b_{$_cacheKey}");
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    return $_cacheKey;
  }
  
  /**
   * Get cached HTML, with hole punching.
   *
   * @param  string  $key                The cache key.
   * @param  bool    $holepunch_formkey  Whether to holepunch the formkey (CSRF protection).
   * @param  bool    $holepunch_blocks   Essentially, $holepunch_blocks indicates a full HTML page which requires a lot more hole punching.
   * 
   * @return ?string
   */
  public static function get_cached_html(string $key, $holepunch_formkey = true, $holepunch_blocks = true): ?string {
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    $_cache   = Mage::app()->getCache();
    $html     = $_cache->load($key);
    
    if(empty($html)) {
      self::log("Cache MISS: {$key}");
      self::_add_server_timing_header("FPC miss: {$key}");
      return null;
    }
    
    if(!is_string($html)) {
      $html = strval($html);
    }
    $size_raw_key = mb_strlen($html);
    
    $_layout  = Mage::app()->getLayout();
    
    /* Hole punching */
    
    // Formkey (CSRF protection)
    if($holepunch_formkey === true) {
      Varien_Profiler::start("DHH::FPC::Holepunch::formkey");
      $search = "<!-- fpc form_key_placeholder -->";
      $replace = Mage::getSingleton("core/session")->getFormKey();
      if(!empty($replace)) {
        $html = str_replace($search, $replace, $html, $count);
        // $level = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
        self::log("Replaced {$search} {$count} times with ".mb_strlen((string) $replace)." chars");
      }
      Varien_Profiler::stop("DHH::FPC::Holepunch::formkey");
    }
    
    if($holepunch_blocks === true) {
      // "<!-- FPC Cache URL: https://www.chefstore.nl/sencor-41018217-srxd-3105-cent-brush-srv-3150-60.html -->"
      $pos_start = mb_strpos($html, "<!-- FPC Cache URL:");
      if($pos_start !== false) {
        $pos_end = mb_strpos($html, "-->", $pos_start);
        if($pos_end !== false) {
          $html = mb_substr($html, 0, $pos_start).mb_substr($html, $pos_end + 3);
        }
      }
      
      // The sidebar, is PART OF the minicart
      Varien_Profiler::start("DHH::FPC::Holepunch::minicart");
      $minicart_html = $_layout
        ->createBlock("checkout/cart_minicart")
        ->setTemplate("checkout/cart/minicart.phtml")
        ->toHtml();
      // Disabled for performance
      // To re-enable, also make changes to checkout/cart/minicart.phtml
      // $sidebar_html = $_layout
        // ->createBlock("checkout/cart_sidebar")
        // ->setTemplate("checkout/cart/minicart/items.phtml")->toHtml();
      $sidebar_html = "";
      $replace = self::replace_between($minicart_html, "<!-- header_sidebar_start -->", "<!-- header_sidebar_end -->", $sidebar_html);
      $search = "<!-- header_minicart_here -->";
      $html = str_replace($search, $replace, $html, $count);
      // $level = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
      self::log("Replaced {$search} {$count} times with ".mb_strlen((string) $replace)." chars");
      Varien_Profiler::stop("DHH::FPC::Holepunch::minicart");
      
      // CORE_MESSAGES
      // Varien_Profiler::start("DHH::FPC::Holepunch::messages_html");
      $replace  = $_layout
        ->createBlock("core/messages")
        ->setTemplate("page/html/notices.phtml")
        ->toHtml();
      $replace .= $_layout->getMessagesBlock()->toHtml();
      $search   = "<!-- core_messages_here -->";
      $html     = str_replace($search, $replace, $html, $count);
      $level    = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
      self::log("Replaced {$search} {$count} times with ".mb_strlen($replace)." chars", $level);
      // Varien_Profiler::stop("DHH::FPC::Holepunch::messages_html");
      
      // MINIQUOTE
      Varien_Profiler::start("DHH::FPC::Holepunch::miniquote_html");
      $replace = $_layout
        ->createBlock("qquoteadv/checkout_miniquote_miniquote")
        ->setTemplate("qquoteadv/checkout/quote/miniquotehead.phtml")
        ->toHtml();
      $search = "<!-- header_miniquote_here -->";
      $html = str_replace($search, $replace, $html, $count);
      $level = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
      self::log("Replaced {$search} {$count} times with ".mb_strlen((string) $replace)." chars", $level);
      Varien_Profiler::stop("DHH::FPC::Holepunch::miniquote_html");
      
      // // Breadcrumbs block -- only for catalog_product_view
      // Seems impossible: the current product object is not available at this point
      
      // if(DHH_FPC_DEBUG === true) {
        // Varien_Profiler::start("DHH::FPC::Holepunch::breadcrumbs_html");
        // if(Mage::app()->getFrontController()->getAction()->getFullActionName() === "catalog_product_view") {
          // $breadcrumbs_html = $_layout->getBlock("breadcrumbs");
          // $breadcrumbs_html = $_layout
            // ->createBlock("richsnippets/product")
            // ->setTemplate("tm/richsnippets/richsnippets_view.phtml")
            // ->toHtml();
          // // $breadcrumbs_html = $_layout
            // // ->getBlock("catalog/breadcrumbs");
            // // // ->setTemplate("tm/richsnippets/richsnippets_head.phtml")
            // // ->toHtml();
          // $search = "<!-- breadcrumbs_here -->";
          // $html = str_replace($search, $breadcrumbs_html, $html, $count);
          // self::log($breadcrumbs_html);
          // self::log("Replaced {$search} {$count} times (".mb_strlen($breadcrumbs_html)." chars)");
        // }
        // Varien_Profiler::stop("DHH::FPC::Holepunch::breadcrumbs_html");
      // }
      
      // Mage::getSingleton("core/session")->addNotice(Mage::helper("core")->__("Notice ".date("c")));
      // Mage::getSingleton("core/session")->addSuccess(Mage::helper("core")->__("Notice ".date("c")));
      
      // MAIN NAV
      // Varien_Profiler::start("DHH::FPC::Holepunch::nav");
      $replace  = (string) $_cache->load(DHH_FPC_NAV_KEY);
      $search   = "<!-- nav_here -->";
      $html     = str_replace($search, $replace, $html, $count);
      $level    = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
      self::log("Replaced {$search} {$count} times with ".mb_strlen($replace)." chars", $level);
      // Varien_Profiler::stop("DHH::FPC::Holepunch::nav");
      
      // FOOTER
      // Varien_Profiler::start("DHH::FPC::Holepunch::footer");
      $replace  = (string) $_cache->load(DHH_FPC_FOOTER_KEY);
      $search   = "<!-- footer_here -->";
      $html     = str_replace($search, $replace, $html, $count);
      $level    = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
      self::log("Replaced {$search} {$count} times with ".mb_strlen($replace)." chars", $level);
      // Varien_Profiler::stop("DHH::FPC::Holepunch::footer");
    }
    
    $html = trim($html);
    $size = mb_strlen($html);
    self::log("Cache HIT: {$key} (Net: {$size_raw_key} bytes, Gross: {$size} bytes)");
    self::_add_server_timing_header("FPC hit: {$key}");
    self::_emit_server_timing_header();
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    return $html;
  }
  
  public static function save_cached_html(string $key, string $html, bool $holepunch_formkey = true, bool $holepunch_blocks = true, array $cache_tags = []) {
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    // Prevent canonical URL shortening
    $html = str_replace("<link rel=\"canonical\" href=\"https://www.chefstore.nl", "<link rel=\"canonical\" href=\"https://wwww.chefstore.nl", $html);
    
    // Shorten URLs
    $html = str_replace("value=\"https://www.chefstore.nl/", "value=\"/", $html);
    $html = str_replace("src=\"https://www.chefstore.nl/", "src=\"/", $html);
    $html = str_replace("src='https://www.chefstore.nl/", "src='/", $html);
    $html = str_replace("href=\"https://www.chefstore.nl/", "href=\"/", $html);
    $html = str_replace("setLocation('https://www.chefstore.nl/", "setLocation('/", $html);
    $html = str_replace("href='https://www.chefstore.nl/", "href='/", $html);
    
    // Reverse prevent canonical URL shortening
    $html = str_replace("<link rel=\"canonical\" href=\"https://wwww.chefstore.nl", "<link rel=\"canonical\" href=\"https://www.chefstore.nl", $html);
    
    // Unnecessary HTML
    $html = str_replace(" type=\"text/javascript\"", "", $html);
    $html = str_replace(" type=\"text/css\"", "", $html);
    
    // Fix XHTML crap
    $html = str_replace(" />", ">", $html);
    
    // HTML minifier -- Broken:
    // /koelingen/vrieskasten/glasdeurvriezers/vrieskast-1530-l-3-glasdeuren-zwart-lichtbak-combisteel-7455-2435.html
    // https://www.chefstore.nl/service
    // $bytes_pre = mb_strlen($html);
    // $options = [
      // "collapse_whitespace" => false,
      // "disable_comments"    => true,
    // ];
    // try {
      // $minifier = new TinyHtmlMinifier($options);
      // $html = $minifier->minify($html);
      // $bytes_post = mb_strlen($html);
      // self::log("Minifier OK: {$bytes_pre} => {$bytes_post} bytes");
    // } catch(Exception $e) {
      // self::log("Minifier exception: {$e->getMessage()}");
    // }
    
    // Handle form_key (CSRF protection)
    if($holepunch_formkey) {
      $formKey = Mage::getSingleton("core/session")->getFormKey();
      if($formKey) {
        $formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";
        $html = str_replace($formKey, $formKeyPlaceholder, $html, $count);
        // $level = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
        self::log("Replaced form_key {$count} times");
      }
    }
    
    // Replace holepunched content with placeholders
    if($holepunch_blocks) {
      $html = self::replace_between($html, "<!-- header_minicart_start -->", "<!-- header_minicart_end -->", "<!-- header_minicart_here -->");
      $html = self::replace_between($html, "<!-- header_miniquote_start -->", "<!-- header_miniquote_end -->", "<!-- header_miniquote_here -->");
      $html = self::replace_between($html, "<!-- header_sidebar_start -->", "<!-- header_sidebar_end -->", "<!-- header_sidebar_here -->");
      $html = self::replace_between($html, "<!-- core_messages_start -->", "<!-- core_messages_end -->", "<!-- core_messages_here -->");
      // $html = self::replace_between($html, "<!-- breadcrumbs_start -->", "<!-- breadcrumbs_end -->", "<!-- breadcrumbs_here -->");
      $html = self::replace_between($html, "<!-- nav_start -->", "<!-- nav_end -->", "<!-- nav_here -->");
      $html = self::replace_between($html, "<!-- footer_start -->", "<!-- footer_end -->", "<!-- footer_here -->");
    }
    
    // If we detect a whole HTML page (not a partial page), add the cache URL as a comment
    if(strpos((string) $html, "<html") !== false && strpos((string) $html, "</html>") !== false) {
      $cache_url = self::get_cache_url();
      $html .= "\n<!-- FPC Cache URL: {$cache_url} -->\n";
    }
    
    // Prepare cache tags
    $cache_tags[] = "DHH_FPC";
    
    // Store in cache
    if(Mage::app()->getCache()->save($html, $key, $cache_tags, 7 * 86400)) {
      self::log("Cache: SAVED {$key}, ".mb_strlen((string) $html)." chars");
      self::_add_server_timing_header("FPC saved: {$key}");
      self::_emit_server_timing_header();
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return true;
    }
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    return false;
  }
  
  /**
   * Replace content between two needles in a string
   *
   * @param  string  $str
   * @param  string  $needle_start
   * @param  string  $needle_end
   * @param  string  $replacement
   * 
   * @return string|array
   */
  public static function replace_between(string $str, string $needle_start, string $needle_end, string $replacement): string|array {
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    $pos_start = strpos((string) $str, (string) $needle_start);
    if($pos_start === false) {
      self::log(__METHOD__.": {$needle_start} not found!", Zend_Log::DEBUG);
      return $str;
    }
    
    $start    = $pos_start === false ? 0 : $pos_start;
    $pos_end  = strpos((string) $str, (string) $needle_end, $start);
    if($pos_end === false) {
      self::log(__METHOD__.": {$needle_end} not found!", Zend_Log::DEBUG);
      return $str;
    }
    
    $end = $pos_end === false ? mb_strlen((string) $str) : $pos_end + mb_strlen((string) $needle_end);
    
    self::log(__METHOD__.": ".htmlentities((string) $needle_start).":: Start = {$start}, End = {$end}");
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    return substr_replace((string) $str, (string) $replacement, $start, $end - $start);
  }
  
  /**
   * Write a single log message to the FPC plaintext log for debug purposes. Only active if DHH_FPC_DEBUG is true.
   *
   * @param  mixed $msg
   * @param  mixed $level
   * 
   * @return void
   */
  public static function log($msg, $level = Zend_Log::DEBUG): void {
    if(DHH_FPC_DEBUG || $level !== Zend_Log::DEBUG) {
      Mage::log($msg, $level, "fpc.txt", true);
    }
  }
  
  /**
   * Add a Server-Timing header value to be emitted later.
   *
   * @param  string $string
   */
  public static function _add_server_timing_header(string $string) {
    if(headers_sent()) {
      return false;
    }
    $GLOBALS["dhh_header_server_timing"] ??= [];
    $GLOBALS["dhh_header_server_timing"][] = $string;
  }
  
  /**
   * Emit any saved HTTP headers, if possible.
   *
   * @return false|true|null
   */
  public static function _emit_server_timing_header(): bool|null {
    if(headers_sent()) {
      return false;
    }
    
    $GLOBALS["dhh_header_server_timing"] ??= [];
    
    if(!empty($GLOBALS["dhh_header_server_timing"])) {
      header("Server-Timing: ".implode("; ", (array) $GLOBALS["dhh_header_server_timing"]));
      unset($GLOBALS["dhh_header_server_timing"]);
      return true;
    }
    
    return null;
  }
  
  /**
   * Clean cache entries by their tags.
   *
   * - Usage: DeHeerHoreca_Fpc_Helper_Data::_clean_by_tags(["foo", "bar"])
   * - Do NOT use prefixes like zc:ti:, adds "e6b_" if needed
   *
   * @param  string|array  $cache_tags
   *
   * @return bool
   */
  public static function clean_by_tags(string|array $cache_tags): bool {
    $cache_tags = (array) $cache_tags;
    
    // Prepend with e6b_ if needed. Redis library does NOT do this.
    $cache_tags = Arr::map($cache_tags, fn($tag) => Str::start($tag, "e6b_"));
    
    // @notice without getBackend() it does not work!
    if(DHH_FPC_DEBUG) {
      $cache_keys = Mage::app()->getCache()->getBackend()->getIdsMatchingAnyTags($cache_tags);
      Mage::log("Cleaning cache tags: ".var_export($cache_tags, true).". Matched keys: ".var_export($cache_keys, true), Zend_Log::DEBUG, "verbose.txt", true);
    }
    
    $response = Mage::app()->getCache()->getBackend()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $cache_tags);
    if(DHH_FPC_DEBUG) {
      Mage::log("Response: ".var_export($response, true), Zend_Log::DEBUG, "verbose.txt", true);
    }
    
    return $response;
  }
  
  /**
   * @deprecated use clean_by_tags()
   *
   * @param  mixed ...$args
   *
   * @return bool
   */
  public static function _clean_by_keys(...$args): bool {
    return self::clean_by_tags($args);
  }
}
