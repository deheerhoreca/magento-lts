<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Feed
 */


$installer = $this;
$installer->startSetup();

$installer->run("
        UPDATE `{$this->getTable('amfeed/template')}`
        SET
        xml_header = CONCAT(xml_header, ' <created_at>{{DATE}}</created_at>')
        where title = 'Google.com';
    ");
