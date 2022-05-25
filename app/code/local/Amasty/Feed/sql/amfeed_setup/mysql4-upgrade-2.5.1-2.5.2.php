<?php
    /**
    * @author Amasty Team
    * @copyright Copyright (c) 2010-2012 Amasty (http://www.amasty.com)
    * @package Amasty_Feeds
    */
    $installer = $this;
    $installer->startSetup();
    
    $installer->run("
        UPDATE`{$this->getTable('amfeed/template')}`
        SET store_id = " . Mage::app()->getStore()->getId() . "
        ;
    ");