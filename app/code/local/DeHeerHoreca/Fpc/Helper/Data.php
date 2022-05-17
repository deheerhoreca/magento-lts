<?php

/**
 * Helper
 *
 * @author Fabrizio Branca
 * @since  2013-05-23
 */

class DeHeerHoreca_Fpc_Helper_Data extends Mage_Core_Helper_Abstract {
    
    public function clearCache() {
      $mode = Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG;
      $tag = "quickndirtyfpc";
      
      // Beaufiul, is it not?
      echo "<pre>";
      echo "------- Clearing Redis FPC cache --------".PHP_EOL.PHP_EOL;
      echo shell_exec("redis-cli --scan --pattern zc:k:e6b_FPC* | xargs redis-cli del");
      echo PHP_EOL."----- Done clearing Redis FPC cache -----".PHP_EOL;
      echo "</pre>";
      
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
      Mage::log("Revalidated ".count($cache)." classes", null, "fpc.log", true);
    }
    
    /**
     * Check url
     *
     * @return bool
     */
    public function checkUrl() {
      $k = base64_decode(Mage::app()->getRequest()->getParam("k"));
      $v = base64_decode(Mage::app()->getRequest()->getParam("v"));
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
          "k"      => base64_encode($k),
          "v"      => base64_encode(Mage::helper("core")->encrypt($k)),
          "_store" => Mage::app()->getDefaultStoreView()->getCode()
        )
      );
    }
    
    // Cleans the cached URL by removing URL parameters that do not affect the payload
    // Otherwise we would cache index.html?sqr=x separately from index.html
    public function get_cache_url() {
      $url = html_entity_decode(Mage::helper("core/url")->getCurrentUrl());
      
      // List of query parameters that have no consequences for the rendered HTML
      $ignored_url_query_keys = [
        "sqr", "profile", "___store", "refreshfpc", "__cf_chl_jschl_tk__",
        "utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term",
        "gclid",
      ];
      $url = self::strip_param_from_url($url, $ignored_url_query_keys);
      
      return $url;
    }
    
    // @see https://stackoverflow.com/questions/4937478/strip-off-url-parameter-with-php
    public function strip_param_from_url($url, $params) {
      $url        = strtok($url, "#");            // Remove the fragment
      $base_url   = strtok($url, "?");            // Get the base url
      
      if($base_url === $url) {              // Shortcut if there are no parameters
        return $url;
      }
      
      $parsed_url = parse_url($url);              // Parse it
      $query      = $parsed_url["query"];              // Get the query string
      parse_str($query, $parameters);            // Convert Parameters into array
      
      foreach($params as $param) {
        if(isset($parameters[$param])) {
          unset($parameters[$param]);             // Delete the one you want
        }
      }
      
      $new_query = http_build_query($parameters); // Rebuilt query string
      $url = "{$base_url}?{$new_query}";
      
      return rtrim($url, "?");                    // Trim possible trailing ?
    }
    
    // Logic also exists in DeHeerHoreca_Fpc_Model_Observer
    public function get_cache_prefix() {
      $cache_key_prefix = Mage::app()->getFrontController()->getAction()->getFullActionName();
      
      if($cache_key_prefix === "catalog_product_view") {
        $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
        $supplier = "";
        // This is bit too heavy unfortunately
        // $rm = Mage::getResourceModel("catalog/product");
        // $value = $rm->getAttributeRawValue($id, "supplier", Mage::app()->getStore()->getStoreId());
        // $collection = Mage::getResourceModel('catalog/product_collection')->addAttributeToSelect("supplier")->addIdFilter($id);
        // $value = $collection->getColumnValues("supplier");
        // if(empty($value) === false) {
          // $supplier = array_pop($value);
        // } else {
          // $supplier = "-nosupplier";
        // }
        $cache_key_prefix .= "{$supplier}_{$id}";
      } elseif($cache_key_prefix === "catalog_category_view") {
        $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
        $cache_key_prefix .= "_".$id;
      }
      
      return $cache_key_prefix;
    }
    
    // Determine of the current request is anonymous or logged in
    public function is_request_anonymous(): bool {
      if(Mage::helper("checkout/cart")->getItemsCount() > 0 || Mage::getSingleton("customer/session")->isLoggedIn()) {
        self::log("Request is not anonymous");
        return false;
      }
      
      if(DHH_FPC_DEBUG === true) {
        self::log("Request is anonymous");
      }
      
      return true;
    }
    
    public function is_read_cache_enabled($non_anonymous_okay = false) {
      
      // is_ajax is by amasty layered nav, and right now we cannot save that HTML (does not pass the page/ phtmls)
      if(isset($_GET["nofpc"]) || isset($_GET["refreshfpc"]) || isset($_GET["is_ajax"])) {
        self::log("Read cache disabled (URL parameter)");
        return false;
      }
      
      if(DHH_FPC_ENABLED === false) {
        self::log("Read cache disabled (DHH_FPC_ENABLED)");
        return false;
      }
      
      if($_SERVER["REQUEST_METHOD"] !== "GET") {
        self::log("Read cache disabled (REQUEST_METHOD)");
        return false;
      }
      
      $_action = Mage::app()->getFrontController()->getAction()->getFullActionName();
      
      if($_action === "cms_index_noRoute" || strstr($_action, "checkout")
      || strstr($_action, "customer") || strstr($_action, "api")
      || strstr($_action, "mpm") || strstr($_action, "manage")
      || strstr($_action, "sales") || strstr($_action, "qquoteadv")) {
        self::log("Read cache disabled (Magento action)");
        return false;
      }
      
      if(Mage::helper("deheerhoreca_fpc/data")->is_request_anonymous() === false) {
        if($non_anonymous_okay === false) {
          self::log("Read cache disabled (Not anonymous)");
          return false;
        }
      }
      
      self::log("Read cache enabled");
      return true;
    }
    
    public function is_write_cache_enabled($non_anonymous_okay = false) {
      
      if(DHH_FPC_ENABLED === false
        || $_SERVER["REQUEST_METHOD"] !== "GET"
        || isset($_GET["nofpc"]) === true
        || in_array(Mage::app()->getFrontController()->getAction()->getFullActionName(), 
             ["cms_index_noRoute"])
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "checkout")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "customer")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "api")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "mpm")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "manage")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "sales")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "qquoteadv")        
      ) {
        self::log("Write cache disabled (request excluded from caching)");
        return false;
      }
      
      if($non_anonymous_okay === false && Mage::helper("deheerhoreca_fpc/data")->is_request_anonymous() === false) {
        self::log("Write cache disabled (request not anonymous)");
        return false;
      }
      
      self::log("Write cache enabled");      
      return true;
    }
    
    public function get_cache_key($cache_key_prefix = null) {
      $cache_key_url = Mage::helper("deheerhoreca_fpc/data")->get_cache_url();
      if(empty($cache_key_prefix) === true) {
        $cache_key_prefix = Mage::helper("deheerhoreca_fpc/data")->get_cache_prefix();
      }
      $cache_key_url_hash = substr(base_convert(md5($cache_key_url), 16, 32), 0, 12);
      $_cacheKey = "FPC_{$cache_key_prefix}_".base64_encode($cache_key_url_hash);
      if(DHH_FPC_DEBUG === true) {
        Mage::log("Cache URL: {$cache_key_url}", null, "fpc.log", true);
        Mage::log("Cache URL hash: {$cache_key_url_hash}, Cache Key Prefix: {$cache_key_prefix}, Cache Key: zc:k:e6b_{$_cacheKey}", null, "fpc.log", true);
      }
      
      return $_cacheKey;
    }
    
    public function get_cached_html($key, $skip_formkey = false, $replace_blocks = true) {
      $html = Mage::app()->getCache()->load($key);
      
      if(empty($html) === true) {
        self::log("Cache MISS: {$key}");
        return null;
      }
      
      /* Hole punching */
      
      // Formkey (CSRF protection)
      
      if($skip_formkey === false) {
        Varien_Profiler::start("DHH::FPC::Holepunch::formkey");
        $search = "<!-- fpc form_key_placeholder -->";
        $formKey = Mage::getSingleton("core/session")->getFormKey();
        if(empty($formKey) === false) {
          $html = str_replace($search, $formKey, $html, $count);
          self::log("Replaced {$search} {$count} times");
        }
        Varien_Profiler::stop("DHH::FPC::Holepunch::formkey");
      }
      
      if($replace_blocks === true) {
        
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
        $minicart_html = self::replace_between($minicart_html, "<!-- header_sidebar_start -->", "<!-- header_sidebar_end -->", $sidebar_html);
        $search = "<!-- header_minicart_here -->";
        $html = str_replace($search, $minicart_html, $html, $count);
        self::log("Replaced {$search} {$count} times");
        Varien_Profiler::stop("DHH::FPC::Holepunch::minicart");
        
        // core_messages
        
        Varien_Profiler::start("DHH::FPC::Holepunch::messages_html");
        $messages_html .= Mage::app()
          ->getLayout()
          ->createBlock("core/messages")
          ->setTemplate("page/html/notices.phtml")->toHtml();
        $messages_html .= Mage::app()->getLayout()->getMessagesBlock()->toHtml();
        $search = "<!-- core_messages_here -->";
        $html = str_replace($search, $messages_html, $html, $count);
        self::log("Replaced {$search} {$count} times");
        Varien_Profiler::stop("DHH::FPC::Holepunch::messages_html");
        
        // miniquote
        
        Varien_Profiler::start("DHH::FPC::Holepunch::miniquote_html");
        $miniquote_html = Mage::app()
          ->getLayout()
          ->createBlock("qquoteadv/checkout_miniquote_miniquote")
          ->setTemplate("qquoteadv/checkout/quote/miniquotehead.phtml")
          ->toHtml();
        $search = "<!-- header_miniquote_here -->";
        $html = str_replace($search, $miniquote_html, $html, $count);
        self::log("Replaced {$search} {$count} times");
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
      }
      
      self::log("Cache HIT: {$key}");
      
      return $html;
    }
    
    public function save_cached_html($key, $html, $replace_formkey = true, $replace_blocks = true) {

      // Handle form_key (CSRF protection)
      if($replace_formkey === true) {
        $formKey = Mage::getSingleton("core/session")->getFormKey();
        if($formKey) {
          $formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";
          $html = str_replace($formKey, $formKeyPlaceholder, $html);
        }
      }
      
      if($replace_blocks === true) {
        $html = self::replace_between($html, "<!-- header_minicart_start -->", "<!-- header_minicart_end -->", "<!-- header_minicart_here -->");
        $html = self::replace_between($html, "<!-- header_miniquote_start -->", "<!-- header_miniquote_end -->", "<!-- header_miniquote_here -->");
        $html = self::replace_between($html, "<!-- header_sidebar_start -->", "<!-- header_sidebar_end -->", "<!-- header_sidebar_here -->");
        $html = self::replace_between($html, "<!-- core_messages_start -->", "<!-- core_messages_end -->", "<!-- core_messages_here -->");
        // $html = self::replace_between($html, "<!-- breadcrumbs_start -->", "<!-- breadcrumbs_end -->", "<!-- breadcrumbs_here -->");
      }
      
      if(Mage::app()->getCache()->save($html, $key, ["quickndirtyfpc"], 7 * 86400)) {
        self::log("Cache: SAVED {$key}, ".strlen($html)." chars");
        return true;
      }
      
      return false;
    }
    
    public function replace_between($str, $needle_start, $needle_end, $replacement) {
      
      if(strstr($str, $needle_start) === false) return $str;
      if(strstr($str, $needle_end) === false) return $str;
      
      $pos = strpos($str, $needle_start);
      $start = $pos === false ? 0 : $pos;

      $pos = strpos($str, $needle_end, $start);
      $end = $pos === false ? strlen($str) : $pos + strlen($needle_end);
      
      if(DHH_FPC_DEBUG === true) {
        Mage::log(__METHOD__.": ".htmlentities($needle_start).":: Start = {$start}, End = {$end}", null, "fpc.log", true);
      }

      return substr_replace($str, $replacement, $start, $end - $start);
    }
    
    static function log($msg): void {
      if(DHH_FPC_DEBUG === true) {
         Mage::log($msg, null, "fpc.log", true);
      }
    }
}

if(function_exists("printr") === false) {
  function printr($expr, $return = false) {
    $ret = null;
    if(is_array($expr) && !sizeof($expr)) {
      return;
    }
    if(php_sapi_name() !== "cli") {
      $ret .= "<pre>";
    }
    $ret .= print_r($expr, true);
    if(php_sapi_name() !== "cli") {
      $ret .= "</pre>";
    } else {
      $ret .= PHP_EOL;
    }
    if($return) {
      return $return;
    }
    echo $ret;
  }
}
