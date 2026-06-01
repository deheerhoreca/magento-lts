<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute_Pure', false)) {
    class Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute_Pure
    {
        protected $_filter;
        protected $_items = [];
        protected $_requestValue = 'color';
        protected $_showLessMore = false;

        public function getItems() { return $this->_items; }
        public function setItems(array $items) { $this->_items = $items; }
        public function escapeHtml($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
        public function getSortBy() { return null; }
        public function getDisplayType() { return null; }
        public function getHideCounts() { return false; }
        public function getSeoNoindex() { return null; }
        public function getSeoRel() { return true; }
        public function getRequestValue() { return $this->_requestValue; }
        public function getSortFeaturedFirst() { return false; }
        public function getMaxOptions() { return 0; }
        public function setShowLessMore($value) { $this->_showLessMore = (bool) $value; }
    }
}

if (!class_exists('Amasty_Shopby_Model_Source_Attribute', false)) {
    class Amasty_Shopby_Model_Source_Attribute
    {
        public const DT_IMAGES_ONLY = 1;
        public const DT_DROPDOWN = 2;
    }
}

if (!class_exists('Amasty_Shopby_Model_Filter', false)) {
    class Amasty_Shopby_Model_Filter
    {
        public const SEO_NO_INDEX_MULTIPLE_MODE = 1;
        public const SORT_BY_NAME = 1;
        public const SORT_BY_QTY = 2;
    }
}

require_once __DIR__ . '/../../../../app/code/local/Amasty/Shopby/Block/Catalog/Layer/Filter/Attribute.php';

final class FilterAttributeRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        Mage::$baseUrl = 'https://example.test/';
        Mage::$helpers['amshopby'] = new class {
            public function getIsApplyButtonEnabled() { return false; }
        };
        Mage::$helpers['amshopby/attributes'] = new class {
            public function sortOptionsByName($a, $b) { return strcmp($a['label'] ?? '', $b['label'] ?? ''); }
            public function sortOptionsByCounts($a, $b) { return 0; }
        };
        Mage::$models['amshopby/url_builder'] = new class {
            public function reset() {}
            public function clearPagination() {}
        };
        Mage::$app = new class {
            public function getRequest() {
                return new class {
                    public function getParams() { return []; }
                    public function getParam($k) { return null; }
                };
            }
        };
    }

    public function testGetItemsAsArrayBuildsExpectedFields(): void
    {
        $item = new class {
            public function getOptionId() { return 10; }
            public function getUrl() { return 'https://example.test/color/red'; }
            public function getLabel() { return 'Red'; }
            public function getDescr() { return 'Desc'; }
            public function getCount() { return 3; }
            public function getImage() { return 'red.png'; }
            public function getImageHover() { return null; }
            public function getIsSelected() { return true; }
            public function getIsFeatured() { return false; }
            public function getUrlAttributeOptionConfigAsJson($builder) { return '{"x":1}'; }
        };

        $block = new class extends Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute {
            public function getDisplayType() { return null; }
            public function getHideCounts() { return false; }
            public function getSeoNoindex() { return null; }
            public function getSeoRel() { return true; }
            public function getRequestValue() { return 'color'; }
            public function getSortFeaturedFirst() { return false; }
            public function getMaxOptions() { return 0; }
            public function getSortBy() { return null; }
        };

        $block->setItems([$item]);
        $rows = $block->getItemsAsArray();

        self::assertCount(1, $rows);
        self::assertSame(10, $rows[0]['id']);
        self::assertSame('Red', $rows[0]['label']);
        self::assertStringContainsString('count', $rows[0]['count']);
        self::assertSame('https://example.test/amshopby/red.png', $rows[0]['image']);
        self::assertTrue($rows[0]['is_selected']);
        self::assertSame(' rel="nofollow" ', $rows[0]['rel']);
        self::assertSame('[]', $rows[0]['data-config']);
    }
}
