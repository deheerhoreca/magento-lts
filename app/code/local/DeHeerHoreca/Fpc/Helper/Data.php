<?php

/**
 * Helper
 *
 * @author Fabrizio Branca
 * @since  2013-05-23
 */
class DeHeerHoreca_Fpc_Helper_Data extends Mage_Core_Helper_Abstract
{

    const LOG_FILE = 'dhh_fpc.log';

    /**
     * Clear the class path cache
     *
     * @return bool
     */
    public function clearCache() {
      $mode = Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG;
      $tag = "quickndirtyfpc";

      //Beaufiul, isn't it?
      echo "<pre>";
      echo "------- Clearing Redis FPC cache --------".PHP_EOL.PHP_EOL;
      echo shell_exec("redis-cli --scan --pattern *QUICKNDIRTYFPC* | xargs redis-cli del");
      echo PHP_EOL."----- Done clearing Redis FPC cache -----".PHP_EOL;
      echo "</pre>";

      $url = Mage::helper("adminhtml")->getUrl("adminhtml/cache/index");
      echo "<span><a href='{$url}'>Back</a></span><br><br>";

      return true;

    }

    /**
     * Revalidate all currently cached entries
     */
    public function revalidateCache() {
      return;
      
      $start = microtime(true);
      $cache = Varien_Autoload::getCache();
      Varien_Autoload::setCache(array());
      foreach ($cache as $className => $path) {
          Varien_Autoload::getFullPath($className);
      }
      $duration = microtime(true) - $start;
      Mage::log('[DeHeerHoreca_Fpc] Revalidated ' . count($cache) . ' classes (duration: ' . round($duration, 2) . ' sec)', 6 /* Zend_Log::INFO */, self::LOG_FILE);
    }

    /**
     * Check url
     *
     * @return bool
     */
    public function checkUrl() {
      $k = base64_decode(Mage::app()->getRequest()->getParam('k'));
      $v = base64_decode(Mage::app()->getRequest()->getParam('v'));
      $ek = Mage::helper('core')->decrypt($v);
      return $k && $v && ($ek == $k);
    }

    /**
     * Check url
     *
     * @return bool
     */
    public function getUrl() {
      $k = Mage::helper('core')->getRandomString(16);
      return Mage::getUrl(
        'deheerhorecafpc/index/clear',
        array(
          'k'      => base64_encode($k),
          'v'      => base64_encode(Mage::helper('core')->encrypt($k)),
          '_store' => Mage::app()->getDefaultStoreView()->getCode()
        )
      );
    }
    
    /*
     * Cleans the cached URL by removing URL parameters that do not affect the payload
     * If not done, we would cache index.html?sqr=x separately from index.html
     */
    public function get_cache_url() {
      $url = html_entity_decode(Mage::helper('core/url')->getCurrentUrl());
      
      $ignored_url_query_keys = [
        "sqr", "profile", "___store", "refreshfpc", "__cf_chl_jschl_tk__",
      ];
      $url = Mage::helper("deheerhoreca_fpc/data")->strip_param_from_url($url, $ignored_url_query_keys);
      
      return $url;
    }

    # https://stackoverflow.com/questions/4937478/strip-off-url-parameter-with-php
    public function strip_param_from_url($url, $params) {
      $base_url = strtok($url, '?');              // Get the base url
      $parsed_url = parse_url($url);              // Parse it
      
      if(empty($parsed_url['query']) === true) {
        return $url;
      }
      
      $query = $parsed_url['query'];              // Get the query string
      parse_str($query, $parameters );            // Convert Parameters into array
      
      foreach($params as $param) {
        if(isset($parameters[$param])) {
          unset($parameters[$param]);             // Delete the one you want
        }
      }
      
      $new_query = http_build_query($parameters); // Rebuilt query string
      $url = $base_url.'?'.$new_query;
      
      return rtrim($url, "?");                    // Trim possible trailing ?
    }
    
    // Logic also exists in DeHeerHoreca_Fpc_Model_Observer
    public function get_cache_prefix() {
      $cache_key_prefix = Mage::app()->getFrontController()->getAction()->getFullActionName();
      
      if($cache_key_prefix === "catalog_product_view") {
        $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam('id');
        $cache_key_prefix .= "-".$id;
      } elseif($cache_key_prefix === "catalog_category_view") {
        $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam('id');
        $cache_key_prefix .= "-".$id;
      }
      
      return $cache_key_prefix;
    }
    
    public function is_request_anonymous() {
      if(
         Mage::helper('checkout/cart')->getItemsCount() > 0
         || Mage::getSingleton('customer/session')->isLoggedIn()
      ) {
        if(DHH_FPC_DEBUG === true) {
          print_r("<br /><br /><br /><br /><br /><br />Request is NOT anonymous");
        }
        return false;
      }
      
      if(DHH_FPC_DEBUG === true) {
        print_r("<br /><br /><br /><br /><br /><br />Request IS anonymous");
      }
      
      return true;
    }
    
    public function is_read_cache_enabled($non_anonymous_okay = false) {
      
      $enabled = true;
      
      if(isset($_GET['nofpc'])) {
        $enabled = false;
      } elseif(isset($_GET['refreshfpc'])) {
        $enabled = false;
      } elseif(DHH_FPC_ENABLED === false) {
        $enabled = false;
      } elseif($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $enabled = false;
      } elseif(in_array(Mage::app()->getFrontController()->getAction()->getFullActionName(),
          ["cms_index_noRoute"])
        ) {
        $enabled = false;
      } elseif(
        strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "checkout")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "customer")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "api")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "mpm")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "manage")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "sales")
        || strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "qquoteadv")        
        ) {
        $enabled = false;
      }
      
      if(Mage::helper("deheerhoreca_fpc/data")->is_request_anonymous() === false) {
        if($non_anonymous_okay === false) {
          $enabled = false;
        }
      }
      
      if($enabled === true && DHH_FPC_DEBUG === true) {
        print_r("<br />Read cache enabled");
      } elseif($enabled === false && DHH_FPC_DEBUG === true) {
        print_r("<br />Read cache disabled");
      }
      
      return $enabled;
    }
    
    public function is_write_cache_enabled($non_anonymous_okay = false) {

      if(DHH_FPC_ENABLED === false
        || $_SERVER['REQUEST_METHOD'] !== 'GET'
        || isset($_GET['nofpc']) === true
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
        if(DHH_FPC_DEBUG === true) {
          print_r("<br />Write cache disabled");
        }
        return false;
      }
      
      if(Mage::helper("deheerhoreca_fpc/data")->is_request_anonymous() === false) {
        
        if(DHH_FPC_DEBUG === true) {
          print_r("<br />Write cache disabled");
        }
        
        if($non_anonymous_okay === false) {
          return false;
        }
      }
      
      if(DHH_FPC_DEBUG === true) {
        print_r("<br />Write cache enabled");
      }
      
      return true;
    }
    
    public function get_cache_key($cache_key_prefix = null) {
      $cache_key_url = Mage::helper("deheerhoreca_fpc/data")->get_cache_url();
      if(empty($cache_key_prefix) === true) {
        $cache_key_prefix = Mage::helper("deheerhoreca_fpc/data")->get_cache_prefix();
      }
      $_cacheKey = "QUICKNDIRTYFPC-{$cache_key_prefix}-".base64_encode($cache_key_url);
      if(DHH_FPC_DEBUG === true) {
        print_r("<br />Cache URL: {$cache_key_url}");
        print_r("<br />Cache Key Prefix: {$cache_key_prefix}");
        print_r("<br />Cache Key: {$_cacheKey}");
      }
      
      return $_cacheKey;
    }
    
    public function get_cached_html($key, $skip_formkey = false, $replace_blocks = true) {
      $html = Mage::app()->getCache()->load($key);
      
      if($skip_formkey === false) {
        $formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";

        if(empty($html) === false) {
          // Handle form_key (CSRF protection)
          $formKey = Mage::getSingleton('core/session')->getFormKey();
          if(empty($formKey) === false) {
            if(DHH_FPC_DEBUG === true) {
              print_r("<br />Form key: {$formKey}");
            }
            $html = str_replace($formKeyPlaceholder, $formKey, $html);
          }
        }
      }
      
      /* Hole punching */
      
      // the sidebar, is PART OF the minicart
      
      if($replace_blocks === true) {
        $minicart_html = Mage::app()
          ->getLayout()
          ->createBlock("checkout/cart_minicart")
          ->setTemplate("checkout/cart/minicart.phtml")
          ->toHtml();
        $sidebar_html = Mage::app()
          ->getLayout()
          ->createBlock("checkout/cart_sidebar")
          ->setTemplate("checkout/cart/minicart/items.phtml")->toHtml();
        
        $messages_html .= Mage::app()
          ->getLayout()
          ->createBlock("core/messages")
          ->setTemplate("page/html/notices.phtml")->toHtml();
        $messages_html .= Mage::app()->getLayout()->getMessagesBlock()->toHtml();
          
        $miniquote_html = Mage::app()
          ->getLayout()
          ->createBlock("qquoteadv/checkout_miniquote_miniquote")
          ->setTemplate("qquoteadv/checkout/quote/miniquotehead.phtml")
          ->toHtml();
        
        // Mage::getSingleton('core/session')->addNotice(Mage::helper('core')->__("Notice ".date("c")));
        // Mage::getSingleton('core/session')->addSuccess(Mage::helper('core')->__("Notice ".date("c")));
        
        // printr($messages_html);
        
        $minicart_html = self::replace_between($minicart_html, "<!-- header_sidebar_start -->", "<!-- header_sidebar_end -->", $sidebar_html);
        
        $html = str_replace("<!-- header_minicart_here -->", $minicart_html, $html);
        $html = str_replace("<!-- core_messages_here -->", $messages_html, $html);
        $html = str_replace("<!-- header_miniquote_here -->", $miniquote_html, $html);
      }
      
      if(empty($html)) {
        if(DHH_FPC_DEBUG === true) {
          print_r("<br />Cache MISS: {$key}");
        }
        return null;
      }
      
      if(DHH_FPC_DEBUG === true) {
        print_r("<br />Cache HIT: {$key}");
      }
      
      return $html;
    }
    
    public function save_cached_html($key, $html, $replace_formkey = true, $replace_blocks = true) {

      // Handle form_key (CSRF protection)
      if($replace_formkey === true) {
        $formKey = Mage::getSingleton('core/session')->getFormKey();
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
      }
      
      Mage::app()->getCache()->save($html, $key, ["quickndirtyfpc"], 86400);
      
      if(DHH_FPC_DEBUG === true) {
        print_r("<br />Cache: SAVED {$key}, ".strlen($html)." chars");
      }
      
      return true;
    }
    
    public function replace_between($str, $needle_start, $needle_end, $replacement) {
      
      if(strstr($str, $needle_start) === false) return $str;
      if(strstr($str, $needle_end) === false) return $str;
      
      $pos = strpos($str, $needle_start);
      $start = $pos === false ? 0 : $pos;

      $pos = strpos($str, $needle_end, $start);
      $end = $pos === false ? strlen($str) : $pos + strlen($needle_end);
      
      if(DHH_FPC_DEBUG === true) {
        echo "<br />".htmlentities($needle_start)."::Start = {$start}";
        echo "<br />".htmlentities($needle_start)."::End = {$end}";
      }

      return substr_replace($str, $replacement, $start, $end - $start);
    } 
}

if(function_exists('printr') === false) {
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
