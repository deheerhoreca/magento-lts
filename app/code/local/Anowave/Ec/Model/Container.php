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

class Anowave_Ec_Model_Container extends Mage_Core_Model_Config_Data
{
	/**
	 * API 
	 * 
	 * @var Anowave_Ec_Model_Api
	 */
	private $api = null;
	
	/**
	 * Get comment text 
	 * 
	 * @param Mage_Core_Model_Config_Element $element
	 * @param unknown $currentValue
	 * @return string|unknown
	 */
	public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
	{
		$containers = array();
		
		foreach($this->getContainers() as $container)
		{
			$containers[] = "Container: <strong>$container->publicId</strong>,  Container ID: <strong>$container->containerId</strong>";
		}

		if (!$this->getApi()->getClient()->isAccessTokenExpired())
		{
			return nl2br(join(PHP_EOL, $containers));
		}
		else return Mage::app()->getLayout()->createBlock('ec/system_container')->setTemplate('ec/system/container.phtml')->toHtml();
	}
	
	/**
	 * Get containers 
	 * 
	 * @return array
	 */
	private function getContainers()
	{
		$account = Mage::getStoreConfig('ec/api/google_gtm_account_id', $this->getApi()->getStoreId());
		
		if ($account)
		{
			return $this->getApi()->getContainers($account);
		}
		 
		return array();
	}
	
	/**
	 * Get API 
	 * 
	 * @return Anowave_Ec_Model_Api
	 */
	private function getApi()
	{
		if (!$this->api)
		{
			$this->api = Mage::getModel('ec/api');
		}
		
		return $this->api;
	}
}