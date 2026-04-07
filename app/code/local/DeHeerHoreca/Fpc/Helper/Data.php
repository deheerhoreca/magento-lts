<?php

/*
@todo Use lib/Afterpay/vendor/guzzlehttp/guzzle/src/UriTemplate.php to normalize URLs?
@todo Normalize faulty "?amp%3B": https://www.chefstore.nl/koelingen/koelwerkbanken-saladettes.html?amp%3Bgn_capacity=2537&material_group=2191
@todo Perhaps just use these methods? Mage::app()->saveCache(); Mage::app()->cleanCache();
*/

declare(strict_types=1); // @todo

use \Chefstore\Utils;
use \Illuminate\Support\Arr;
use \Illuminate\Support\Collection;
use \Illuminate\Support\Str;

class DeHeerHoreca_Fpc_Helper_Data extends Mage_Core_Helper_Abstract {
  
  /** @var ?bool Lazy flag indicating whether this request is anonymous or not */
  public static $request_is_anonymous         = null;
  
  public const DHH_FPC_LOG_FILE               = "fpc.txt";
  
  public const PLACEHOLDER_FORMKEY            = "___FPC_FORM_KEY_PLACEHOLDER___";
  public const PLACEHOLDER_FORMKEY_DEPRECATED = "<!-- fpc form_key_placeholder -->";
  
  public const REDIS_CACHE_TAG_PREFIX         = "zc:ti:om";
  public const REDIS_CACHE_KEY_PREFIX         = "zc:k:om";
  
  /** @var array<string,string|string[]> */
  private static array $httpHeaders = [];
  
  /** @var array List of extra tags to add to the cache for this request */
  public static $addTags = [];
  
  /** @var string[] OpenMage action whitelist for FPC caching */
  public static $om_action_whitelist = [
    "catalog_product_view",
    "catalog_category_view",
    "blog_post_view",
    "blog_index_list",
    "cms_page_view",
    "cms_index_index",
    // "amshopby_index_index", // Disabled because we need to tag it properly first
  ];
  
  protected static $_cache = null;
  
  /**
   * Clear the cache
   * @return bool
   */
  public function clearCache(): bool {
    $url = Mage::helper("adminhtml")->getUrl("adminhtml/cache/index");
    echo "<span><a href='{$url}'>Not doing that</a></span><br><br>";
    return true;
  }
  
  /**
   * Trim the cached URL by removing parts that do not affect the cached payload, and normalize the URL for better matching.
   * @todo Keep this up to date to prevent cache fragmentation and blowup
   *
   * Partial list of known URL parameters that do not affect the HTML payload, and should be ignored for caching purposes:
   * -----------------------------------------------------------------------------------------------------------------------
   * - csredir: chefstore.nl redirect indicator.
   * - cfaltconfig: Cloudflare alternative config flag (Page Rules, Rules, etc.)
   * - multipass: Added by LayeredNav if >X values selected to control bots and caching.
   * - sqr: Sooqr search query of the previous page, does not affect the HTML payload.
   * - profile: Server-side OpenMage profiling flag, does not affect the HTML payload.
   * - ___store: OpenMage store switcher parameter, does not affect the HTML payload.
   * - refreshfpc: Used by our cache warmer to force refresh the cache, keep away from cache payload and key.
   * - usg, msclkid, utm_source, utm_medium, utm_campaign, utm_content, utm_term, gclid, mc_cid, mc_eid: No effect on the HTML payload.
   * - multipass: Added by LayeredNav if >X values selected to control bots and caching. Keep out of cache key.
   * - cstag: Chefstore SEO tag, does not affect the HTML payload, cached key should match regardless of presence.
   *
   * @param  string|null  $url  Optional URL overwrite for debug/development, otherwise current URL is used
   * @return string             The normalized cache URL, ready and hashing
   */
  public static function get_cache_url(?string $url = null): string {
    $url ??= getDecodedCurrentUrl();
    
    // List of query parameters that have no consequences for the rendered HTML
    $ignored_url_query_keys = [
      // OpenMage:
      "sqr", "profile", "___store", "refreshfpc", "usg", "msclkid",
      "utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term",
      "gclid", "gbraid", "wbraid", "mc_cid", "mc_eid",
      "cstag", "title", "srsltid", "csredir", "multipass", "opi", "sa", "ved",
      
      // Cloudflare:
      "forcepreload", "forcepreloadonly", "backend", "cfhtmlcache", "__cf_chl_jschl_tk__",
      "cfaltconfig",
    ];
    $url = self::strip_param_from_url($url, $ignored_url_query_keys);
    $url = rtrim($url, "&?/");                // Useless postfixes can be ignored safely
    
    // Fix faulty encoding, and urlencode some characters
    $url = str_replace(
      ["?amp%3B", ","  ],
      [   "?",    "%2C"],
      $url);
    
    return $url;
  }
  
  /**
   * Strip parameters from URL.
   * @todo Replace with a lib.
   * @see https://stackoverflow.com/questions/4937478/strip-off-url-parameter-with-php
   *
   * @param  string  $url
   * @param  array   $params
   * @param  bool    $sort
   *
   * @return string
   */
  private static function strip_param_from_url(string $url, array $params, bool $sort = true): string {
    $url        = strtok($url, "#");            // Remove the fragment
    $base_url   = strtok($url, "?");            // Get the base url
    if($base_url === $url) {                    // Shortcut if there are no parameters
      return $url;
    }
    
    $parsed_url = parse_url($url);
    $query      = $parsed_url["query"];
    $parameters = [];
    
    parse_str($query, $parameters);
    foreach($params as $param) {
      if(isset($parameters[$param])) {
        unset($parameters[$param]);
      }
    }
    
    if($sort) {
      ksort($parameters);
    }
    
    $new_query = http_build_query($parameters); // Rebuilt query string
    $url = "{$base_url}?{$new_query}";
    
    return rtrim($url, "&?/");
  }
  
  /**
   * Get cache key prefix applicable to this request.
   *
   * @return string
   */
  public static function get_cache_prefix(): string {
    $cache_key_prefix = (string) Mage::app()->getFrontController()->getAction()->getFullActionName();
    if($cache_key_prefix === "catalog_product_view" || $cache_key_prefix === "catalog_category_view") {
      $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
      $cache_key_prefix .= "__".$id;
    }
    
    return $cache_key_prefix;
  }
  
  /**
   * Add tags to the list of tags to associate with this request.
   * Duplicate tags will be filtered after all tags are added.
   *
   * @param array $tags
   * @return void
   */
  public static function addTags(array $tags): void {
    self::$addTags = array_merge(self::$addTags, $tags);
  }
  
  /**
   * Get cache tags applicable to this request.
   * > Cache tags (sets) should be uppercased.
   * > Tags should follow OpenMage's native conventions where possible.
   *
   * @return  array
   */
  public static function get_cache_tags(): array {
    $cache_tags = [];
    if($om_action = (string) Mage::app()->getFrontController()->getAction()->getFullActionName()) {
      // $cache_tags[] = strtoupper("DHH_{$om_action}"); // DHH tag
      $cache_tags[] = strtoupper($om_action);         // Native tag
    }
    if($om_action === "catalog_product_view") {
      $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
      $cache_tags[] = strtoupper(Mage_Catalog_Model_Product::CACHE_TAG)."_{$id}";
      // $cache_tags[] = "PRODUCT_{$id}";
    } elseif($om_action === "catalog_category_view") {
      $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
      // $cache_tags[] = "CATALOG_CATEGORY_{$id}";
      $cache_tags[] = strtoupper(Mage_Catalog_Model_Category::CACHE_TAG)."_{$id}";
    }
    
    $cache_tags = collect($cache_tags)->merge(self::$addTags)->unique()->values()->all();
    
    return $cache_tags;
  }
  
  /**
   * Determine if a request is anonymous (not logged in, no cart items).
   *
   * @return bool
   */
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
   * Check if loading from cache should be bypassed for this request, based on request headers.
   * Used by the cache warmer tool in Intel to force cache refreshes, or as needed by browser dev tools.
   *
   * @return bool
   * @throws Zend_Controller_Request_Exception
   */
  public static function request_has_no_cache_headers(): bool {
    static $has_no_cache_headers = null;
    
    // @todo Switch to:
    // if($has_no_cache_headers === null) {
    //   $has_no_cache_headers = sis("no-cache", [
    //     $_SERVER["HTTP_CACHE_CONTROL"] 	?? "",
    //     $_SERVER["HTTP_PRAGMA"] 				?? ""
    //   ]);
    // }
    
    if($has_no_cache_headers === null) {
      // Keep returning FALSE without setting the cache until we can see the Accept header (which is very common).
      if(blank(Mage::app()?->getRequest()?->getHeader("Accept"))) {
        return false;
      }
      $has_no_cache_headers = sis("no-cache", [
        Mage::app()?->getRequest()?->getHeader("Pragma"),
        Mage::app()?->getRequest()?->getHeader("Cache-Control")
      ]);
    }
    
    return $has_no_cache_headers;
  }
  
  /**
   * Implements shared parts of is_read_cache_enabled()/is_write_cache_enabled().
   * A value of FALSE means all DHH caches are NOT disabled, TRUE means they ARE disabled.
   *
   * @param $non_anonymous_okay  bool    Switch to check for anonymous requests (cart block, etc.)
   * @param $isHtmlBlock         bool    For HTML block caching, the controller action is not used as a filter
   * @param $type                string  Cache type for logging and decision purposes.
   *
   * @return bool
   */
  private static function isAnyCacheDisabled(bool $non_anonymous_okay = false, bool $isHtmlBlock = false, string $type = ""): bool {
    if(!DHH_FPC_ENABLED) {
      self::log("FPC cache disabled (by DHH_FPC_ENABLED)");
      return true;
    }
    
    // Disallow listview product blocks. The URLs are messed up.
    if($type === "dhh_listview_product") {
      self::log("FPC cache disabled (by type): {$type}");
      return true;
    }
    
    // Dissallow non-GET/HEAD requests while not in CLI mode.
    if(PHP_SAPI !== "cli" && ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD")) {
      self::log("FPC cache disabled (by REQUEST_METHOD): {$_SERVER["REQUEST_METHOD"]}");
      return true;
    }
    
    return false;
  }
  
  /**
   * Determine if the FPC cache is enabled for reading.
   * This does NOT check if the cache contains the requested page, only if reading from cache is allowed.
   *
   * @param $non_anonymous_okay  bool    Switch to check for anonymous requests (cart block, etc.).
   * @param $isHtmlBlock         bool    For HTML block caching, the controller action is not used as a filter.
   * @param $type                string  Cache type for logging and decision purposes.
   *                                     Known types: dhh_listview_product, get_stock_info, eke_ogmeta,
   *                                     topmenu, footer_html, full_page_cache, tm_richsnippets.
   *
   * @return                     bool    TRUE if reading from cache is allowed
   */
  public static function is_read_cache_enabled(bool $non_anonymous_okay = false, bool $isHtmlBlock = false, string $type = ""): bool {
    static $decision = [];
    if(isset($decision[$type])) {
      return $decision[$type];
    }
    
    // Check if the cache is disabled globally for both reading and writing, for this type:
    if(self::isAnyCacheDisabled($non_anonymous_okay, $isHtmlBlock, $type)) {
      $decision[$type] = false;
      return $decision[$type];
    }
    
    // Check for no-cache headers in the request -- But only if it's a development IP (we saw bots running with those headers)
    if(isDevIp() && self::request_has_no_cache_headers()) {
      self::log("READ disabled (request has no-cache headers): {$type}. ".Mage::app()?->getRequest()?->getHeader("Pragma")." ".Mage::app()?->getRequest()?->getHeader("Cache-Control"));
      $decision[$type] = false;
      return $decision[$type];
    }
    
    // Protect the cache against bots in layered navigation labyrinths: allow max 2 GET params
    // ath = Aoe_TemplateHints flag
    // is_ajax is by amasty layered nav, and right now we cannot save that HTML (does not pass the page/ phtmls)
    if(isset($_GET["nofpc"]) || isset($_GET["refreshfpc"]) || isset($_GET["is_ajax"]) || isset($_GET["ath"])
    || (!$isHtmlBlock && is_countable($_GET) && count($_GET) > 1)) {
      self::log("READ disabled (by request header or URL param: {$type}");
      $decision[$type] = false;
      return $decision[$type];
    }
    
    if(!$isHtmlBlock) {
      $om_action = (string) Mage::app()->getFrontController()->getAction()->getFullActionName();
      if(!in_array($om_action, self::$om_action_whitelist, true)) {
        self::log("READ disabled (OM action): {$type}");
        $decision[$type] = false;
        return $decision[$type];
      }
    }
    
    if(!$non_anonymous_okay && self::is_request_anonymous()) {
      self::log("READ disabled (Anonymous not allowed and request is not anonymous): {$type}");
      $decision[$type] = false;
      return $decision[$type];
    }
    
    self::log("READ enabled: {$type}");
    $decision[$type] = true;
    return $decision[$type];
  }
  
  /**
   * Determine if the FPC cache is enabled for writing.
   *
   * @param  $non_anonymous_okay  bool    Switch to check for anonymous requests (cart block, etc.)
   * @param  $isHtmlBlock         bool    For HTML block caching, the controller action is not used as a filter
   * @param  $type                string  The type of content being cached (for logging and decision purposes)
   *
   * Known types:
   * - dhh_listview_product
   * - get_stock_info
   * - eke_ogmeta
   * - topmenu
   * - footer_html
   * - full_page_cache
   * - tm_richsnippets
   *
   * @return                    bool    TRUE if writing to cache is allowed
   */
  public static function is_write_cache_enabled(bool $non_anonymous_okay, bool $isHtmlBlock, string $type): bool {
    // @todo verify:
      static $decision = [];
    if(isset($decision[$type])) {
      return $decision[$type];
    }
    
    if(self::isAnyCacheDisabled($non_anonymous_okay, $isHtmlBlock, $type)) {
      $decision[$type] = false;
      return false;
    }
    
    // >  Disallow &multipass GET param (layered nav labyrinths overload cache)
    // >  "isAjax" and "ajax" are used by OpenMage core for AJAX requests, leave those alone
    // >  Allow max 2 GET params (layered nav labyrinths overload cache)
    // >  ath        = Aoe_TemplateHints flag (undesirable debug content)
    // >  bf         = ???
    // >  is_ajax    = Amasty layered nav AJAX request only, not cachable yet
    // >  Maybe later: $isAjax = Mage::app()->getRequest()->isAjax();
    if(isset($_GET["nofpc"]) || isset($_GET["multipass"]) || isset($_GET["is_ajax"]) || isset($_GET["ath"]) || isset($_GET["bf"])
    || (!$isHtmlBlock && is_countable($_GET) && count($_GET) > 1)) {
      self::log("WRITE disabled (URL parameter): {$type}");
      $decision[$type] = false;
      return false;
    }
    
    if($isHtmlBlock !== true) {
      $action = (string) Mage::app()->getFrontController()->getAction()->getFullActionName();
      if(!in_array($action, self::$om_action_whitelist, true)) {
        self::log("WRITE disabled (OpenMage action {$action}): {$type}");
        $decision[$type] = false;
        return false;
      }
    }
    
    if(!$non_anonymous_okay && !self::is_request_anonymous()) {
      self::log("WRITE disabled (Anonymous not allowed and request is not anonymous): {$type}");
      $decision[$type] = false;
      return false;
    }
    
    self::log("WRITE enabled: {$type}");
    $decision[$type] = true;
    
    return true;
  }
  
  /**
   * Create an obfuscated, repeatable, Redis-safe key with optional prefix. For whole HTML pages only.
   *
   * @param  ?string  $cache_key_prefix  Optional prefix for the cache key
   * @param  ?string  $url               Optional URL override for debug/development, by default the current URL is used
   *
   * @return string
   */
  public static function get_cache_key(?string $cache_key_prefix = null, ?string $url = null): string {
    $cache_key_url        = self::get_cache_url($url);
    $cache_key_prefix   ??= self::get_cache_prefix();
    $cache_key_url_hash   = substr(base_convert(md5($cache_key_url), 16, 32), 0, 12);
    $cacheKey             = "dhh__{$cache_key_prefix}_".base64_encode($cache_key_url_hash);
    self::log("Normalized cache URL: {$cache_key_url}");
    self::log("Hash: {$cache_key_url_hash}");
    self::log("Cache Key: ".self::REDIS_CACHE_KEY_PREFIX."_{$cacheKey}");
    
    return $cacheKey;
  }
  
  /**
   * Get cached HTML, with hole punching.
   *
   * @param  string       $key                The cache key.
   * @param  bool         $holepunch_formkey  Whether to holepunch the formkey (CSRF protection).
   * @param  bool         $holepunch_blocks   Essentially, $holepunch_blocks indicates a full HTML page which requires a lot more hole punching.
   *
   * @return string|null  The cached HTML, or null if not found.
   */
  public static function get_cached_html(string $key, bool $holepunch_formkey = true, bool $holepunch_blocks = true): ?string {
    Varien_Profiler::start("DHH::FPC::get_cached_html");
    // $_cache   = Mage::app()->getCache();							// Circumvents our modified Cache class
    // $html     = $_cache->load($key);
    $html     = omCacheGet($key);
    
    if(empty($html)) {
      self::log("MISS: {$key}");
      self::addServerTimingHeader("FPC miss: {$key}");
      Varien_Profiler::stop("DHH::FPC::get_cached_html");
      return null;
    }
    
    if(!is_string($html)) {
      $html = strval($html);
    }
    $size_raw_key = mb_strlen($html);
    
    /* HOLE PUNCHING */
    
    // Formkey (CSRF protection)
    if($holepunch_formkey === true) {
      $html = self::personalizeFormkey($html);
    }
    
    if($holepunch_blocks === true) {
      $_layout ??= Mage::app()->getLayout();
      
      // The FPC URL comment appended to the cached HTML
      // "<!-- FPC Cache URL: https://www.chefstore.nl/sencor-41018217-srxd-3105-cent-brush-srv-3150-60.html -->"
      $pos_start = mb_strpos($html, "<!-- FPC Cache URL:");
      if($pos_start !== false) {
        $pos_end = mb_strpos($html, "-->", $pos_start);
        if($pos_end !== false) {
          $html = mb_substr($html, 0, $pos_start).mb_substr($html, $pos_end + 3);
        }
      }
      
      // The sidebar, is PART OF the minicart
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
      
      // CORE_MESSAGES
      $replace  = $_layout
        ->createBlock("core/messages")
        ->setTemplate("page/html/notices.phtml")
        ->toHtml();
      $replace .= $_layout->getMessagesBlock()->toHtml();
      $search   = "<!-- core_messages_here -->";
      $html     = str_replace($search, $replace, $html, $count);
      $level    = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
      self::log("Replaced {$search} {$count} times with ".mb_strlen($replace)." chars", $level);
      
      // MINIQUOTE
      $replace = $_layout
        ->createBlock("qquoteadv/checkout_miniquote_miniquote")
        ->setTemplate("qquoteadv/checkout/quote/miniquotehead.phtml")
        ->toHtml();
      $search = "<!-- header_miniquote_here -->";
      $html = str_replace($search, $replace, $html, $count);
      $level = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
      self::log("Replaced {$search} {$count} times with ".mb_strlen((string) $replace)." chars", $level);
      // // Breadcrumbs block -- only for catalog_product_view
      // Seems impossible: the current product object is not available at this point
      
      // if(DHH_FPC_DEBUG === true) {
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
      // }
      
      // Mage::getSingleton("core/session")->addNotice(Mage::helper("core")->__("Notice ".date("c")));
      // Mage::getSingleton("core/session")->addSuccess(Mage::helper("core")->__("Notice ".date("c")));
      
      // MAIN NAV
      $replace  = (string) Mage::app()->loadCache(DHH_FPC_NAV_KEY); // Supports 2-level APCu caching
      $search   = "<!-- nav_here -->";
      $html     = str_replace($search, $replace, $html, $count);
      $level    = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
      self::log("Replaced {$search} {$count} times with ".mb_strlen($replace)." chars", $level);
      
      // FOOTER
      $replace  = (string) Mage::app()->loadCache(DHH_FPC_FOOTER_KEY); // Supports 2-level APCu caching
      $search   = "<!-- footer_here -->";
      $html     = str_replace($search, $replace, $html, $count);
      $level    = $count > 0 ? Zend_Log::DEBUG : Zend_Log::WARN;
      self::log("Replaced {$search} {$count} times with ".mb_strlen($replace)." chars", $level);
    }
    
    $html = trim($html);
    $size = mb_strlen($html);
    self::log("HIT: {$key} (Net: {$size_raw_key} bytes, Gross: {$size} bytes)");
    self::addServerTimingHeader("FPC hit: {$key}");
    Varien_Profiler::stop("DHH::FPC::get_cached_html");
    
    return $html;
  }
  
  /**
   * Save cached HTML, with hole punching.
   *
   * @todo Find a better way to minify HTML, JBZoo?
   *
   * @param  string $key                The cache key.
   * @param  string $html               The HTML content to cache.
   * @param  bool   $holepunch_formkey  Whether to hole punch the form key.
   * @param  bool   $holepunch_blocks   Whether to hole punch blocks.
   * @param  array  $cache_tags         Cache tags for invalidation.
   *
   * @return bool
   */
  public static function save_cached_html(string $key, string $html, bool $holepunch_formkey = true, bool $holepunch_blocks = true, array $cache_tags = []): bool {
    Varien_Profiler::start("DHH::FPC::save_cached_html  {$key}");
    
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
    
    // Handle form_key (CSRF protection)
    if($holepunch_formkey) {
      $html = self::anonymizeFormkey($html);
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
    $cache_url = "";
    if(str_contains($html, "<html") && str_contains($html, "</html>")) {
      $cache_url = self::get_cache_url();
      $html .= "\n<!-- FPC Cache URL: {$cache_url} -->\n";
    }
    
    // Prepare cache tags
    // $cache_tags[] = "DHH_FPC"; // Removed: Simplifying cache tag management by relying on OM native tags as much as possible
    
    // Store in cache
    if(self::saveToCacheDeferred($key, $html, $cache_tags, 7 * 86400, minifyHtml: false)) {
      self::addServerTimingHeader("FPC: SAVE {$key}");
      $return = true;
    }
    Varien_Profiler::stop("DHH::FPC::save_cached_html  {$key}");
    
    return $return ?? false;
  }
  
  /**
   * Generic method (no hole punching) to save data to cache. Not meant for full HTML pages.
   *
   * @param  string  $key         The cache key.
   * @param  mixed   $data        The data to cache.
   * @param  array   $cache_tags  Cache tags for invalidation.
   * @param  int     $lifetime    Cache lifetime in seconds.
   * @param  bool    $minifyHtml  Whether to conservatively minify as HTML before saving.
   *
   * @return bool    TRUE if saving to cache was successful.
   */
  public static function saveToCache(string $key, mixed $data, array $cache_tags = [], int $lifetime = 86400, bool $minifyHtml = false): bool {
    Varien_Profiler::start("DHH::FPC::saveToCache  {$key}");
    
    if($minifyHtml === true && is_string($data)) {
      $data = \Chefstore\Html::minifyHtml($data);
    }
    
    // Save via our own function to enable CacheStats and 2-level caching with APCu.
    if(omCacheSave($data, $key, $cache_tags, $lifetime)) {
      self::log("SAVED {$key}");
      $return = true;
    }
    
    // // This circumvents our modified Cache class, preventing APCu caching:
    // if(Mage::app()->getCache()->save($data, $key, $cache_tags, $lifetime)) {
    //   self::log("SAVED {$key}");
    //   $return = true;
    // }
    
    Varien_Profiler::stop("DHH::FPC::saveToCache  {$key}");
    return $return ?? false;
  }
  
  /**
   * Generic method (no hole punching) to save data to cache, after the response is sent. Not meant for full HTML pages.
   *
   * @param  string  $key         The cache key.
   * @param  mixed   $data        The data to cache.
   * @param  array   $cache_tags  Cache tags for invalidation.
   * @param  int     $lifetime    Cache lifetime in seconds.
   * @param  bool    $minifyHtml  Whether to conservatively minify as HTML before saving.
   *
   * @return true    The deferred saving was scheduled.
   */
  public static function saveToCacheDeferred(string $key, mixed $data, array $cache_tags = [], int $lifetime = 86400, bool $minifyHtml = false): true {
    Varien_Profiler::start("DHH::FPC::saveToCacheDeferred  {$key}");
    $closure = fn() => self::saveToCache($key, $data, $cache_tags, $lifetime, $minifyHtml);
    Utils::deferClosure($closure);
    self::log("(Deferred) SAVE {$key}");
    Varien_Profiler::stop("DHH::FPC::saveToCacheDeferred  {$key}");
    
    return true;
  }
  
  /**
   * Clean cache entries by their tags, after the response is sent.
   * 
   * @param  array  $cache_tags  The cache tags to clean by.
   * @return true                The deferred cleaning was scheduled.
   */
  public static function cleanCacheByTagsDeferred(array $cache_tags): true {
    Varien_Profiler::start("DHH::FPC::cleanCacheByTagsDeferred");
    
    if(empty($cache_tags)) {
      Mage::log("No cache tags provided to cleanCacheByTagsDeferred()", Zend_Log::WARN);
      Varien_Profiler::stop("DHH::FPC::cleanCacheByTagsDeferred");
      return true;
    }
    
    $closure = fn() => self::clean_by_tags($cache_tags);
    Utils::deferClosure($closure);
    self::log("(Deferred) CLEAN tags: ".di($cache_tags), Zend_Log::INFO);
    
    Varien_Profiler::stop("DHH::FPC::cleanCacheByTagsDeferred");
    return true;
  }
  
  /**
   * Holepunch the formkey by replacing it with a placeholder before storing in cache.
   *
   * @param  string  $html  The personalized HTML content.
   * @return string         The anonymized HTML content.
   */
  public static function anonymizeFormkey(string $html): string {
    if($formKey = Mage::getSingleton("core/session")->getFormKey()) {
      $html = str_replace($formKey, self::PLACEHOLDER_FORMKEY, $html, $count);
      self::log("Punched form_key {$count} times");
    }
    return $html;
  }
  
  /**
   * Fill the formkey placeholder with the current formkey value.
   *
   * @param  string  $html  The anonymized HTML content.
   * @return string         The personalized HTML content.
   */
  public static function personalizeFormkey(string $html): string {
    if($formKey = Mage::getSingleton("core/session")->getFormKey()) {
      $html = str_replace(self::PLACEHOLDER_FORMKEY, $formKey, $html, $count);
      self::log("Filled form_key {$count} times");
    }
    return $html;
  }
  
  /**
   * Replace content between two markers with replacement content.
   *
   * @param  string  $html         The original HTML content.
   * @param  string  $start        The start marker.
   * @param  string  $end          The end marker.
   * @param  string  $replacement  The replacement content.
   *
   * @return string
   */
  public static function replace_between(string $str, string $needle_start, string $needle_end, string $replacement): string|array {
    $pos_start = strpos((string) $str, (string) $needle_start);
    if($pos_start === false) {
      self::log("replace_between(): {$needle_start} not found!", Zend_Log::DEBUG);
      return $str;
    }
    
    $start    = $pos_start === false ? 0 : $pos_start;
    $pos_end  = strpos((string) $str, (string) $needle_end, $start);
    if($pos_end === false) {
      self::log("replace_between(): {$needle_end} not found!", Zend_Log::DEBUG);
      return $str;
    }
    
    $end = $pos_end === false ? mb_strlen((string) $str) : $pos_end + mb_strlen((string) $needle_end);
    self::log("replace_between(): ".htmlentities((string) $needle_start).":: Start = {$start}, End = {$end}");
    return substr_replace((string) $str, (string) $replacement, $start, $end - $start);
  }
  
  /**
   * Write a single log message to the FPC plaintext log for debug purposes. Only active if DHH_FPC_DEBUG=true or the log level is elevated.
   * @todo Save log messages and store them in a core_app_run_after event to prevent slowdowns due to file I/O.
   * @todo Possibly add a shutdown_function as a backup to the core_app_run_after event.
   *
   * @param  mixed  $msg
   * @param  int    $level
   *
   * @return void
   */
  public static function log($msg, int $level = Zend_Log::DEBUG): void {
    if(DHH_FPC_DEBUG || $level !== Zend_Log::DEBUG) {
      Mage::log($msg, $level, "fpc.txt", true);
    }
  }
  
  /**
   * Add a Server-Timing header value to be emitted later.
   *
   * @param   string  $string
   * @return  void
   */
  private static function addServerTimingHeader(string $string): void {
    self::$httpHeaders["Server-Timing"] ??= [];
    self::$httpHeaders["Server-Timing"][] = $string;
  }
  
  /**
   * Emit any saved HTTP headers, if possible.
   *
   * @return false|true|null
   */
  public static function emitHttpHeaders(): bool|null {
    // To avoid issues with proxies and CDNs, always set Last-Modified on HTML pages.
    // If the cart is loaded via AJAX, we might reconsider.
    // Any experiment with caching HTML in Cloudflare has been abandoned for now.
    self::$httpHeaders["Last-Modified"] ??= date("r");
    self::$httpHeaders["Cache-Control"] ??= "no-cache, private, max-age=30";
    
    if(empty(self::$httpHeaders)) {
      return null;
    }
    if(headers_sent()) {
      Mage::log("Cannot emit HTTP headers from DHH FPC Helper, headers already sent!", Zend_Log::WARN, "fpc.txt", true);
      return false;
    }
    
    // Disable Nginx response buffering
    // @see https://github.com/colinmollenhour/Cm_Diehard/blob/9deec69dad2a33afc850cc7f0022bbdb158dbeb5/code/Model/Backend/Local.php
    // Mage::app()->getResponse()->setHeader("X-Accel-Buffering", "no", replace: true);
    self::$httpHeaders["X-Accel-Buffering"] = "no";
    ini_set("zlib.output_compression", "Off");
    
    // devLog("HTTP headers before emitHttpHeaders: ".di(Mage::app()->getResponse()->getHeaders()), Zend_Log::INFO);
    foreach(self::$httpHeaders as $header_name => $header_values) {
      $header_value = implode(", ", (array) $header_values);
      Mage::app()->getFrontController()->getResponse()->setHeader($header_name, $header_value, replace: false);
    }
    // devLog("HTTP headers after emitHttpHeaders: ".di(Mage::app()->getResponse()->getHeaders()), Zend_Log::INFO);
    self::$httpHeaders = [];
    
    return null;
  }
  
  /**
   * Clean cache entries by their tags.
   * - Usage: DeHeerHoreca_Fpc_Helper_Data::_clean_by_tags(["foo", "bar"])
   * - Do NOT use prefixes like zc:ti:, adds "dd6_" if needed
   *
   * @param  string|array  $cache_tags
   * @return bool
   */
  public static function clean_by_tags(string|array $cache_tags): bool {
    // if(self::$_cache === null) {
    //   /** @var Mage_Core_Model_Cache */
    //   self::$_cache = Mage::app()->getCacheInstance();
    // }
    
    static $cache_id_prefix = null;
    if($cache_id_prefix === null) {
      $cache_id_prefix = Mage::app()->getCache()->getOption(["id_prefix"]);
    }
    
    return Mage::app()->getCache()->clean("matchingTag", $cache_tags);
    
    // Prepend with dd6_ if needed. Redis library does NOT do this.
    $cache_tags = Arr::map((array) $cache_tags, fn($tag) => Str::start($tag, $cache_id_prefix));
    $cache_tags = array_values(array_unique($cache_tags)); // In mass updates, might have duplicates
    self::log("CLEAN tags: ".di($cache_tags), Zend_Log::INFO);
    
    // ! without getBackend() it does not work!
    if(DHH_FPC_DEBUG) {
      /** @var Mage_Core_Model_Cache */
      $omCache = Mage::app()->getCacheInstance();
      /** @var Cm_Cache_Backend_Redis $om_cache */
      $omCacheFrontend = $omCache->getFrontend();
      // $cache_keys = Mage::app()->getCache()->getBackend()->getIdsMatchingAnyTags($cache_tags);
      $cache_keys = $omCacheFrontend->getIdsMatchingAnyTags($cache_tags);
      self::log("CLEAN tags: ".di($cache_tags).". Matched keys: ".di($cache_keys), Zend_Log::INFO);
    }
    
    $response = Mage::app()->getCache()->getBackend()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $cache_tags);
    if(DHH_FPC_DEBUG) {
      self::log("Response: ".di($response), Zend_Log::INFO);
    }
    
    return $response;
  }
  
  /**
   * Get url. Unused.
   * 
   * @deprecated
   *
   * @return string
   */
  public function getUrl(): string {
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
}
