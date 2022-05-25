<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2010-2012 Amasty (http://www.amasty.com)
* @package Amasty_Flags
*/
$installer = $this;
$installer->startSetup();
$installer->run("
            ALTER TABLE `{$this->getTable('amfeed/profile')}` 
            ADD COLUMN `max_images` int(10) unsigned not null default 5 ;
");
$installer->endSetup(); 