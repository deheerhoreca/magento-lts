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
      self::log("Revalidated ".count($cache)." classes");
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
    // Optionally takes a URL for debug/dev
    public function get_cache_url(string $url = "") {
      if($url === "") {
        $url = html_entity_decode(Mage::helper("core/url")->getCurrentUrl());
      }
      
      // List of query parameters that have no consequences for the rendered HTML
      $ignored_url_query_keys = [
        "sqr", "profile", "___store", "refreshfpc", "__cf_chl_jschl_tk__",
        "utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term",
        "gclid", "cfhtmlcache", "mc_cid", "mc_eid",
      ];
      $url = self::strip_param_from_url($url, $ignored_url_query_keys);
      
      // Remove things that can be ignored safely
      $url = rtrim($url, "&?/");
      
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
    public function get_cache_prefix() {
      $cache_key_prefix = Mage::app()->getFrontController()->getAction()->getFullActionName();
      
      if($cache_key_prefix === "catalog_product_view") {
        $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
        $supplier = "";
        // $supplier = Mage::getResourceModel("catalog/product")->getAttributeRawValue($id, "supplier_product_url", Mage::app()->getStore()->getStoreId());
        $cache_key_prefix .= "{$supplier}_{$id}";
      } elseif($cache_key_prefix === "catalog_category_view") {
        $id = (int) Mage::app()->getFrontController()->getAction()->getRequest()->getParam("id");
        $cache_key_prefix .= "_".$id;
      }
      
      return $cache_key_prefix;
    }
    
    // Determine of the current request is anonymous or logged in
    public function is_request_anonymous(): bool {
      
      // No cookie? No login
      if(isset($_SERVER["HTTP_COOKIE"]) && $_SERVER["HTTP_COOKIE"] === NULL) {
        self::log("Request is not anonymous");
        return false;
      }
      
      // More expensive checks
      if(Mage::helper("checkout/cart")->getItemsCount() > 0 || Mage::getSingleton("customer/session")->isLoggedIn()) {
        self::log("Request is not anonymous");
        return false;
      }
      
      return true;
    }
    
    /*
     * @param non_anonymous_okay  bool    Switch to check for anonymous requests (cart block, etc.)
     * @param html_block_mode     bool    For HTML block caching, the controller action is not taken into account
     */
    public function is_read_cache_enabled($non_anonymous_okay = false, $html_block_mode = false, $debug_name = ""): bool {
      
      Varien_Profiler::start("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      // $dhh_ips = ["5.132.21.238", "185.127.111.251", "185.127.111.252", "87.210.61.235", "185.127.111.227", "81.59.51.217"];
      // if(isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $dhh_ips, true) && isset($_GET['nofpc'])) {
        // echo "<pre>";
        // var_dump($_SERVER["HTTP_COOKIE"]);
        // var_dump($_COOKIE);
        // echo "</pre>";
      // }
      
      if(DHH_FPC_ENABLED === false) {
        self::log("Read cache disabled (DHH_FPC_ENABLED): {$debug_name}");
        return false;
      }
      
      // is_ajax is by amasty layered nav, and right now we cannot save that HTML (does not pass the page/ phtmls)
      if(isset($_GET["nofpc"]) || isset($_GET["refreshfpc"]) || isset($_GET["is_ajax"])) {
        self::log("Read cache disabled (URL parameter): {$debug_name}");
        return false;
      }
      
      if($_SERVER["REQUEST_METHOD"] !== "GET") {
        self::log("Read cache disabled (REQUEST_METHOD): {$debug_name}");
        return false;
      }
      
      if($html_block_mode !== true) {
        $_action = Mage::app()->getFrontController()->getAction()->getFullActionName();
        if($_action === "cms_index_noRoute" || strstr($_action, "checkout")
        || strstr($_action, "customer") || strstr($_action, "api")
        || strstr($_action, "mpm") || strstr($_action, "manage")
        || strstr($_action, "sales") || strstr($_action, "qquoteadv")) {
          self::log("Read cache disabled (Magento action): {$debug_name}");
          return false;
        }
      }
      
      if($non_anonymous_okay === false && Mage::helper("deheerhoreca_fpc/data")->is_request_anonymous() === false) {
        self::log("Read cache disabled (Anonymous not allowed and request is not anonymous): {$debug_name}");
        return false;
      }
      
      self::log("Read cache enabled: {$debug_name}");
      
      Varien_Profiler::stop("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      return true;
    }
    
    /*
     * @param non_anonymous_okay  bool    Switch to check for anonymous requests (cart block, etc.)
     * @param html_block_mode     bool    For HTML block caching, the controller action is not taken into account
     */
    public function is_write_cache_enabled($non_anonymous_okay = false, $html_block_mode = false, $debug_name = ""): bool {
      
      Varien_Profiler::start("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      if(DHH_FPC_ENABLED === false) {
        self::log("Write cache disabled (DHH_FPC_ENABLED): {$debug_name}");
        return false;
      }
      
      // is_ajax is by amasty layered nav, and right now we cannot save that HTML (does not pass the page/ phtmls)
      if(isset($_GET["nofpc"]) || isset($_GET["is_ajax"])) {
        self::log("Write cache disabled (URL parameter): {$debug_name}");
        return false;
      }
      
      if($_SERVER["REQUEST_METHOD"] !== "GET") {
        self::log("Write cache disabled (REQUEST_METHOD): {$debug_name}");
        return false;
      }
      
      if($html_block_mode !== true) {
        $_action = Mage::app()->getFrontController()->getAction()->getFullActionName();
        if($_action === "cms_index_noRoute" || strstr($_action, "checkout")
        || strstr($_action, "customer") || strstr($_action, "api")
        || strstr($_action, "mpm") || strstr($_action, "manage")
        || strstr($_action, "sales") || strstr($_action, "qquoteadv")) {
          self::log("Write cache disabled (Magento action): {$debug_name}");
          return false;
        }
      }
      
      if($non_anonymous_okay === false && Mage::helper("deheerhoreca_fpc/data")->is_request_anonymous() === false) {
        self::log("Read cache disabled (Anonymous not allowed and request is not anonymous): {$debug_name}");
        return false;
      }
      
      self::log("Write cache enabled: {$debug_name}");
      
      Varien_Profiler::stop("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      return true;
    }
    
    // Optionally takes a URL for debug/dev
    public function get_cache_key($cache_key_prefix = null, string $url = "") {
      Varien_Profiler::start("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      $cache_key_url = Mage::helper("deheerhoreca_fpc/data")->get_cache_url($url);
      if(empty($cache_key_prefix) === true) {
        $cache_key_prefix = Mage::helper("deheerhoreca_fpc/data")->get_cache_prefix();
      }
      $cache_key_url_hash = substr(base_convert(md5($cache_key_url), 16, 32), 0, 12);
      $_cacheKey = "FPC_{$cache_key_prefix}_".base64_encode($cache_key_url_hash);
      if(DHH_FPC_DEBUG === true) {
        self::log("Cache URL: {$cache_key_url}");
        self::log("Cache URL hash: {$cache_key_url_hash}, Cache Key Prefix: {$cache_key_prefix}, Cache Key: zc:k:e6b_{$_cacheKey}");
      }
      
      Varien_Profiler::stop("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      return $_cacheKey;
    }
    
    public function get_cached_html($key, $holepunch_formkey = true, $holepunch_blocks = true) {
      Varien_Profiler::start("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      $html = Mage::app()->getCache()->load($key);
      
      if(empty($html) === true) {
        self::log("Cache MISS: {$key}");
        if(!headers_sent()) header("Server-Timing: miss");
        return null;
      }
      
      if(DHH_FPC_DEBUG === true) {
        $size_raw_key = strlen($html);
      }
      
      /* Hole punching */
      
      // Formkey (CSRF protection)
      
      if($holepunch_formkey === true) {
        Varien_Profiler::start("DHH::FPC::Holepunch::formkey");
        $search = "<!-- fpc form_key_placeholder -->";
        $replacement = Mage::getSingleton("core/session")->getFormKey();
        if(empty($replacement) === false) {
          $html = str_replace($search, $replacement, $html, $count);
          self::log("Replaced {$search} {$count} times with ".strlen($replacement)." chars");
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
        $html = str_replace($search, $replacement, $html, $count);
        self::log("Replaced {$search} {$count} times with ".strlen($replacement)." chars");
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
        self::log("Replaced {$search} {$count} times with ".strlen($replacement)." chars");
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
        self::log("Replaced {$search} {$count} times with ".strlen($replacement)." chars");
        Varien_Profiler::stop("DHH::FPC::Holepunch::nav");
        
        // Footer
        Varien_Profiler::start("DHH::FPC::Holepunch::footer");
        $replacement = $_html = Mage::app()->getCache()->load(DHH_FPC_FOOTER_KEY);
        $search = "<!-- footer_here -->";
        $html = str_replace($search, $replacement, $html, $count);
        self::log("Replaced {$search} {$count} times with ".strlen($replacement)." chars");
        Varien_Profiler::stop("DHH::FPC::Holepunch::footer");
      }
      
      if(DHH_FPC_DEBUG === true) {
        $size = strlen($html);
        self::log("Cache HIT: {$key} (Net: {$size_raw_key} bytes, Gross: {$size} bytes)");
      }
      if(!headers_sent()) header("Server-Timing: hit");
      
      Varien_Profiler::stop("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      return $html;
    }
    
    public function save_cached_html($key, $html, $holepunch_formkey = true, $holepunch_blocks = true) {
      
      Varien_Profiler::start("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      // Minify -- Minifying after the holepunching breaks the btn-cart buttons in the listview
      $html = str_replace("<link rel=\"canonical\" href=\"https://www.chefstore.nl", "<link rel=\"canonical\" href=\"https://wwww.chefstore.nl", $html); // Prevent canonical URL shortening
      $html = str_replace("value=\"https://www.chefstore.nl/", "value=\"/", $html);
      $html = str_replace("src=\"https://www.chefstore.nl/", "src=\"/", $html);
      $html = str_replace("src='https://www.chefstore.nl/", "src='/", $html);
      $html = str_replace("href=\"https://www.chefstore.nl/", "href=\"/", $html);
      $html = str_replace("setLocation('https://www.chefstore.nl/", "setLocation('/", $html);
      $html = str_replace("href='https://www.chefstore.nl/", "href='/", $html);
      $html = str_replace(" type=\"text/javascript\"", "", $html);
      $html = str_replace("<link rel=\"canonical\" href=\"https://wwww.chefstore.nl", "<link rel=\"canonical\" href=\"https://www.chefstore.nl", $html);
      $html = str_replace(" />", ">", $html);
      
      // HTML minifier broken:
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
      if($holepunch_formkey === true) {
        $formKey = Mage::getSingleton("core/session")->getFormKey();
        if($formKey) {
          $formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";
          $html = str_replace($formKey, $formKeyPlaceholder, $html, $count);
          self::log("Replaced form_key {$count} times");
        }
      }
      
      if($holepunch_blocks === true) {
        $html = self::replace_between($html, "<!-- header_minicart_start -->", "<!-- header_minicart_end -->", "<!-- header_minicart_here -->");
        $html = self::replace_between($html, "<!-- header_miniquote_start -->", "<!-- header_miniquote_end -->", "<!-- header_miniquote_here -->");
        $html = self::replace_between($html, "<!-- header_sidebar_start -->", "<!-- header_sidebar_end -->", "<!-- header_sidebar_here -->");
        $html = self::replace_between($html, "<!-- core_messages_start -->", "<!-- core_messages_end -->", "<!-- core_messages_here -->");
        // $html = self::replace_between($html, "<!-- breadcrumbs_start -->", "<!-- breadcrumbs_end -->", "<!-- breadcrumbs_here -->");
        $html = self::replace_between($html, "<!-- nav_start -->", "<!-- nav_end -->", "<!-- nav_here -->");
        $html = self::replace_between($html, "<!-- footer_start -->", "<!-- footer_end -->", "<!-- footer_here -->");
      }
      
      // @todo Build JSON object
      // $data = [
        // "ts"        => time(),
        // "html"      => $html,
      // ];
      // $json = json_encode($data);
      
      // Store in cache
      if(Mage::app()->getCache()->save($html, $key, ["quickndirtyfpc"], 7 * 86400)) {
        self::log("Cache: SAVED {$key}, ".strlen($html)." chars");
        if(!headers_sent()) header("Server-Timing: saved");
        return true;
      }
      
      Varien_Profiler::stop("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      return false;
    }
    
    public function replace_between($str, $needle_start, $needle_end, $replacement) {
      
      Varien_Profiler::start("DHH::FPC::".__CLASS__."::".__METHOD__);
      
      $pos_start  = strpos($str, $needle_start);
      if($pos_start === false) {
        self::log(__METHOD__.": {$needle_start} not found!");
        return $str;
      }
      
      $start      = $pos_start === false ? 0 : $pos_start;
      $pos_end    = strpos($str, $needle_end, $start);
      
      if($pos_end === false) {
        self::log(__METHOD__.": {$needle_end} not found!");
        return $str;
      }
      
      $end        = $pos_end === false ? strlen($str) : $pos_end + strlen($needle_end);
      
      self::log(__METHOD__.": ".htmlentities($needle_start).":: Start = {$start}, End = {$end}");
      
      Varien_Profiler::stop("DHH::FPC::".__CLASS__."::".__METHOD__);
      
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
      $ret .= "<pre style='white-space: pre-wrap; word-wrap:break-word;'>";
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

// @see https://github.com/jenstornell/tiny-html-minifier/blob/master/src/TinyHtmlMinifier.php
// "Latest commit 5bea148 on Jun 25, 2019"
class TinyHtmlMinifier
{
    private $options;
    private $output;
    private $build;
    private $skip;
    private $skipName;
    private $head;
    private $elements;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->output = '';
        $this->build = [];
        $this->skip = 0;
        $this->skipName = '';
        $this->head = false;
        $this->elements = [
            'skip' => [
                'code',
                'pre',
                'script',
                'textarea',
            ],
            'inline' => [
                'a',
                'abbr',
                'acronym',
                'b',
                'bdo',
                'big',
                'br',
                'cite',
                'code',
                'dfn',
                'em',
                'i',
                'img',
                'kbd',
                'map',
                'object',
                'samp',
                'small',
                'span',
                'strong',
                'sub',
                'sup',
                'tt',
                'var',
                'q',
            ],
            'hard' => [
                '!doctype',
                'body',
                'html',
            ]
        ];
    }

    // Run minifier
    public function minify(string $html) : string
    {
        if (!isset($this->options['disable_comments']) ||
            !$this->options['disable_comments']) {
            $html = $this->removeComments($html);
        }

        $rest = $html;

        while (!empty($rest)) {
            $parts = explode('<', $rest, 2);
            $this->walk($parts[0]);
            $rest = (isset($parts[1])) ? $parts[1] : '';
        }

        return $this->output;
    }

    // Walk trough html
    private function walk(&$part)
    {
        $tag_parts = explode('>', $part);
        $tag_content = $tag_parts[0];

        if (!empty($tag_content)) {
            $name = $this->findName($tag_content);
            $element = $this->toElement($tag_content, $part, $name);
            $type = $this->toType($element);

            if ($name == 'head') {
                $this->head = $type === 'open';
            }

            $this->build[] = [
                'name' => $name,
                'content' => $element,
                'type' => $type
            ];

            $this->setSkip($name, $type);

            if (!empty($tag_content)) {
                $content = (isset($tag_parts[1])) ? $tag_parts[1] : '';
                if ($content !== '') {
                    $this->build[] = [
                        'content' => $this->compact($content, $name, $element),
                        'type' => 'content'
                    ];
                }
            }

            $this->buildHtml();
        }
    }

    // Remove comments
    private function removeComments($content = '')
    {
        return preg_replace('/(?=<!--)([\s\S]*?)-->/', '', $content);
    }

    // Check if string contains string
    private function contains($needle, $haystack)
    {
        return strpos($haystack, $needle) !== false;
    }

    // Return type of element
    private function toType($element)
    {
        return (substr($element, 1, 1) == '/') ? 'close' : 'open';
    }

    // Create element
    private function toElement($element, $noll, $name)
    {
        $element = $this->stripWhitespace($element);
        $element = $this->addChevrons($element, $noll);
        $element = $this->removeSelfSlash($element);
        $element = $this->removeMeta($element, $name);
        return $element;
    }

    // Remove unneeded element meta
    private function removeMeta($element, $name)
    {
        if ($name == 'style') {
            $element = str_replace(
                [
                    ' type="text/css"',
                    "' type='text/css'"
                ],
                ['', ''],
                $element
            );
        } elseif ($name == 'script') {
            $element = str_replace(
                [
                    ' type="text/javascript"',
                    " type='text/javascript'"
                ],
                ['', ''],
                $element
            );
        }
        return $element;
    }

    // Strip whitespace from element
    private function stripWhitespace($element)
    {
        if ($this->skip == 0) {
            $element = preg_replace('/\s+/', ' ', $element);
        }
        return trim($element);
    }

    // Add chevrons around element
    private function addChevrons($element, $noll)
    {
        if (empty($element)) {
            return $element;
        }
        $char = ($this->contains('>', $noll)) ? '>' : '';
        $element = '<' . $element . $char;
        return $element;
    }

    // Remove unneeded self slash
    private function removeSelfSlash($element)
    {
        if (substr($element, -3) == ' />') {
            $element = substr($element, 0, -3) . '>';
        }
        return $element;
    }

    // Compact content
    private function compact($content, $name, $element)
    {
        if ($this->skip != 0) {
            $name = $this->skipName;
        } else {
            $content = preg_replace('/\s+/', ' ', $content);
        }

        if (in_array($name, $this->elements['skip'])) {
            return $content;
        } elseif (in_array($name, $this->elements['hard']) ||
            $this->head) {
            return $this->minifyHard($content);
        } else {
            return $this->minifyKeepSpaces($content);
        }
    }

    // Build html
    private function buildHtml()
    {
        foreach ($this->build as $build) {

            if (!empty($this->options['collapse_whitespace'])) {

                if (strlen(trim($build['content'])) == 0)
                    continue;

                elseif ($build['type'] != 'content' && !in_array($build['name'], $this->elements['inline']))
                    trim($build['content']);

            }

            $this->output .= $build['content'];
        }

        $this->build = [];
    }

    // Find name by part
    private function findName($part)
    {
        $name_cut = explode(" ", $part, 2)[0];
        $name_cut = explode(">", $name_cut, 2)[0];
        $name_cut = explode("\n", $name_cut, 2)[0];
        $name_cut = preg_replace('/\s+/', '', $name_cut);
        $name_cut = strtolower(str_replace('/', '', $name_cut));
        return $name_cut;
    }

    // Set skip if elements are blocked from minification
    private function setSkip($name, $type)
    {
        foreach ($this->elements['skip'] as $element) {
            if ($element == $name && $this->skip == 0) {
                $this->skipName = $name;
            }
        }
        if (in_array($name, $this->elements['skip'])) {
            if ($type == 'open') {
                $this->skip++;
            }
            if ($type == 'close') {
                $this->skip--;
            }
        }
    }

    // Minify all, even spaces between elements
    private function minifyHard($element)
    {
        $element = preg_replace('!\s+!', ' ', $element);
        $element = trim($element);
        return trim($element);
    }

    // Strip but keep one space
    private function minifyKeepSpaces($element)
    {
        return preg_replace('!\s+!', ' ', $element);
    }
}
