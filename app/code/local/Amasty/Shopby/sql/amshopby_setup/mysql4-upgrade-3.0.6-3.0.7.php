<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$table = $this->getTable('amshopby/value');
$this->run("ALTER TABLE `{$table}` MODIFY cms_block_id TEXT");
$this->run("ALTER TABLE `{$table}` MODIFY cms_block_bottom_id TEXT");
$this->endSetup();

