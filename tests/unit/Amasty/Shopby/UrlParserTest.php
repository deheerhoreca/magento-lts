<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../app/code/local/Amasty/Shopby/Model/Url/Parser.php';

final class UrlParserTest extends TestCase
{
    protected function setUp(): void
    {
        Mage::$storeConfig = [
            'amshopby/seo/key' => 'shopby',
            'amshopby/seo/option_char' => '-',
        ];

        Mage::$helpers['amshopby/url'] = new class {
            public function checkRemoveSuffix(string $params): string { return $params; }
            public function _convertAttributeToMagento(string $code): string { return $code; }
            public function isDecimal(string $code): bool { return $code === 'length'; }
            public function getAllFilterableOptionsAsHash($storeId = null): array
            {
                return [
                    'color' => ['red' => '11', 'blue' => '12'],
                ];
            }
        };

        Mage::$app = new class {
            public function getRequest()
            {
                return new class {
                    public function getQuery($key)
                    {
                        return null;
                    }
                };
            }
            public function getStore($code)
            {
                return new class {
                    public function getId() { return 1; }
                };
            }
        };
    }

    public function testParseParamsBuildsExpectedQuery(): void
    {
        $parser = new Amasty_Shopby_Model_Url_Parser();

        $result = $parser->parseParams('price-10-20-length-1.5,3.5-red');

        self::assertSame(
            ['price' => '10-20', 'length' => '1.5,3.5', 'color' => '11'],
            $result
        );
    }

    public function testParseParamsReturnsFalseOnUnmatchedToken(): void
    {
        $parser = new Amasty_Shopby_Model_Url_Parser();

        self::assertFalse($parser->parseParams('no-match-token'));
    }
}
