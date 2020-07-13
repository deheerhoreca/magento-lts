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

class Anowave_Ec_CookieController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Server Side Push
	 * 
	 * @todo: To be introduced in next releases
	 */
	public function indexAction()
	{
		/**
		 * Set consent cookie
		 */
		Mage::helper('ec/cookie_consent')->set();
		
		/**
		 * Set response
		 * 
		 * @var array $response
		 */
		$response = array
		(
			'cookie' => true	
		);
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody
		(
			json_encode($response)
		);
	}
	
	/**
	 * Get cookie content
	 */
	public function contentAction()
	{
		$cookie = (int) Mage::helper('ec/cookie_consent')->get();
		
		if (!$cookie)
		{
			$response = array
			(
				'cookie' 		=> false,
				'cookieContent' => $this->getLayout()->createBlock('ec/cookie')->toHtml()
			);
		}
		else
		{
			$response = array
			(
				'cookie' 		=> true,
				'cookieContent' => Anowave_Ec_Helper_Cookie_Consent::COOKIE_CONSENT_GRANTED_EVENT
			);
		}
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody
		(
			json_encode($response)
		);
	}
}