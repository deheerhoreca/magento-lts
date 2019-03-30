<?php

ini_set('include_path',  PATH_SEPARATOR  . dirname(__FILE__) . '/../../../../' . PATH_SEPARATOR . dirname(__FILE__));
//Set custom memory limit
ini_set('memory_limit', '512M');
//Include Magento libraries
require_once 'app/Mage.php';
//Start the Magento application
Mage::app('default');
//Avoid issues "Headers already send"
session_start();