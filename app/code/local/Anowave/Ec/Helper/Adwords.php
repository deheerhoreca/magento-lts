<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2020 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

class Anowave_Ec_Helper_Adwords extends Anowave_Ec_Helper_Data
{
	/**
	 * Check if using GTAG implementation
	 *
	 * @return boolean
	 */
	public function useAdwordsConversionTrackingGtag()
	{
		return 1 === (int) Mage::getStoreConfig('ec/adwords/conversion_implementation');
	}
	
	/**
	 * Get gtag.js snippet 
	 * 
	 * @return mixed|string|NULL
	 */
	public function getAdwordsConversionTrackingGtagSiteTag()
	{
		return Mage::getStoreConfig('ec/adwords/gtag_global_site_tag');
	}
	
	/**
	 * Get gtag.js send_to paramerer 
	 * 
	 * @return mixed|string|NULL
	 */
	public function getAdwordsConversionTrackingGtagSendToParameter()
	{
		return Mage::getStoreConfig('ec/adwords/gtag_send_to');
	}
	
	/**
	 * Get gtag event parameters 
	 * 
	 * @param Mage_Sales_Model_Order $order
	 * @return JSON
	 */
	public function getAdwordsConversionTrackingGtagConvesionEvent(Mage_Sales_Model_Order $order)
	{
		return Mage::helper('ec/json')->encode
		(
			array
			(
				'send_to' 			=> $this->getAdwordsConversionTrackingGtagSendToParameter(),
				'value' 			=> $this->getRevenue($order),
				'currency' 			=> Mage::app()->getStore()->getCurrentCurrencyCode(),
				'transaction_id' 	=> $order->getIncrementId()
			)
		);
	}
}