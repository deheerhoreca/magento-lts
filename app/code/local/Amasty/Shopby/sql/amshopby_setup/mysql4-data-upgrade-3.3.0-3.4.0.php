<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */

/** @var Mage_Eav_Model_Entity_Setup $this */

$this->startSetup();
$changes = array(
    'block/ajax' => 'general/ajax',
    'block/scroll_to_products' => 'general/scroll_to_products',
    'block/submit_filters' => 'general/submit_filters',
    'block/submit_position' => 'general/submit_position',
    'block/slider_use_ui' => 'general/slider_use_ui',
    'block/state_pos' => 'general/state_pos',
    'block/enable_overflow_scroll' => 'general/enable_overflow_scroll',

    'block/categories_pos' => 'category_filter/block_pos',
    'general/categories_order' => 'category_filter/position',
    'general/categories_collapsed' => 'category_filter/collapsed',
    'general/categories_type' => 'category_filter/display_mode',
    'general/multiselect_categories' => 'category_filter/multiselect',
    'advanced_categories/start_category' => 'category_filter/start_category',
    'advanced_categories/show_all_categories' => 'category_filter/show_all_categories',
    'advanced_categories/show_all_categories_depth' => 'category_filter/tree_depth',
    'general/categories_max_options' => 'category_filter/max_options',
    'general/include_cat' => 'category_filter/include_cat',
    'general/exclude_cat' => 'category_filter/exclude_cat',
    'advanced_categories/display_product_count' => 'category_filter/display_product_count',

    'block/price_pos' => 'price_filter/block_pos',
    'general/price_collapsed' => 'price_filter/collapsed',
    'general/price_type' => 'price_filter/display_mode',
    'general/use_custom_ranges' => 'price_filter/use_custom_ranges',
    'general/slider_type' => 'price_filter/slider_type',
    'general/slider_step' => 'price_filter/slider_step',
    'general/price_from_to' => 'price_filter/add_from_to',
    'general/price_exclude' => 'price_filter/exclude_cat',

    'general/stock_collapsed' => 'stock_filter/collapsed',
    'block/stock_filter_pos' => 'stock_filter/block_pos',
    'general/stock_filter_pos' => 'stock_filter/position',

    'general/rating_collapsed' => 'rating_filter/collapsed',
    'block/rating_filter_pos' => 'rating_filter/block_pos',
    'general/rating_filter_pos' => 'rating_filter/position',

    'general/new_collapsed' => 'new_filter/collapsed',
    'block/new_filter_pos' => 'new_filter/block_pos',
    'general/new_filter_pos' => 'new_filter/position',

    'general/on_sale_collapsed' => 'on_sale_filter/collapsed',
    'block/on_sale_filter_pos' => 'on_sale_filter/block_pos',
    'general/on_sale_filter_pos' => 'on_sale_filter/position',
);

$table = $this->getTable('core/config_data');
foreach ($changes as $old => $new) {
    $this->run("DELETE FROM `{$table}` WHERE `path` = 'amshopby/{$new}'");
    $this->run("UPDATE `{$table}` SET `path` = 'amshopby/{$new}' WHERE `path` = 'amshopby/{$old}'");
}

$disableLogicUpdatedIn = array('category_filter', 'stock_filter', 'rating_filter', 'new_filter', 'on_sale_filter');
foreach ($disableLogicUpdatedIn as $filter) {
    $posPath = 'amshopby/' . $filter . '/position';
    $enablePath = 'amshopby/' . $filter . '/enable';
    $select = $this->getConnection()->select()
        ->from($table, array('scope', 'scope_id'))
        ->where('`path` = "' . $posPath . '" AND `value` = -1');
    $ids = $this->getConnection()->fetchPairs($select);
    foreach ($ids as $scope => $scopeId) {
        $select = "
INSERT INTO `{$table}` (`scope`, `scope_id`, `path`, `value`) 
VALUES ('{$scope}', $scopeId, '{$enablePath}', 0)
ON DUPLICATE KEY UPDATE  `value` = 0
";

        $this->run($select);
    }
}

$this->endSetup();
