<?php
    $installer = $this;
    $installer->startSetup();
    
    $installer->run("
        delete from `{$this->getTable('amfeed/template')}`
        where title = 'test';
        
        delete from `{$this->getTable('amfeed/template')}`
        where feed_id = 14;
    ");