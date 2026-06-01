<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('Amasty_Shopby_Helper_Cached', false)) {
    class Amasty_Shopby_Helper_Cached
    {
        protected $saved;
        protected $loaded = [];

        protected function save($data, $key, $lifetime)
        {
            $this->saved = compact('data', 'key', 'lifetime');
        }

        protected function load($key)
        {
            return $this->loaded[$key] ?? [];
        }

        public function setLoadedData($key, $value)
        {
            $this->loaded[$key] = $value;
        }

        public function getSavedPayload()
        {
            return $this->saved;
        }
    }
}

require_once __DIR__ . '/../../../../app/code/local/Amasty/Shopby/Helper/Layer/Cache.php';

final class LayerCacheTest extends TestCase
{
    protected function setUp(): void
    {
        Mage::$helpers['core'] = new class {
            public function isModuleEnabled($module)
            {
                return false;
            }
        };

        Mage::$helpers['amshopby/attributes'] = new class {
            public function getRequestedBrandOption()
            {
                return null;
            }
        };

        Mage::$app = new class {
            public function getRequest()
            {
                return new class {
                    public function getParam($name, $default = null)
                    {
                        $map = ['ambrand_id' => 7, 'am_landing' => 'landing-a'];
                        return $map[$name] ?? $default;
                    }

                    public function getModuleName()
                    {
                        return 'catalog';
                    }
                };
            }
        };
    }

    public function testGetFilterItemsReturnsNullWhenCodeMissing(): void
    {
        $cache = new Amasty_Shopby_Helper_Layer_Cache();
        $cache->setStateKey('state-1');

        self::assertNull($cache->getFilterItems('color'));
    }

    public function testSetFilterItemsAndSaveUsesComposedKey(): void
    {
        $cache = new Amasty_Shopby_Helper_Layer_Cache();
        $cache->setStateKey('state-1');
        $cache->setFilterItems('color', [['id' => 1]]);
        $cache->saveLayerCache();

        $saved = $cache->getSavedPayload();
        self::assertSame('state-1landing-a_AMBRANDS_7_AMSHOPBY', $saved['key']);
        self::assertSame([['id' => 1]], $saved['data']['color']);
    }
}
