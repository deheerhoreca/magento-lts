<?php

if(substr($_SERVER['HTTP_HOST'], 0, 3) === "dev") {
  define("DHH_FPC_ENABLED", false);
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
  $url = html_entity_decode(Mage::helper('core/url')->getCurrentUrl());
  $url = strip_param_from_url($url, ["sqr", "profile", "___store", "refreshfpc", "pagespeed"]);
  
  return $url;
}

# https://stackoverflow.com/questions/4937478/strip-off-url-parameter-with-php
function strip_param_from_url($url, $params) {
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
