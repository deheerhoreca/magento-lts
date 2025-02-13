<?php

ini_set("display_errors", true);
error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
ini_set("memory_limit", "1G");

// const PLAYGROUND_IS_OPEN = false;
const PLAYGROUND_IS_OPEN = true;

if(!PLAYGROUND_IS_OPEN) {
  header("Location: /", true, 301);
  die();
}

require "app/bootstrap.php";
require "app/Mage.php";

// $developer_mode = false;
$developer_mode = true;

$store_code = "base";
// $store_code = "admin";

// $store_id = Mage_Core_Model_App::ADMIN_STORE_ID;
$store_id = 1;

$mage_options = ["_isInstalled" => true];

$_SERVER["MAGE_RUN_TYPE"] = "store";

Mage::register('custom_entry_point', true);
Mage::setIsDeveloperMode($developer_mode);
Mage::init();
Mage::app(code: $store_code, type: $_SERVER["MAGE_RUN_TYPE"], options: $mage_options);
// Mage::app()->setCurrentStore($store_id);

// $url_map = Guidance_Cachebuster_Model_Source_Urlmap()->toOptionArray();
// $parser = new Guidance_Cachebuster_Model_Parser();
// $parser = Guidance_Cachebuster_Helper_Data()

$helper = Mage::helper('guidance_cachebuster');
$parser = $helper->getParser();

// $response = $observer->getControllerAction()->getResponse();
$response = Mage::app()->getResponse();

// $orig_body = $response->getBody();
$orig_body = <<<EOT
<!DOCTYPE html>
<html lang="nl" id="top" class="no-js" itemscope itemtype="http://schema.org/WebPage" data-environment="production" data-transaction="GET amshopby_index_index" data-route="amshopby">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bij Chefstore.nl Horeca</title>
    <link rel="stylesheet" href="/skin/frontend/rwd/dhh/css/styles.css" media="all">
    <link rel="stylesheet" href="/skin/frontend/rwd/dhh/css/catalog_category_view.css" media="all">
    <link rel="stylesheet" href="/skin/frontend/rwd/dhh/css/amshopby.css" media="all">
    <link rel="stylesheet" href="/skin/frontend/rwd/dhh/fonts/font-awesome-4.7.0/css/font-awesome.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="/skin/assets/glightbox/dist/css/glightbox.min.css" media="print" onload="this.media='all'">
    <script src="/js/ext-jquery.min.js"></script>
    <script src="/js/ext-prototype.min.js"></script>
    <script src="/js/ext-scriptaculous-builder.min.js"></script>
    <script src="/js/ext-scriptaculous-effects.min.js"></script>
    <script src="/js/ext-jquery-ui.min.js"></script>
    <script src="/js/ext-jquery.ui.touch-punch.min.js"></script>
    <script>if(document.cookie.match(/^(.*;)?\s*__Host-dhh_past_first_page\s*=\s*[^;]+(.*)?$/)==null&&window.location.href.indexOf("cart")<0&&window.location.href.indexOf("qquoteadv")<0&&window.location.href.indexOf("afrekenen")<0&&window.location.href.indexOf("customer")<0){window.zESettings={webWidget:{chat:{connectOnPageLoad:false}}};}else{window.zESettings={webWidget:{chat:{connectOnPageLoad:true}}};}document.cookie="__Host-dhh_past_first_page=foo; path=/; Secure; Max-Age=2592000; SameSite=Strict";</script>
    <script id="ze-snippet" src="//static.zdassets.com/ekr/snippet.js?key=6a6d58da-d9c1-4d6a-98f5-5873329abca0"></script>
    <script src="/js/lib/jquery/noconflict.js"></script>
    <script src="/js/prototype/validation.js"></script>
    <script src="/js/varien/js.js"></script>
    <script src="/js/varien/form.js"></script>
    <script src="/js/mage/translate.js"></script>
    <script src="/js/mage/cookies.js"></script>
    <script src="/js/amasty/amshopby/amshopby.js"></script>
    <script src="/js/amasty/amshopby/jquery.noconflict.js"></script>
    <script src="/js/amasty/amshopby/amshopby-jquery.js"></script>
    <script src="/skin/frontend/rwd/default/js/lib/modernizr.custom.min.js"></script>
    <script src="/skin/frontend/rwd/dhh/js/lib/enquire.min.js"></script>
    <script src="/skin/frontend/rwd/dhh/js/app.js"></script>
    <script src="/skin/frontend/base/default/js/miniquote.js"></script>
    <script src="/skin/frontend/rwd/dhh/js/dhh.js"></script>
    <script src="/skin/frontend/rwd/default/js/configurableswatches/product-media.js"></script>
    <script src="/skin/frontend/rwd/default/js/configurableswatches/swatches-list.js"></script>
    <link rel="canonical" href="https://www.chefstore.nl/catalog/category/view/s/default-category/id/2/">
    <link rel="preload" href="/skin/frontend/rwd/dhh/fonts/opensans/memtYaGs126MiZpBA-UFUIcVXSCEkx2cmqvXlWqWuU6F.woff2" as="font" type="font/woff2">
    <link rel="preload" href="/skin/frontend/rwd/dhh/fonts/opensans/memvYaGs126MiZpBA-UvWbX2vVnXBbObj2OVTS-muw.woff2" as="font" type="font/woff2">
    <script>Mage.Cookies.path='/';Mage.Cookies.domain='.www.chefstore.nl';</script>
      <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-WRCTMHM');</script>
                          <!-- BEGIN GOOGLE ANALYTICS 4 CODE -->
          <script async src="https://www.googletagmanager.com/gtag/js?id=G-TPM4BPKLFY"></script>
          <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-TPM4BPKLFY');</script>
          <!-- END GOOGLE ANALYTICS 4 CODE -->
      <script src="/js/ext-translator.js" defer></script>
    <!-- Don't put anything in here, but it in the phtml --><meta name="description" content="Professionele horeca apparatuur, onderdelen, inrichting en disposables. Professioneel advies. snel, makkelijk en veilig. Gratis verzending vanaf 150 euro.">
    <meta name="keywords" content="horeca groothandel, horecagroothandel, horeca koelkast, horecakoelkast, opzetkoelvitrine, dubbeldeurs koelkast, backbar, displaykoelkasten, koelcel, vriescel, koelcel met motor, vriescel met motor, opzetkoelvitrine, opzet saladierre, saladette, koelwerkbank, 2 deurs koelwerkbank, 3 deurs koelwerkbank, RVS werktafel, RVS werktafel met laden, pizzawerktafel, pizzawerkbank, pizzaschep, pizzaoven">
    <meta name="robots" content="INDEX,FOLLOW">
    <meta name="theme-color" content="#fff">
    <meta name="application-name" content="Chefstore.nl">
    <meta name="apple-mobile-web-app-title" content="Chefstore.nl">
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
  </head>
  <body></body></html>
EOT;

$body = $parser->parseHtml($response->getBody());

// dump($helper->isEnabled());
dump($parser);
dump($orig_body);
dump($body);

// $response->setBody($body);
