<?php

class TM_Richsnippets_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
     * Path to store config if frontend output is enabled
     *
     * @var string
     */
    const XML_PATH_ENABLED            = 'richsnippets/general/enabled';

	public function snippetsEnabled( $store = null )
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ENABLED, $store);
    }
}
