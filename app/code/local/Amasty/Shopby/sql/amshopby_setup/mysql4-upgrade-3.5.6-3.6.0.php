<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();
$select = $this->getConnection()->select()
    ->from($this->getTable('core_config_data'), array('scope', 'scope_id', 'path', 'value'))
    ->where('`path` = "amshopby/general/submit_filters"');

$data = $this->getConnection()->fetchAll($select);
foreach ($data as $item) {
    $value = $item['value'] ?: 0;
    $this->run("INSERT IGNORE INTO `{$this->getTable('core_config_data')}` (scope, scope_id, path, value)
            VALUES({$item['scope']}, {$item['scope_id']}, 'amshopby/general/submit_filters_desktop', {$value})");
    $this->run("INSERT IGNORE INTO `{$this->getTable('core_config_data')}` (scope, scope_id, path, value)
            VALUES({$item['scope']}, {$item['scope_id']}, 'amshopby/general/submit_filters_mobile', {$value})");
}

$this->endSetup();
