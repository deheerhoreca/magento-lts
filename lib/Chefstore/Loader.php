<?php

declare(strict_types=1);

use \Illuminate\Support\Arr;
use \Illuminate\Support\Collection;
use \Illuminate\Support\Number;
use \Illuminate\Support\Str;
use \Symfony\Contracts\Cache\ItemInterface;

// Setup global aliases to prevent "use" statements all over -- Needs test because this file might get included multiple times by Composer
// Cannot be executed multiple times between our apps
if (!defined("DHH_CLASS_ALIASES_APPLIED")) {
  if (!is_callable("Arr"))           class_alias(Arr::class, "Arr", true);
  if (!is_callable("Collection"))    class_alias(Collection::class, "Collection", true);
  if (!is_callable("Number"))        class_alias(Number::class, "Number", true);
  if (!is_callable("Str"))           class_alias(Str::class, "Str", true);
  if (!is_callable("ItemInterface")) class_alias(ItemInterface::class, "ItemInterface", true);
  define("DHH_CLASS_ALIASES_APPLIED", true);
}

require_once __DIR__."/config.php";
require_once __DIR__."/functions.php";
require_once __DIR__."/Cache.php";
require_once __DIR__."/ElasticApmHelper.php";
require_once __DIR__."/Helper.php";
require_once __DIR__."/Html.php";
require_once __DIR__."/Observability.php";
require_once __DIR__."/Functions.php";
require_once __DIR__."/Utils.php";
