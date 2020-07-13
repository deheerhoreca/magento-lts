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

class Anowave_Ec_Helper_Cookie extends Anowave_Package_Helper_Data
{
	/**
	 * Set cookie key 
	 * 
	 * @var string
	 */
	const COOKEY_KEY = 'privateData';
	
	/**
	 * Set cookie lifetime
	 * 
	 * @var integer
	 */
	const COOKIE_LIFETIME = 3600;
	
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
	public function set($data = array())
	{
		Mage::getModel('core/cookie')->set(self::COOKEY_KEY,Mage::helper('ec/json')->encode($data), self::COOKIE_LIFETIME, self::COOKIE_PATH, $_SERVER['HTTP_HOST'], false, false);
		
		return $this;
	}
	
	/**
	 * Delete cookie
	 */
	public function delete()
	{
		Mage::getModel('core/cookie')->delete(self::COOKEY_KEY,self::COOKIE_PATH, $_SERVER['HTTP_HOST'], false, false);
	}
}