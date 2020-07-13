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

class Anowave_Ec_Helper_Cookie_Consent extends Anowave_Package_Helper_Data
{
	/**
	 * Cookie consent event
	 * 
	 * @var string
	 */
	const COOKIE_CONSENT_GRANTED_EVENT = 'cookieConsentGranted';
	
	/**
	 * Cookie key 
	 * 
	 * @var string
	 */
	const COOKEY_KEY = 'cookieConsentGranted';
	
	/**
	 * Set cookie lifetime
	 *
	 * @var integer
	 */
	const COOKIE_LIFETIME = 2592000;
	
	/**
	 * Set cookie path
	 *
	 * @var string
	 */
	const COOKIE_PATH = '/';
	
	/**
	 * Set cookie
	 *
	 * @param array $data
	 */
	public function set()
	{
		Mage::getModel('core/cookie')->set(self::COOKEY_KEY,1,self::COOKIE_LIFETIME,self::COOKIE_PATH,$_SERVER['HTTP_HOST'],false,false);
		
		return $this;
	}
	
	/**
	 * Get cookie
	 * 
	 * @return int
	 */
	public function get()
	{
		return (int) Mage::getModel('core/cookie')->get(self::COOKEY_KEY);
	}
	
	/**
	 * Delete cookie
	 */
	public function delete()
	{
		Mage::getModel('core/cookie')->delete(self::COOKEY_KEY,self::COOKIE_PATH,$_SERVER['HTTP_HOST'],false,false);
	}

	/**
	 * Check if cookie consent is enabled 
	 * 
	 * @return boolean
	 */
	public function supportCookieDirective()
	{
		return 1 === (int) Mage::getStoreConfig('ec/cookie/enable');
	}
	
	/**
	 * Check if consent cookie is accepted/consent granted
	 * 
	 * @return boolean
	 */
	public function isCookieConsentAccepted()
	{
		return 1 === $this->get();
	}
	
	
	public function getCookieDirectiveBackgroundColor()
	{
		return Mage::getStoreConfig('ec/cookie/content_background_color');
	}
	
	public function getCookieDirectiveTextColor()
	{
		return Mage::getStoreConfig('ec/cookie/content_text_color');
	}
	
	public function getCookieDirectiveTextAcceptColor()
	{
		return Mage::getStoreConfig('ec/cookie/content_accept_color');
	}
}