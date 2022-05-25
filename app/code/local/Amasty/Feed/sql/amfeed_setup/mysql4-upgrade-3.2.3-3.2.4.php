<?php
    /**
    * @author Amasty Team
    * @copyright Copyright (c) 2010-2012 Amasty (http://www.amasty.com)
    * @package Amasty_Feeds
    */
    $installer = $this;
    $installer->startSetup();

    $installer->run("
        ALTER TABLE `{$this->getTable('amfeed/profile')}`
        ADD COLUMN `currency` VARCHAR(255) default null;
    ");