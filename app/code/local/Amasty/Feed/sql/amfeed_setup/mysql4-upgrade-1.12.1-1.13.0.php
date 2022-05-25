<?php
    /**
    * @author Amasty Team
    * @copyright Copyright (c) 2010-2012 Amasty (http://www.amasty.com)
    * @package Amasty_Feeds
    */
    $installer = $this;
    $installer->startSetup();
    
    $installer->run("
                alter table `{$this->getTable('amfeed/profile')}` 
                ADD COLUMN `csv_header_static` TEXT NOT NULL AFTER csv_header;
    ");
    $installer->endSetup();
