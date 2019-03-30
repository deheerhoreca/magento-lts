<?php

class Shopworks_Billink_Model_TransactionCounter
{
    const TRANSACTION_COUNTER_URL = 'http://billink.shopworkswebservices.nl/insert.php';

    /**
     * @var Shopworks_Billink_Helper_Logger
     */
    private $_logger;

    /**
     * @var Shopworks_Billink_Helper_Billink
     */
    private $_helper;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_logger = Mage::helper('billink/Logger');
	 $this->_helper = Mage::helper('billink/Billink');
    }

    /**
     * @param string $websiteUrl
     * @param string $orderAmount
     */
    public function sendTransaction($websiteUrl, $orderAmount)
    {
        $billinkName = Mage::getStoreConfig('payment/billink/billink_name');
        $timestamp = gmdate("Y-m-d H:i:s");
        $billinkPluginVersion = (string) Mage::getConfig()->getNode()->modules->Shopworks_Billink->version;

        $transactions = array(
            'transactions'=> array(
                array(
                    'billinkName' => $billinkName,
                    'websiteUrl' => $websiteUrl,
                    'orderAmount' => (float)$orderAmount,
                    'timestamp' => $timestamp,
                    'billinkPluginVersion' => $billinkPluginVersion,
		            'isTest' => (boolean)$this->_helper->isInTestMode()
                )
            )
        );

        //Make request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::TRANSACTION_COUNTER_URL);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'transactions=' . json_encode($transactions));
	 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	 curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);

        if($result === false)
        {
            $this->_logger->log("Error sending transaction: " . curl_error($ch), Zend_Log::ERR);
        }
    }
}