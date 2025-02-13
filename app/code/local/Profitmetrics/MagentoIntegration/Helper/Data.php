<?php

class Profitmetrics_MagentoIntegration_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_MODULE_ENABLED = 'profitmetrics/settings/enabled';
    const XML_PATH_HEADLESS_MODE = 'profitmetrics/settings/headless_mode';
    const XML_PATH_BUY_PRICE_ATTRIBUE = 'profitmetrics/settings/buy_price_attribute';
    const XML_PATH_FEED_FILE_NAME = 'profitmetrics/settings/feed_filename';
    const XML_PATH_STORE_PUBLIC_ID = 'profitmetrics/settings/store_public_id';
    const XML_PATH_SECRET_CODE = 'profitmetrics/settings/secret_code';
    const XML_PATH_TRACKING_CODE_JS = 'profitmetrics/settings/tracking_code_js';
    const XML_PATH_WEIGHT_MULTIPLIER = 'profitmetrics/settings/weight_multiplier';
    const XML_PATH_GOOGLE_ADS_CONVERSION_ID = 'profitmetrics/settings/google_ads_conversion_id';
    const XML_PATH_BLOCK_SCRIPT_BEFORE_CONSENT = 'profitmetrics/settings/block_script_before_consent';
    const XML_PATH_ORDER_STATUSES_TO_SEND = 'profitmetrics/order_cron_settings/order_statuses';
    const XML_PATH_ORDER_SEND_DAYS = 'profitmetrics/advanced/order_send_days';
    const XML_PATH_TRACKING_DATA_LIFETIME = 'profitmetrics/advanced/tracking_data_lifetime';
    const XML_PATH_PROFITMETRICS_ENDPOINT_TYPE = 'profitmetrics/advanced/order_send_endpoint';
    const XML_PATH_MODULE_INSTALLATION_DATE = 'profitmetrics/advanced/installation_date';
    const PROFITMETRICS_VISITOR_ID_SESSION_KEY = 'profitmetrics_visitor_id';
    const PROFITMETRICS_UPDATE_TIMESTAMP_SESSION_KEY = 'profitmetrics_update_timestamp';
    const XML_PATH_OVERWRITE_COST_CURRENCY = 'profitmetrics/settings/overwrite_cost_currency';

    protected $oldestVisitorDate;
    /** @var string */
    protected $moduleInstallationDate;

    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_MODULE_ENABLED);
    }

    /**
     * @return bool
     */
    public function getIsHeadlessModeActive()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_HEADLESS_MODE);
    }

    /**
     * @return string
     */
    public function getBuyPriceAttribute()
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_BUY_PRICE_ATTRIBUE);
    }

    public function getFeedFileName($store = null)
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_FEED_FILE_NAME, $store);
    }

    /**
     * @param Mage_Core_Model_Store|null $store
     * @return string
     */
    public function getStorePublicId($store = null)
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_STORE_PUBLIC_ID, $store);
    }

    /**
     * @param string $code
     * @return bool
     */
    public function checkSecretCode($code)
    {
        $secretCode = $this->getSecretCode();

        return $secretCode && $code === $secretCode;
    }

    /**
     * @return string
     */
    public function getSecretCode()
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_SECRET_CODE);
    }

    /**
     * @return string[]
     */
    public function getOrderStatusesToSend()
    {
        $orderStatusesCommaSeparated = (string) Mage::getStoreConfig(self::XML_PATH_ORDER_STATUSES_TO_SEND);

        return $orderStatusesCommaSeparated ? explode(',', $orderStatusesCommaSeparated) : [];
    }

    /**
     * @return string
     */
    public function getTrackingCodeJs()
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_TRACKING_CODE_JS);
    }

    /**
     * @return bool
     */
    public function isOrderSuccessPage()
    {
        return Mage::app()->getFrontController()->getAction()->getFullActionName() === 'checkout_onepage_success';
    }

    /**
     * @return int
     */
    public function getWeightMultiplier()
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_WEIGHT_MULTIPLIER);
    }

    public function getOrderSendDays()
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_ORDER_SEND_DAYS);
    }

    public function getTrackingDataLifetimeDays()
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_TRACKING_DATA_LIFETIME);
    }

    /**
     * @return string
     */
    public function getOldestVisitorDate()
    {
        if (!isset($this->oldestVisitorDate)) {
            $this->oldestVisitorDate = '';
            /** @var Profitmetrics_MagentoIntegration_Model_Resource_Tracking_Collection $visitorCollection */
            $visitorCollection = Mage::getResourceModel('profitmetrics/tracking_collection');
            $visitorCollection->setOrder('created_at', 'ASC');
            $visitorCollection->setPageSize(1);
            $oldestVisitor = $visitorCollection->getFirstItem();

            if ($oldestVisitor->getCreatedAt()) {
                $this->oldestVisitorDate = $oldestVisitor->getCreatedAt();
            }
        }

        return $this->oldestVisitorDate;
    }

    /**
     * @return string
     */
    public function getProfitMetricsApiEndpointType()
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_PROFITMETRICS_ENDPOINT_TYPE);
    }

    /**
     * @return string
     */
    public function getGoogleAdsConversionId()
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_GOOGLE_ADS_CONVERSION_ID);
    }

    /**
     * @return string
     */
    public function getModuleInstallationDate()
    {
        if (!isset($this->moduleInstallationDate)) {
            $this->moduleInstallationDate = Mage::getStoreConfig(self::XML_PATH_MODULE_INSTALLATION_DATE);
            $saveDate = false;

            if (!$this->moduleInstallationDate) {
                $this->moduleInstallationDate = $this->getOldestVisitorDate();
                $saveDate = true;
            }

            if (!$this->moduleInstallationDate) {
                $this->moduleInstallationDate = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $saveDate = true;
            }

            if ($saveDate) {
                Mage::getModel('core/config')->saveConfig(self::XML_PATH_MODULE_INSTALLATION_DATE, $this->moduleInstallationDate);
            }
        }

        return $this->moduleInstallationDate;
    }

    public function getBlockScriptBeforeContent()
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_BLOCK_SCRIPT_BEFORE_CONSENT);
    }

    /**
     * @return string
     */
    public function getOverwriteCostCurrency($store = null)
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_OVERWRITE_COST_CURRENCY, $store);
    }
}
