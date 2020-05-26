<?php

if(substr($_SERVER['HTTP_HOST'], 0, 3) === "dev") {
  define("DHH_FPC_ENABLED", true);
} else {
  define("DHH_FPC_ENABLED", true);
}

$formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";
$error404tag        = "<!--FPC404TAG-->";

const FPC_TTL       = 86400;

/*
- 1column.phtml:
  - https://www.prokoeling.nl/koelkasten/alle-koelkasten/display-koelkasten/display-koelkast-zwart-wit-met-slot-klapdeuren-800ltr-exquisit.html
- 2columns-left.phtml:
  - https://www.prokoeling.nl/koelkasten/alle-koelkasten.html
*/

function get_cache_url() {
  $url = Mage::helper('core/url')->getCurrentUrl();
  $url = str_replace([
    "?refreshfpc",
    "?___store=default",
    "?profile=1",
  ], null, $url);
  
  return $url;
}
