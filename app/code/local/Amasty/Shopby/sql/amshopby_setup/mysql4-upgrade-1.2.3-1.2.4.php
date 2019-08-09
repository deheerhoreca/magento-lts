<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|max_options:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'max_options')) {
    $this->run(
        "ALTER TABLE `{$tableName}` ADD `max_options` SMALLINT NOT NULL AFTER `attribute_id`"
    );
}

$this->endSetup();