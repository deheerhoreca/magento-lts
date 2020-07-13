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

class Anowave_Ec_Helper_Cache extends Anowave_Package_Helper_Data
{
	/**
	 * Listing output cache tag
	 * 
	 * @var string
	 */
	const CACHE_LISTING = 'ec_cache_listing_';
	
	/**
	 * Listing data cache tag
	 * 
	 * @var string
	 */
	const CACHE_LISTING_DATA = 'ec_cache_listing_data_';
	
	/**
	 * Details output cache tag
	 * 
	 * @var string
	 */
	const CACHE_DETAILS = 'ec_cache_details_';
	
	/**
	 * Details data cache tag
	 * 
	 * @var string
	 */
	const CACHE_DETAILS_DATA = 'ec_cache_details_data_';
	
	/**
	 * Listing tag 
	 * 
	 * @var string
	 */
	const LISTING = 'catalog_category_view';
	
	/**
	 * Details tag
	 * 
	 * @var string
	 */
	const DETAILS = 'catalog_product_view';
	
	/**
	 * Cache lifetime (in seconds)
	 * 
	 * @var integer
	 */
	const CACHE_LIFETIME = 360;
	
	/**
	 * Check if cache is enabled
	 */
	public function useCache()
	{
		return Mage::app()->useCache('ec');
	}
	
	/**
	 * Load cache by tag 
	 * 
	 * @param string $tag
	 */
	public function load($id)
	{
		return Mage::app()->getCache()->load($this->generateCacheId($id), true);
	}
	
	/**
	 * Save cache
	 */
	public function save($content, $id)
	{
		Mage::app()->getCache()->save($content, $this->generateCacheId($id), array('ec'), Anowave_Ec_Helper_Cache::CACHE_LIFETIME);
		
		return $this;
	}
	
	/**
	 * Remove cache
	 */
	public function remove()
	{
		Mage::app()->getCache()->clean('all', array('ec'));
	}
	
	/**
	 * Generate unique cache id
	 * 
	 * @param string $prefix
	 */
	protected function generateCacheId($prefix)
	{
		/**
		 * Add store id
		 * 
		 * @var int
		 */
		$p[] = Mage::app()->getStore()->getId();
		
		/**
		 * Add website id
		 */
		$p[] = Mage::app()->getStore()->getWebsiteId();
		
		/**
		 * Push current currency
		 */
		$p[] = Mage::app()->getStore()->getCurrentCurrencyCode();
		
		/**
		 * Check for mobile users
		 */
		$p[] = Zend_Http_UserAgent_Mobile::match(Mage::helper('core/http')->getHttpUserAgent(),$_SERVER);
		
		/**
		 * Add customer group to cache
		 */
		$p[] = Mage::getSingleton('customer/session')->getCustomerGroupId();
		
		/**
		 * Push request URI
		 * 
		 * @var string
		 */
		$p[] = $_SERVER['REQUEST_URI'];

		/**
		 * Add request specific parameters to cache
		 */
		foreach (array($_GET, $_POST, $_FILES) as $request)
		{
			if ($request)
			{
				$p[] = $request;		
			}
		}
		
		/**
		 * Serialize
		 */
		$p = md5(serialize($p));
		
		/**
		 * Merge
		 */
		return "{$prefix}_{$p}";
	}
	
	/**
	 * Generate block cache id
	 * 
	 * @param Mage_Core_Block_Template $block
	 */
	public function generateBlockCacheId(Mage_Core_Block_Template $block)
	{
		return "{$this->generateCacheId('ec')}_{$block->getNameInLayout()}";
	}
}