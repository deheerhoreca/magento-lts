<?php

// Non-PSR-4 loader for our own library functions and classes -- Gets included via Composer autoloading on every request

declare(strict_types=1);

// Setup global aliases to prevent "use" statements all over -- Needs test because this file might get included multiple times by Composer
// Cannot be executed multiple times between our apps
// @todo DEPRECATED, move away from global aliases
if (!defined("DHH_CLASS_ALIASES_APPLIED")) {
  if(!class_exists("Carbon"))              class_alias(\Carbon\Carbon::class, "Carbon", true);
  if(!class_exists("Arr"))                 class_alias(\Illuminate\Support\Arr::class, "Arr", true);
  if(!class_exists("Collection"))          class_alias(\Illuminate\Support\Collection::class, "Collection", true);
  if(!class_exists("Number"))              class_alias(\Illuminate\Support\Number::class, "Number", true);
  if(!class_exists("Str"))                 class_alias(\Illuminate\Support\Str::class, "Str", true);
  define("DHH_CLASS_ALIASES_APPLIED", true);
}

require_once __DIR__."/functions.php";          // BEFORE config.php
require_once __DIR__."/config.php";
require_once __DIR__."/CacheBuster.php";
require_once __DIR__."/Catalog.php";
require_once __DIR__."/CsCache.php";
require_once __DIR__."/Helper.php";
require_once __DIR__."/Html.php";
require_once __DIR__."/Observability.php";
require_once __DIR__."/Utils.php";
