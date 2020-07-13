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

class Anowave_Ec_Model_Observer_Offline extends Anowave_Ec_Model_Observer
{
	/**
	 * Send order to Google Analytics using Measurement Protocol
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function beforePlaceOrder(Varien_Event_Observer $observer)
	{
		/**
		 * Allow offline order tracking for orders made from admin only
		 */
		if (Mage::app()->getStore()->isAdmin())
		{
			if (Mage::helper('ec')->useMeasurementProtocol() && $observer->getOrder()->getId())
			{
				Mage::getModel('ec/api_measurement_protocol')->purchase($observer->getOrder());
			}
		}
	}
}