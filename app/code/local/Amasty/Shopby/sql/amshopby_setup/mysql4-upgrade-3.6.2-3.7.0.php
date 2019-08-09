<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$select = $this->getConnection()->select()
    ->from(array('e' => $this->getTable('catalog/category')), array('entity_id', 'int.value', 'eav.attribute_id'))
    ->join(
        array('int' => $this->getTable('catalog_category_entity_int')),
        'e.entity_id = int.entity_id AND e.level = 1 AND int.value = 0',
        array()
    )
    ->join(
        array('eav' => $this->getTable('eav_attribute')),
        'int.attribute_id = eav.attribute_id AND eav.attribute_code = "is_anchor"',
        array()
    );

$rootCategories = $this->getConnection()->fetchAll($select);

foreach ($rootCategories as $category) {
    if ($category['value'] !== "1") {
        $this->run(
            "UPDATE `{$this->getTable('catalog_category_entity_int')}`
            SET `value` = 1
            WHERE `attribute_id` = {$category['attribute_id']} AND `entity_id` = {$category['entity_id']}"
        );
    }
}

$this->endSetup();
