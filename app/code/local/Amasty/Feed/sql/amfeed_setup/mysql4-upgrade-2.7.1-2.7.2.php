<?php
    /**
    * @author Amasty Team
    * @copyright Copyright (c) 2010-2012 Amasty (http://www.amasty.com)
    * @package Amasty_Feeds
    */
    $installer = $this;
    $installer->startSetup();
    
    $installer->run("
        ALTER TABLE `{$this->getTable('amfeed/field')}` 
        CHANGE column condition_serialized condition_serialized longtext;
    ");