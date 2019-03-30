<?php

/**
 * Class Shopworks_Billink_Helper_Logger
 */
class Shopworks_Billink_Helper_Logger
{
    const LOG_NAME = 'billink.log';

    /**
     * @return string
     */
    public function getLogName()
    {
        return self::LOG_NAME;
    }

    /**
     * @param string $msg
     * @param int $logLevel
     */
    public function log($msg, $logLevel)
    {
        Mage::log($msg, $logLevel, self::LOG_NAME);
    }
}