<?php

// @see https://github.com/colinmollenhour/Cm_Cache_Backend_Redis

PHP_SAPI == 'cli' || die('<h1>:P</h1>');

ini_set('memory_limit','1024M');
set_time_limit(0);
error_reporting(E_ALL | E_STRICT);

require_once __DIR__.'/../app/Mage.php';
Mage::app()->getCache()->getBackend()->clean('old');

// uncomment this for Magento Enterprise Edition
// Enterprise_PageCache_Model_Cache::getCacheInstance()->getFrontend()->getBackend()->clean('old');
