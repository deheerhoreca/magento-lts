<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigDataCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magmodules\GoogleShopping\Logger\GoogleShoppingLogger;

class General extends AbstractHelper
{

    const MODULE_CODE = 'Magmodules_GoogleShopping';
    const XPATH_EXTENSION_ENABLED = 'magmodules_googleshopping/general/enable';
    const XPATH_CRON_ENABLED = 'magmodules_googleshopping/generate/cron';

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ProductMetadataInterface
     */
    private $metadata;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ConfigDataCollectionFactory
     */
    private $configDataCollectionFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var GoogleShoppingLogger
     */
    private $logger;

    /**
     * General constructor.
     *
     * @param Context                     $context
     * @param ObjectManagerInterface      $objectManager
     * @param StoreManagerInterface       $storeManager
     * @param ModuleListInterface         $moduleList
     * @param ProductMetadataInterface    $metadata
     * @param ConfigDataCollectionFactory $configDataCollectionFactory
     * @param GoogleShoppingLogger        $logger
     * @param Config                      $config
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $metadata,
        ConfigDataCollectionFactory $configDataCollectionFactory,
        GoogleShoppingLogger $logger,
        Config $config
    ) {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->metadata = $metadata;
        $this->configDataCollectionFactory = $configDataCollectionFactory;
        $this->config = $config;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * General check if Extension is enabled
     *
     * @param null $storeId
     * @return bool
     */
    public function getEnabled($storeId = null)
    {
        return (boolean) $this->getStoreValue(self::XPATH_EXTENSION_ENABLED, $storeId);
    }

    /**
     * @return bool
     */
    public function getCronEnabled()
    {
        return (boolean) $this->getStoreValue(self::XPATH_CRON_ENABLED);
    }

    /**
     * Get Configuration data
     *
     * @param $path
     * @param $scope
     * @param null $storeId
     * @return mixed
     */
    public function getStoreValue($path, $storeId = null, $scope = null)
    {
        if (empty($scope)) {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }

    /**
     * @param      $path
     * @param null $storeId
     * @param null $scope
     *
     * @return array|mixed
     */
    public function getStoreValueArray($path, $storeId = null, $scope = null)
    {
        $value = $this->getStoreValue($path, $storeId, $scope);

        $result = json_decode($value, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            if (is_array($result)) {
                return $result;
            }
            return [];
        }

        $value = @unserialize($value);
        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    /**
     * Set configuration data function
     * @param $value
     * @param $key
     * @param null $storeId
     */
    public function setConfigData($value, $key, $storeId = null)
    {
        if ($storeId) {
            $this->config->saveConfig($key, $value, 'stores', $storeId);
        } else {
            $this->config->saveConfig($key, $value, 'default', 0);
        }
    }

    /**
     * Get Uncached Value from core_config_data
     *
     * @param      $path
     * @param null $storeId
     *
     * @return mixed
     */
    public function getUncachedStoreValue($path, $storeId)
    {
        $collection = $this->configDataCollectionFactory->create();
        $collection->addFieldToSelect('value');
        $collection->addFieldToFilter('path', $path);
        $collection->addFieldToFilter('scope_id', $storeId);
        $collection->addFieldToFilter('scope', 'stores');

        return $collection->getFirstItem()->getValue();
    }

    /**
     * Returns current version of the extension
     * @return mixed
     */
    public function getExtensionVersion()
    {
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);

        return $moduleInfo['setup_version'];
    }

    /**
     * Returns current version of Magento
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->metadata->getVersion();
    }

    /**
     * @param $path
     * @return array
     */
    public function getEnabledArray($path)
    {
        $storeIds = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if ($this->getStoreValue($path)) {
                if ($this->getEnabled($store->getId())) {
                    $storeIds[] = $store->getId();
                }
            }
        }

        return $storeIds;
    }

    /**
     * @param $id
     * @param $data
     */
    public function addTolog($id, $data)
    {
        $debug = true;
        if ($debug) {
            $this->logger->add($id, $data);
        }
    }
}
