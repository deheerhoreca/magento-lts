<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Model;

use Magmodules\GoogleShopping\Model\Collection\Products as ProductModel;
use Magmodules\GoogleShopping\Helper\Source as SourceHelper;
use Magmodules\GoogleShopping\Helper\Product as ProductHelper;
use Magmodules\GoogleShopping\Helper\General as GeneralHelper;
use Magmodules\GoogleShopping\Helper\Feed as FeedHelper;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Psr\Log\LoggerInterface;

class Feed
{

    const XPATH_FEED_RESULT = 'magmodules_googleshopping/feeds/results';
    const XPATH_GENERATE = 'magmodules_googleshopping/generate/enable';

    /**
     * @var ProductModel
     */
    private $productModel;

    /**
     * @var SourceHelper
     */
    private $sourceHelper;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var GeneralHelper
     */
    private $generalHelper;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * Generate constructor.
     *
     * @param ProductModel    $productModel
     * @param SourceHelper    $sourceHelper
     * @param ProductHelper   $productHelper
     * @param GeneralHelper   $generalHelper
     * @param FeedHelper      $feedHelper
     * @param Emulation       $appEmulation
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductModel $productModel,
        SourceHelper $sourceHelper,
        ProductHelper $productHelper,
        GeneralHelper $generalHelper,
        FeedHelper $feedHelper,
        Emulation $appEmulation,
        LoggerInterface $logger
    ) {
        $this->productModel = $productModel;
        $this->sourceHelper = $sourceHelper;
        $this->productHelper = $productHelper;
        $this->generalHelper = $generalHelper;
        $this->feedHelper = $feedHelper;
        $this->appEmulation = $appEmulation;
        $this->logger = $logger;
    }

    /**
     * Generate all feeds
     * @return array
     */
    public function generateAll()
    {
        $storeIds = $this->generalHelper->getEnabledArray(self::XPATH_GENERATE);
        foreach ($storeIds as $storeId) {
            $this->generateByStore($storeId, 'cron');
        }
    }

    /**
     * @param        $storeId
     * @param string $type
     * @param array  $productIds
     * @param int    $page
     *
     * @return array
     */
    public function generateByStore($storeId, $type = 'manual', $productIds = [], $page = 1)
    {
        $timeStart = microtime(true);
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $config = $this->sourceHelper->getConfig($storeId, $type);
        $this->feedHelper->createFeed($config);
        $products = $this->productModel->getCollection($config, $productIds);
        $pages = ($type == 'preview') ? $page : $products->getLastPageNumber();
        $processed = 0;

        do {
            $products->setCurPage($page);
            $products->load();

            if ($pages > 1 || !empty($config['filters']['advanced'])) {
                $parents = $this->productModel->getParents($products, $config);
                $processed += $this->getFeedData($products, $parents, $config, $type);
            } else {
                $processed += $this->getFeedData($products, $products, $config, $type);
            }

            if ($config['debug_memory']) {
                $this->feedHelper->addLog($page, $pages, $storeId);
            }

            $products->clear();
            $parents = null;
            $page++;
        } while ($page <= $pages);

        $pageSize = isset($config['filters']['page_size']) ? $config['filters']['page_size'] : '';
        $summary = $this->feedHelper->getFeedSummary($timeStart, $processed, $pageSize);
        $footer = $this->sourceHelper->getXmlFromArray($summary, 'config');
        $this->feedHelper->writeFooter($footer);
        $this->feedHelper->updateResult($storeId, $processed, $summary['time'], $summary['date'], $type, $pages);

        $this->appEmulation->stopEnvironmentEmulation();

        return [
            'status' => 'success',
            'qty'    => $processed,
            'path'   => $config['feed_locations']['full_path'],
            'url'    => $config['feed_locations']['url']
        ];
    }

    /**
     * @param $products
     * @param $parents
     * @param $config
     * @param $type
     *
     * @return int
     */
    public function getFeedData($products, $parents, $config, $type)
    {
        $qty = 0;
        foreach ($products as $product) {
            $parent = null;
            if ($config['filters']['relations']) {
                if ($parentId = $this->productHelper->getParentId($product->getEntityId())) {
                    $parent = $parents->getItemById($parentId);
                }
            }

            $dataRow = $this->productHelper->getDataRow($product, $parent, $config);
            if (empty($dataRow)) {
                continue;
            }

            if($feedRow = $this->sourceHelper->reformatData($dataRow, $product, $config)) {
                $this->feedHelper->writeRow($feedRow);
                $qty++;
            }
        }

        return $qty;
    }
}
