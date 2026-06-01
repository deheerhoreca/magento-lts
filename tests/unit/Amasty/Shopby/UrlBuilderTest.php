<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('Amasty_Shopby_Model_Source_Url_Mode', false)) {
    class Amasty_Shopby_Model_Source_Url_Mode
    {
        public const MODE_SHORT = 1;
    }
}

require_once __DIR__ . '/../../../../app/code/local/Amasty/Shopby/Model/Url/Builder.php';

final class UrlBuilderTest extends TestCase
{
    public function testGetUrlWithChangeStoreKeepsOnlyStoreAndAddsCategory(): void
    {
        Mage::$app = new class {
            public function getRequest()
            {
                return new class {
                    public function getParam($key)
                    {
                        return $key === 'id' ? 42 : null;
                    }
                };
            }
        };

        $builder = new class extends Amasty_Shopby_Model_Url_Builder {
            protected function updateEffectiveQuery() {}
            protected function getParamPart() { return '?x=1'; }
            protected function getBasePart($seoAttributePartExist = false) { return 'https://example.test/category'; }
            public function setQueryForTest(array $query): void { $this->query = $query; }
            public function getQueryForTest(): array { return $this->query; }
        };

        $builder->setQueryForTest(['___store' => 'nl', 'q' => 'abc', 'page' => 2]);
        $url = $builder->getUrl(true);

        self::assertSame('https://example.test/category?x=1', $url);
        self::assertSame(['___store' => 'nl', 'cat' => 42], $builder->getQueryForTest());
    }
}
