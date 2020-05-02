#!/bin/bash

/opt/plesk/php/7.2/bin/php -r 'require "app/Mage.php"; Mage::app()->getCacheInstance()->flush();'
