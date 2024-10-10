<?php

/**
 * Helper
 *
 * @author Fabrizio Branca
 * @since  2013-05-23
 */

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
    
    // // Beaufiul, is it not?
    // echo "<pre>";
    // echo "------- Clearing Redis FPC cache --------".PHP_EOL.PHP_EOL;
    // echo shell_exec("redis-cli --scan --pattern zc:k:e6b_FPC* | xargs redis-cli del");
    // echo PHP_EOL."----- Done clearing Redis FPC cache -----".PHP_EOL;
    // echo "</pre>";
    
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
  public function get_cache_url(string $url = ""): string {
    if($url === "") {
      $url = html_entity_decode((string) Mage::helper("core/url")->getCurrentUrl());
    }
    
    // List of query parameters that have no consequences for the rendered HTML
    $ignored_url_query_keys = [
      "sqr", "profile", "___store", "refreshfpc", "__cf_chl_jschl_tk__",
      "utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term",
      "gclid", "gbraid", "wbraid", "cfhtmlcache", "mc_cid", "mc_eid",
      "cstag", "title", "srsltid",
    ];
    $url = self::strip_param_from_url($url, $ignored_url_query_keys);
    
    // Remove things that can be ignored safely
    $url = rtrim((string) $url, "&?/");
    
    return $url;
  }
  
  // @see https://stackoverflow.com/questions/4937478/strip-off-url-parameter-with-php
  public function strip_param_from_url($url, $params, $sort = true) {
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
  public function get_cache_prefix(): string {
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
  public function is_request_anonymous(): bool {
    
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
  
  /*
   * @param non_anonymous_okay  bool    Switch to check for anonymous requests (cart block, etc.)
   * @param html_block_mode     bool    For HTML block caching, the controller action is not taken into account
   */
  public function is_read_cache_enabled(bool $non_anonymous_okay = false, bool $html_block_mode = false, string $debug_name = ""): bool {
    
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    if(!DHH_FPC_ENABLED) {
      self::log("Read cache disabled (DHH_FPC_ENABLED): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    // ath = Aoe_TemplateHints flag
    // is_ajax is by amasty layered nav, and right now we cannot save that HTML (does not pass the page/ phtmls)
    if(isset($_GET["nofpc"]) || isset($_GET["refreshfpc"]) || isset($_GET["is_ajax"]) || isset($_GET["ath"])) {
      self::log("Read cache disabled (URL parameter): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    if(PHP_SAPI !== "cli" && ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD")) {
      self::log("Read cache disabled (REQUEST_METHOD): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    if(!$html_block_mode) {
      $om_action = (string) Mage::app()->getFrontController()->getAction()->getFullActionName();
      if(!in_array($om_action, self::$om_action_whitelist, true)) {
        self::log("Read cache disabled (OM action): {$debug_name}");
        Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
        return false;
      }
    }
    
    // Temporarily block non-FPC caching
    // if($debug_name !== "fpc") {
    //   Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    //   return false;
    // }
    
    if($non_anonymous_okay === false && Mage::helper("deheerhoreca_fpc/data")->is_request_anonymous() === false) {
      self::log("Read cache disabled (Anonymous not allowed and request is not anonymous): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    self::log("Read cache enabled: {$debug_name}");
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    
    return true;
  }
  
  /*
   * @param non_anonymous_okay  bool    Switch to check for anonymous requests (cart block, etc.)
   * @param html_block_mode     bool    For HTML block caching, the controller action is not taken into account
   */
  public function is_write_cache_enabled($non_anonymous_okay = false, $html_block_mode = false, $debug_name = ""): bool {
    
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    if(!DHH_FPC_ENABLED) {
      self::log("Write cache disabled (DHH_FPC_ENABLED): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    // ath = Aoe_TemplateHints flag
    // is_ajax is by amasty layered nav, and right now we cannot save that HTML (does not pass the page/ phtmls)
    if(isset($_GET["nofpc"]) || isset($_GET["is_ajax"]) || isset($_GET["ath"])) {
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
        self::log("Write cache disabled (Magento action): {$debug_name}");
        Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
        return false;
      }
    }
    
    if($non_anonymous_okay === false && Mage::helper("deheerhoreca_fpc/data")->is_request_anonymous() === false) {
      self::log("Read cache disabled (Anonymous not allowed and request is not anonymous): {$debug_name}");
      Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
      return false;
    }
    
    self::log("Write cache enabled: {$debug_name}");
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    
    return true;
  }
  
  // Optionally takes a URL for debug/dev
  public function get_cache_key(string $cache_key_prefix = "", string $url = ""): string {
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    $cache_key_url = Mage::helper("deheerhoreca_fpc/data")->get_cache_url($url);
    
    if(empty($cache_key_prefix)) {
      $cache_key_prefix = Mage::helper("deheerhoreca_fpc/data")->get_cache_prefix();
    }
    
    $cache_key_url_hash = substr(base_convert(md5($cache_key_url), 16, 32), 0, 12);
    $_cacheKey = "FPC_{$cache_key_prefix}_".base64_encode($cache_key_url_hash);
    
    if(DHH_FPC_DEBUG === true) {
      self::log("Cache URL: {$cache_key_url}");
      self::log("Cache URL hash: {$cache_key_url_hash}, Cache Key Prefix: {$cache_key_prefix}, Cache Key: zc:k:e6b_{$_cacheKey}");
    }
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    
    return $_cacheKey;
  }
  
  public function get_cached_html(string $key, $holepunch_formkey = true, $holepunch_blocks = true) {
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    $html = Mage::app()->getCache()->load($key);
    
    if(empty($html)) {
      self::log("Cache MISS: {$key}");
      self::_add_server_timing_header("FPC miss");
      return null;
    }
    
    if(DHH_FPC_DEBUG) {
      $size_raw_key = strlen((string) $html);
    }
    
    /* Hole punching */
    
    // Formkey (CSRF protection)
    
    if($holepunch_formkey === true) {
      Varien_Profiler::start("DHH::FPC::Holepunch::formkey");
      $search = "<!-- fpc form_key_placeholder -->";
      $replacement = Mage::getSingleton("core/session")->getFormKey();
      if(empty($replacement) === false) {
        $html = str_replace($search, $replacement, (string) $html, $count);
        self::log("Replaced {$search} {$count} times with ".strlen((string) $replacement)." chars");
      }
      Varien_Profiler::stop("DHH::FPC::Holepunch::formkey");
    }
    
    if($holepunch_blocks === true) {
      
      // The sidebar, is PART OF the minicart
      
      Varien_Profiler::start("DHH::FPC::Holepunch::minicart");
      $minicart_html = Mage::app()
        ->getLayout()
        ->createBlock("checkout/cart_minicart")
        ->setTemplate("checkout/cart/minicart.phtml")
        ->toHtml();
      // Disabled for performance
      // To re-enable, also make changes to checkout/cart/minicart.phtml
      // $sidebar_html = Mage::app()
        // ->getLayout()
        // ->createBlock("checkout/cart_sidebar")
        // ->setTemplate("checkout/cart/minicart/items.phtml")->toHtml();
      $sidebar_html = "";
      $replacement = self::replace_between($minicart_html, "<!-- header_sidebar_start -->", "<!-- header_sidebar_end -->", $sidebar_html);
      $search = "<!-- header_minicart_here -->";
      $html = str_replace($search, $replacement, (string) $html, $count);
      self::log("Replaced {$search} {$count} times with ".strlen((string) $replacement)." chars");
      Varien_Profiler::stop("DHH::FPC::Holepunch::minicart");
      
      // core_messages
      
      Varien_Profiler::start("DHH::FPC::Holepunch::messages_html");
      $replacement = Mage::app()
        ->getLayout()
        ->createBlock("core/messages")
        ->setTemplate("page/html/notices.phtml")->toHtml();
      $replacement .= Mage::app()->getLayout()->getMessagesBlock()->toHtml();
      // var_dump($replacement);exit;
      $search = "<!-- core_messages_here -->";
      $html = str_replace($search, $replacement, $html, $count);
      self::log("Replaced {$search} {$count} times with ".strlen($replacement)." chars");
      Varien_Profiler::stop("DHH::FPC::Holepunch::messages_html");
      // miniquote
      
      Varien_Profiler::start("DHH::FPC::Holepunch::miniquote_html");
      $replacement = Mage::app()
        ->getLayout()
        ->createBlock("qquoteadv/checkout_miniquote_miniquote")
        ->setTemplate("qquoteadv/checkout/quote/miniquotehead.phtml")
        ->toHtml();
      $search = "<!-- header_miniquote_here -->";
      $html = str_replace($search, $replacement, $html, $count);
      self::log("Replaced {$search} {$count} times with ".strlen((string) $replacement)." chars");
      Varien_Profiler::stop("DHH::FPC::Holepunch::miniquote_html");
      
      // // Breadcrumbs block -- only for catalog_product_view
      // Seems impossible: the current product object is not available at this point
      
      // if(DHH_FPC_DEBUG === true) {
        // Varien_Profiler::start("DHH::FPC::Holepunch::breadcrumbs_html");
        // if(Mage::app()->getFrontController()->getAction()->getFullActionName() === "catalog_product_view") {
          // $breadcrumbs_html = Mage::app()->getLayout()->getBlock("breadcrumbs");
          // $breadcrumbs_html = Mage::app()
            // ->getLayout()
            // ->createBlock("richsnippets/product")
            // ->setTemplate("tm/richsnippets/richsnippets_view.phtml")
            // ->toHtml();
            
            
          // // $breadcrumbs_html = Mage::app()
            // // ->getLayout()
            // // ->getBlock("catalog/breadcrumbs");
            
            
          
            // // // ->setTemplate("tm/richsnippets/richsnippets_head.phtml")
            // // ->toHtml();
          // $search = "<!-- breadcrumbs_here -->";
          // $html = str_replace($search, $breadcrumbs_html, $html, $count);
          // self::log($breadcrumbs_html);
          // self::log("Replaced {$search} {$count} times (".strlen($breadcrumbs_html)." chars)");
        // }
        // Varien_Profiler::stop("DHH::FPC::Holepunch::breadcrumbs_html");
      // }
      
      // Mage::getSingleton("core/session")->addNotice(Mage::helper("core")->__("Notice ".date("c")));
      // Mage::getSingleton("core/session")->addSuccess(Mage::helper("core")->__("Notice ".date("c")));
      
      // Main nav
      Varien_Profiler::start("DHH::FPC::Holepunch::nav");
      $replacement = $_html = Mage::app()->getCache()->load(DHH_FPC_NAV_KEY);
      $search = "<!-- nav_here -->";
      $html = str_replace($search, $replacement, $html, $count);
      self::log("Replaced {$search} {$count} times with ".strlen((string) $replacement)." chars");
      Varien_Profiler::stop("DHH::FPC::Holepunch::nav");
      
      // Footer
      Varien_Profiler::start("DHH::FPC::Holepunch::footer");
      $replacement = $_html = Mage::app()->getCache()->load(DHH_FPC_FOOTER_KEY);
      $search = "<!-- footer_here -->";
      $html = str_replace($search, $replacement, $html, $count);
      self::log("Replaced {$search} {$count} times with ".strlen((string) $replacement)." chars");
      Varien_Profiler::stop("DHH::FPC::Holepunch::footer");
    }
    
    if(DHH_FPC_DEBUG === true) {
      $size = strlen((string) $html);
      self::log("Cache HIT: {$key} (Net: {$size_raw_key} bytes, Gross: {$size} bytes)");
    }
    self::_add_server_timing_header("FPC hit");
    self::_emit_server_timing_header();
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    
    return $html;
  }
  
  public function save_cached_html(string $key, string $html, bool $holepunch_formkey = true, bool $holepunch_blocks = true, array $cache_tags = []) {
    
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
    
    // Fix XHTML crap
    $html = str_replace(" />", ">", $html);
    
    // HTML minifier -- Broken:
    // /koelingen/vrieskasten/glasdeurvriezers/vrieskast-1530-l-3-glasdeuren-zwart-lichtbak-combisteel-7455-2435.html
    // https://www.chefstore.nl/service
    // $bytes_pre = strlen($html);
    // $options = [
      // "collapse_whitespace" => false,
      // "disable_comments"    => true,
    // ];
    // try {
      // $minifier = new TinyHtmlMinifier($options);
      // $html = $minifier->minify($html);
      // $bytes_post = strlen($html);
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
    
    // $cache_tags[] = "quickndirtyfpc";
    $cache_tags[] = "DHH_FPC";
    
    // Store in cache
    if(Mage::app()->getCache()->save($html, $key, $cache_tags, 7 * 86400)) {
      self::log("Cache: SAVED {$key}, ".strlen($html)." chars");
      self::_add_server_timing_header("FPC saved");
      self::_emit_server_timing_header();
      return true;
    }
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    
    return false;
  }
  
  public function replace_between($str, $needle_start, $needle_end, $replacement) {
    
    Varien_Profiler::start("DHH::FPC::".self::class."::".__METHOD__);
    
    $pos_start  = strpos((string) $str, (string) $needle_start);
    if($pos_start === false) {
      self::log(__METHOD__.": {$needle_start} not found!");
      return $str;
    }
    
    $start      = $pos_start === false ? 0 : $pos_start;
    $pos_end    = strpos((string) $str, (string) $needle_end, $start);
    
    if($pos_end === false) {
      self::log(__METHOD__.": {$needle_end} not found!");
      return $str;
    }
    
    $end        = $pos_end === false ? strlen((string) $str) : $pos_end + strlen((string) $needle_end);
    
    self::log(__METHOD__.": ".htmlentities((string) $needle_start).":: Start = {$start}, End = {$end}");
    
    Varien_Profiler::stop("DHH::FPC::".self::class."::".__METHOD__);
    
    return substr_replace((string) $str, (string) $replacement, $start, $end - $start);
  }
  
  static function log($msg): void {
    if(DHH_FPC_DEBUG) {
        Mage::log($msg, Zend_Log::DEBUG, "fpc.log", true);
    }
  }
  
  static function _add_server_timing_header(string $string) {
    if(headers_sent()) {
      return false;
    }
      
    if(isset($GLOBALS["dhh_header_server_timing"]) === false) {
      $GLOBALS["dhh_header_server_timing"] = [];
    }
    
    $GLOBALS["dhh_header_server_timing"][] = $string;
  }
  
  static function _emit_server_timing_header() {
    if(headers_sent()) {
      return false;
    }
    
    if(isset($GLOBALS["dhh_header_server_timing"]) && is_array($GLOBALS["dhh_header_server_timing"])) {
      header("Server-Timing: ".implode("; ", $GLOBALS["dhh_header_server_timing"]));
      unset($GLOBALS["dhh_header_server_timing"]);
      return true;
    }
    
    return null;
  }
  
  // Usage: DeHeerHoreca_Fpc_Helper_Data::_clean_by_tags(["foo", "bar"])
  // Do NOT use prefixes like zc:ti:e6b_
  public static function clean_by_tags(string|array $cache_tags) {
    $cache_tags = (array) $cache_tags;
    
    if(DHH_FPC_DEBUG) {
      $cache_keys = Mage::app()->getCache()->getIdsMatchingAnyTags($cache_tags);
      Mage::log("Cleaning cache tags: ".var_export($cache_tags, true).". Matched keys: ".var_export($cache_keys, true), Zend_Log::DEBUG, "verbose.txt", true);
    }
    
    $response = Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $cache_tags);
    if(DHH_FPC_DEBUG) {
      Mage::log("Response: ".var_export($response, true), Zend_Log::DEBUG, "verbose.txt", true);
    }
    
    return $response;
  }
  
  // Usage: DeHeerHoreca_Fpc_Helper_Data::_clean_by_keys(["foo", "bar"])
  // Do NOT use prefixes like zc:ti:e6b_
  // @deprecated use clean_by_tags()
  public static function _clean_by_keys(...$args) {
    return self::clean_by_tags($args);
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
