<?php

declare(strict_types = 1);

/* -------------------------------------------------- DEVELOPMENT -------------------------------------------------- */

/** @var  string[]  List of developer IPs */
const DHH_DEV_IPS = [
  "5.132.21.238",
  "185.127.111.251",
  "185.127.111.252",
  "87.210.61.235",
  "185.127.111.227",
  "81.59.51.217"
];

/* ---------------------------------------------------- DHH FPC ---------------------------------------------------- */

const DHH_FPC_NAV_KEY     = "DHH_CMS_TOPMENU";
const DHH_FPC_FOOTER_KEY  = "DHH_CMS_FOOTER";

// Cannot use _dhh_debug() due to the ?nofpc requirement
if(isset($_SERVER["REQUEST_METHOD"]) && ($_SERVER["REQUEST_METHOD"] === "GET" || $_SERVER["REQUEST_METHOD"] === "HEAD") && isDevIp()) {
  define("DHH_FPC_DEBUG", false);   // Default: false
} else {
  define("DHH_FPC_DEBUG", false);   // Default: false
}

if(isset($_SERVER["HTTP_HOST"]) && str_starts_with((string) $_SERVER["HTTP_HOST"], "dev.")) {
  define("DHH_FPC_ENABLED", false); // Default: false
} else {
  define("DHH_FPC_ENABLED", true);  // Default: true
}

/* ---------------------------------------------------- CATALOG ---------------------------------------------------- */

/** @var  array<string,string>  Mapping of OpenMage supplier codes to supplier code names */
const OM_SUPPLIER_CODE_NAME_MAP = [
  "AX"  => "apexa",
  "BA"  => "bartscher",
  "CL"  => "scancool",
  "CM"  => "culimat",
  "CS"  => "combisteel",
  "DH"  => "deheerhoreca",
  "DI"  => "diamond",
  "DJ"  => "dejongluchttechniek",
  "DM"  => "domest",
  "DT"  => "desinfectietoren.nl",
  "EM"  => "emga",
  "FG"  => "foster-gamko",
  "GI"  => "gastro-inox",
  "GN"  => "gastronoble",
  "HD"  => "hendi",
  "HM"  => "heatmaestro",
  "HZ"  => "hoshizaki",
  "IM"  => "itm",
  "IW"  => "icywave",
  "KS"  => "kamadosheriff",
  "LH"  => "liebherr",
  "MX"  => "maxima",
  "NG"  => "naomi-grills",
  "OS"  => "orionstar",
  "PB"  => "probbqshop",
  "PG"  => "penguin",
  "QC"  => "quecom",
  "SD"  => "showdowndisplays",
  "SG"  => "smeg",
  "SR"  => "saro",
  "SS"  => "sousvide supreme",
  "TC"  => "tefcold",
  "TR"  => "torre",
  "VE"  => "veba",
  "VT"  => "virtus",
  "YC"  => "youcup",
];

/** @var int[] These categories are not listed as subcategory tile in listviews */
const EXCLUDED_CATEGORY_IDS = [656, 864, 834, 828, 232];

/** @var int[] Children of these category IDs do not show tiled subcategories in listviews */
const PARENT_CATEGORY_IDS_WITHOUT_SUBCATEGORY_LISTVIEW_TILES = [
  1019,                                 // "Landingspagina's": 1019 is the root category for all landing pages.
                                        // We should not let people hang around in landing pages, so we don't show.
];
