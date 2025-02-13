<?php

class Profitmetrics_MagentoIntegration_Model_Cron
{
    const PRODUCT_BATCH_SIZE = 2000;
    const FEED_DIRECTORY_PATH = 'profitmetrics';
    const CORE_FLAG_KEY_PROFITMETRICS_RUNNING = 'profitmetrics_running';
    const XML_PATH_CONFIGURABLE_PRICE_SORUCE = 'profitmetrics/settings/configurable_price_source';
    const XML_PATH_TAKE_BUY_PRICE_FROM_CONFIGURABLE = 'profitmetrics/settings/buy_price_from_configurable';

    protected $_configurableProductsDataBySimpleProductIds;

    protected $_configurableProductPriceUsed;

    public function exportProductData()
    {
        /** @var Profitmetrics_MagentoIntegration_Helper_Data $helper */
        $helper = Mage::helper('profitmetrics');

        if (!$helper->isEnabled()) {
            return;
        }

        $flag = Mage::getModel(
            'core/flag',
            array('flag_code' => self::CORE_FLAG_KEY_PROFITMETRICS_RUNNING)
        )->loadSelf();

        $flag->setFlagData(Mage::getModel('core/date')->timestamp())->save();

        $buyPriceAttribute = $helper->getBuyPriceAttribute();
        $originalStore = Mage::app()->getStore();

        /** @var Mage_Core_Model_Store $store */
        foreach (Mage::app()->getStores() as $store) {
            Mage::app()->setCurrentStore($store->getCode());
            $productFeed = new SimpleXMLElement("<rss pm-type='gs-1.0'></rss>");
            $channel = $productFeed->addChild('channel');
            $storeCurrency = $store->getCurrentCurrency()->getCode();
            $defaultCurrency = Mage::app()->getDefaultStoreView()->getBaseCurrency()->getCode();
            $overwritePriceBuyCurrency = '';
            if (Mage::helper('profitmetrics')->getOverwriteCostCurrency($store->getCode())) {
                $overwritePriceBuyCurrency = Mage::helper('profitmetrics')->getOverwriteCostCurrency($store->getCode());
            }

            /** @var Mage_Catalog_Model_Product $product */
            foreach ($this->getProductsByStore($store) as $product) {
                $priceBuyDefault = (float)$product->getData($buyPriceAttribute . '_default');
                $priceBuy = (float)$product->getData($buyPriceAttribute);
                $priceBuyCurrency = $defaultCurrency;

                if ($priceBuy !== $priceBuyDefault) {
                    $priceBuyCurrency = $storeCurrency;
                }
                if ($overwritePriceBuyCurrency) {
                    $priceBuyCurrency = $overwritePriceBuyCurrency;
                }

                $productItem = $channel->addChild('item');
                $productItem->addChild('g:id', $product->getId(), 'g');
                $productItem->addChild('title', $helper->escapeHtml($product->getName()));
                $productItem->addChild('link', $product->getProductUrl());
                $productItem->addChild('g:price', $this->getProductPrice($product, $store), 'g');
                $productItem->addChild('pm:price_currency', $storeCurrency, 'pm');
                $productItem->addChild('pm:price_buy', $this->getBuyPrice($product, $store, $buyPriceAttribute), 'pm');
                $productItem->addChild('pm:price_buy_currency', $priceBuyCurrency, 'pm');
                $productItem->addChild('pm:num_stock', (int)$product->getQty(), 'pm');
                $productItem->addChild('pm:sku', $helper->escapeHtml((string)$product->getSku()), 'pm');

                if ($productImageUrl = $this->getProductImageUrl($product, $store)) {
                    $productItem->addChild('g:image_link', $productImageUrl, 'g');
                }
            }

            $feedFileName = $helper->getFeedFileName();
            $storeCode = $store->getCode();
            $feedFileName = str_replace('{{store}}', $storeCode, $feedFileName);

            $outdatedDirectoryToExport = Mage::getBaseDir('media') . DS . self::FEED_DIRECTORY_PATH . DS;
            if (file_exists($outdatedDirectoryToExport)) {
                Varien_Io_File::rmdirRecursive($outdatedDirectoryToExport);
            }

            $directoryToExport = Mage::getBaseDir('var') . DS . self::FEED_DIRECTORY_PATH . DS;

            if (!file_exists($directoryToExport)) {
                if (!mkdir($directoryToExport) && !is_dir($directoryToExport)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $directoryToExport));
                }
            }

            $filename = $directoryToExport . $feedFileName;

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($productFeed->asXML());

            $flag->delete();

            file_put_contents($filename, (string)$dom->saveXML());
        }

        if ($originalStore) {
            Mage::app()->setCurrentStore($originalStore);
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store $store
     * @param string $buyPriceAttribute
     * @return float
     */
    private function getBuyPrice($product, $store, $buyPriceAttribute)
    {
        $buyPrice = (float)$product->getData($buyPriceAttribute);

        if (Mage::getStoreConfig(self::XML_PATH_TAKE_BUY_PRICE_FROM_CONFIGURABLE)) {
            if ($this->_configurableProductsDataBySimpleProductIds === null) {
                $this->loadConfigurableDataBySimpleProductId($store);
            }

            $buyPrice = isset($this->_configurableProductsDataBySimpleProductIds[$product->getId()]['buy_price'])
                ? $this->_configurableProductsDataBySimpleProductIds[$product->getId()]['buy_price']
                : $buyPrice;
        }

        return $buyPrice;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    public function getProductImageUrl(Mage_Catalog_Model_Product $product, Mage_Core_Model_Store $store)
    {
        try {
            if ($product->getImage() && 'no_selection' !== $product->getImage()) {
                return $url = $this->getProductImageUrlOrEmpty($product);
            }

            $this->loadConfigurableDataBySimpleProductId($store);

            return isset($this->_configurableProductsDataBySimpleProductIds[$product->getId()]['image'])
                ? $this->_configurableProductsDataBySimpleProductIds[$product->getId()]['image']
                : $this->getProductImageUrlOrEmpty($product);

        } catch (Exception $exception) {
            Mage::logException($exception);
            return '';
        }
    }

    /**
     * @param Mage_Core_Model_Store $store
     */
    private function loadConfigurableDataBySimpleProductId($store)
    {
        if ($this->_configurableProductsDataBySimpleProductIds === null) {

            $simpleProductsByConfigurable = array();

            /** @var Mage_Catalog_Model_Product_Type_Configurable $configurableProductType */
            $configurableProductsCollection = Mage::getModel('catalog/resource_product_type_configurable_product_collection');
            $configurableProductsCollection->addStoreFilter($store);

            foreach ($this->getSimpleConfigurableConnections($configurableProductsCollection) as $simpleConfigurable) {
                $simpleId = $simpleConfigurable['entity_id'];
                $configurableId = $simpleConfigurable['parent_id'];
                if (!isset($simpleProductsByConfigurable[$configurableId])) {
                    $simpleProductsByConfigurable[$configurableId] = array();
                }

                $simpleProductsByConfigurable[$configurableId][] = $simpleId;
            }

            $attributesToLoad = ['image', 'image_url'];
            $buyPriceAttribute = Mage::helper('profitmetrics')->getBuyPriceAttribute();
            if ($buyPriceAttribute) {
                $attributesToLoad[] = $buyPriceAttribute;
            }

            $configurableProductsCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->setStore($store)
                ->addStoreFilter($store)
                ->addAttributeToSelect($attributesToLoad)
                ->addAttributeToFilter('type_id', 'configurable')
                ->addAttributeToFilter('image', array('notnull' => true));

            $this->_configurableProductsDataBySimpleProductIds = array();

            /** @var Mage_Catalog_Model_Product $configurableProduct */
            foreach ($configurableProductsCollection as $configurableProduct) {
                $productImageUrl = $this->getProductImageUrlOrEmpty($configurableProduct);

                if (array_key_exists($configurableProduct->getId(), $simpleProductsByConfigurable)) {
                    foreach ($simpleProductsByConfigurable[$configurableProduct->getId()] as $simpleProductId) {
                        $this->_configurableProductsDataBySimpleProductIds[$simpleProductId] = [
                            'image' => $productImageUrl,
                            'buy_price' => $configurableProduct->getData($buyPriceAttribute)
                        ];
                    }
                }
            }
        }
    }

    /**
     * @param Mage_Calalog_Model_Product $product
     * @return string
     */
    protected function getProductImageUrlOrEmpty(Mage_Catalog_Model_Product $product)
    {
        $productImageUrl = '';
        try {
            $productImageUrl = (string) $product->getImageUrl();
        } catch (Exception $exception) {
            Mage::logException($exception);
        }

        return $productImageUrl;
    }

    /**
     * @param $collection
     * @return array
     */
    protected function getSimpleConfigurableConnections($collection)
    {
        return $collection->getConnection()->fetchAll($collection->getSelect());
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store $store
     * @return float
     */
    protected function getProductPrice(Mage_Catalog_Model_Product $product, Mage_Core_Model_Store $store)
    {
        switch ($product->getTypeId()) {
            case Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE:
                if ($this->isConfigurableProductPriceUsed()) {
                    $price = $this->getCurrentOrSpecialPrice($product);
                    break;
                }

                /** @var Mage_Catalog_Model_Product_Type_Configurable $configurableProductType */
                $configurableProductType = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
                $simpleProductsCollection = $configurableProductType->getUsedProductCollection()
                    ->setStore($store)
                    ->addAttributeToSelect('*')
                    ->addFilterByRequiredOptions();
                $configurableChildrenMinimumPrice = INF;

                foreach ($simpleProductsCollection as $simpleProduct) {
                    if ($simpleProduct->getPrice() < $configurableChildrenMinimumPrice) {
                        $configurableChildrenMinimumPrice = $this->getCurrentOrSpecialPrice($simpleProduct);
                    }

                }
                $price = !is_infinite($configurableChildrenMinimumPrice) ? $configurableChildrenMinimumPrice : 0;
                break;
            case 'bundle':
                $minMaxPrices = Mage::getModel('bundle/product_price')->getTotalPrices(
                    $product,
                    null,
                    true,
                    false
                );
                $price = array_shift($minMaxPrices);
                break;
            case Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE:
                $groupedChildrenMinimumPrice = INF;
                /** @var Mage_Catalog_Model_Product_Type_Grouped $childGroupedProducts */
                $childGroupedProducts = Mage::getModel('catalog/product_type_grouped')
                    ->getAssociatedProducts($product);

                /** @var Mage_Catalog_Model_Product $groupedChildProduct */
                foreach ($childGroupedProducts as $groupedChildProduct) {
                    if ($groupedChildProduct->getPrice() < $groupedChildrenMinimumPrice) {
                        $groupedChildrenMinimumPrice = $this->getCurrentOrSpecialPrice($groupedChildProduct);
                    }
                }

                $price = !is_infinite($groupedChildrenMinimumPrice) ? $groupedChildrenMinimumPrice : 0;

                break;
            default:
                $price = $this->getCurrentOrSpecialPrice($product);
                break;
        }

        return sprintf('%5.2f', (float) $store->convertPrice($price));
    }

    /**
     * @return bool
     */
    public function isConfigurableProductPriceUsed()
    {
        if ($this->_configurableProductPriceUsed === null) {
            $this->_configurableProductPriceUsed = (int) Mage::getStoreConfig(self::XML_PATH_CONFIGURABLE_PRICE_SORUCE)
                === Profitmetrics_MagentoIntegration_Model_System_Config_Source_Configurable_Product_Price::CONFIGURABLE_PRICE_SOURCE_CONFIGURABLE;
        }

        return $this->_configurableProductPriceUsed;
    }

    /**
     * Returns special price in the case current special date intervals will be valid forever:
     * no "to" date and "from" date is in the past.
     * Otherwise returns the current product price
     * @param Mage_Catalog_Model_Product $product
     * @return float
     */
    protected function getCurrentOrSpecialPrice(Mage_Catalog_Model_Product $product)
    {
        if (!($specialPrice = $product->getSpecialPrice())) {
            return $product->getPrice();
        }

        $fromDate = $product->getSpecialFromDate();
        $toDate = $product->getSpecialToDate();
        $price = $product->getPrice();

        if (
            !$toDate
            && (!$fromDate || $fromDate < Mage::getModel('core/date')->date())
            && $specialPrice < $price
        ) {
            $price = $specialPrice;
        }

        return $price;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return Generator
     * @throws Mage_Core_Exception
     */
    protected function getProductsByStore($store)
    {
        $buyPriceAttribute = Mage::helper('profitmetrics')->getBuyPriceAttribute();
        /** @var Mage_Catalog_Model_Resource_Product $productResource */
        $productResource = Mage::getResourceModel('catalog/product');
        Mage::getResourceSingleton('catalog/product_flat')->setStoreId($store->getId());
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $priceBuyAttribute */
        $priceBuyAttribute =$productResource->getAttribute($buyPriceAttribute);
        /** @var Mage_Catalog_Model_Resource_Product_Collection $productsCollection */
        $productsCollection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($store)
            ->setStore($store)
            ->addFieldToFilter('type_id', array('neq' => 'configurable'))
            ->addAttributeToSelect(
                array(
                    'sku',
                    'name',
                    'price',
                    $buyPriceAttribute,
                    $buyPriceAttribute . '_default',
                    'special_price',
                    'special_price_from',
                    'special_price_to',
                    'image',
                    'small_image'
                )
            )
            ->joinField(
                'qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )
            ->joinField(
                $buyPriceAttribute . '_default',
                new Zend_Db_Expr($productResource->getTable('catalog/product') . '_' . $priceBuyAttribute->getBackendType()),
                'value',
                'entity_id=entity_id',
                new Zend_Db_Expr('{{table}}.attribute_id = ' . $priceBuyAttribute->getId().' AND {{table}}.store_id = 0'),
                'left'
            )
            ->setOrder('entity_id', 'DESC')
        ;

        $productsCount = $productsCollection->getSize();
        $pagesCount = ceil($productsCount / self::PRODUCT_BATCH_SIZE);

        for($page = 1; $page <= $pagesCount; $page++) {

            $productsCollection->clear();
            $productsCollection->setPage($page, self::PRODUCT_BATCH_SIZE);

            foreach ($productsCollection as $product) {
                yield $product;
            }
        }
    }

    public function sendOrders()
    {
        Mage::getModel('profitmetrics/order_service')->sendOrders();
    }

    public function visitorClean()
    {
        Mage::getModel('profitmetrics/cron_visitor_clean')->cleanOutdatedVisitors();
    }
}
