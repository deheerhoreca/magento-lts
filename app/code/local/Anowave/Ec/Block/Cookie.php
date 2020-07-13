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

class Anowave_Ec_Block_Cookie extends Mage_Core_Block_Template
{
	public function _construct()
	{
		$this->setTemplate('ec/cookiecontent.phtml');
	}
	
	/**
	 * Get cookie content 
	 * 
	 * @return string
	 */
	public function getCookieDirectiveContent()
	{
		return sprintf($this->getConfig('ec/cookie/content'), Mage::app()->getStore()->getName());
	}
	
	/**
	 * Get config 
	 * 
	 * @param string $config
	 * @return mixed|string|NULL
	 */
	public function getConfig($config = '')
	{
		return Mage::getStoreConfig($config);
	}
}