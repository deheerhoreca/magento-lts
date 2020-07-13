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

class Anowave_Ec_Helper_Affiliation extends Anowave_Package_Helper_Data
{
	/**
	 * Affiliation dimension index (in Google Analytics)
	 * 
	 * @var integer
	 */
	const DEFAULT_DIMENSION_INDEX = 20; 
	
	/**
	 * Get affiliation 
	 * 
	 * @return string
	 */
	public function getAffiliation()
	{	
		$affiliate = Mage::getSingleton('core/session')->getAffiliate();
		
		if ($affiliate)
		{
			return $affiliate;
		}
		
		/**
		 * Check if affiliate parameter is set
		 */
		if (null !== $parameter = $this->getAffiliationParameter())
		{
			if (array_key_exists($parameter, $_GET))
			{
				/**
				 * Read affiliate
				 */
				$affiliate = $_GET[$parameter];
				
				/**
				 * Set affiliate in session/cookie
				 */
				Mage::getSingleton('core/session')->setAffiliate($affiliate);
				
				return $affiliate;
			}
		}

		return trim(Mage::app()->getStore()->getFrontendName());
	}
	
	/**
	 * Get affiliation index
	 * 
	 * @return number|string
	 */
	public function getAffiliationIndex()
	{
		$index = (int) Mage::getStoreConfig('ec/affiliate/dimension');
		
		if (!$index)
		{
			$index = self::DEFAULT_DIMENSION_INDEX;
		}
		
		return $index;
	}
	
	public function getAffiliationParameter()
	{
		if (Mage::getStoreConfigFlag('ec/affiliate/parameter'))
		{
			return (string) Mage::getStoreConfig('ec/affiliate/parameter');
		}
		
		return null;
	}
	
	/**
	 * Get affiliation array 
	 * 
	 * @return string[]
	 */
	public function getAffiliationArray()
	{
		return array
		(
			"dimension{$this->getAffiliationIndex()}" => $this->getAffiliation()
		);
	}
}