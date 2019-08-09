<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$table = $this->getTable('amshopby/value');
if (!$this->getConnection()->tableColumnExists($table, 'mapped_position')) {
    $this->run("ALTER TABLE `{$table}` ADD `mapped_position` int(10) NOT NULL DEFAULT '0'");
}

$this->endSetup();
