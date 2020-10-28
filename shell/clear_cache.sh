#!/bin/bash

/opt/plesk/php/7.3/bin/php -r 'require "app/Mage.php"; Mage::app()->getCacheInstance()->flush();'
