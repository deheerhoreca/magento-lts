<?php

declare(strict_types=1);

/*
 * Usage:
 *  openmage shell/gc_redis_cache.php
 *
 * This script will clean up old Redis cache entries that are no longer used by Magento.
 * It is recommended to run this script after upgrading Magento or changing cache configuration.
 *
 * Note: This script will only remove entries that are not used by Magento anymore. It will not remove entries that are still in use.
 */

PHP_SAPI == "cli" or die("<h1>:P</h1>");
ini_set("memory_limit","1g");
set_time_limit(0);

require __DIR__."/../app/Mage.php";
Mage::app()->getCache()->getBackend()->clean("old");
