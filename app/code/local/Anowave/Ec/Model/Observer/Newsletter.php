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
class Anowave_Ec_Model_Observer_Newsletter extends Anowave_Ec_Model_Observer
{
	/**
	 * Newsletter submit listener
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function subscribe(Varien_Event_Observer $observer)
	{
		if (1 === (int) $observer->getDataObject()->getSubscriberStatus() && true == $observer->getDataObject()->getIsStatusChanged())
		{
			$data = Mage::helper('ec/json')->encode
			(
				array
				(
					'event' 			=>    'newsletterSubmit',
					'eventCategory' 	=> Mage::helper('ec')->__('Newsletter'),
					'eventAction' 		=> Mage::helper('ec')->__('Submit'),
					'eventLabel' 		=> Mage::helper('ec')->__('Subscribe'),
					'eventValue' 		=> 1
				)
			);
			
			Mage::getSingleton('core/session')->setNewsletterEvent($data);
		}
	}
}