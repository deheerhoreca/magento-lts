<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/

$this->startSetup();

$table = $this->getTable('amshopby/value');
$this->run("ALTER TABLE `{$table}` MODIFY url_alias VARCHAR(512)");
$this->endSetup();
