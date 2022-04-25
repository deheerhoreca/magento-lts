#!/bin/bash

/opt/plesk/php/7.4/bin/php -r 'require "app/Mage.php"; Mage::app()->getCacheInstance()->flush();'
