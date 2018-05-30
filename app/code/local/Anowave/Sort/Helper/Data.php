<?php
/**
 * Anowave Magento Sort Category Products by Drag & Drop
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
 * @package 	Anowave_Sort
 * @copyright 	Copyright (c) 2017 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
 
class Anowave_Sort_Helper_Data extends Anowave_Package_Helper_Data
{
	/**
	 * Package Stock Keeping Unit
	 *
	 * @var string
	 */
	protected $package = 'MAGE-DRAGDROP';
	
	/**
	 * Package Config
	 * 
	 * @var string
	 */
	protected $config = 'sort/settings/license';
	
	public function isLimitApplied()
	{
		$params = Mage::app()->getRequest()->getParams();
		
		return 
		(
			isset($params['limit']) && 0 === (int) $params['limit']
		) ? false : true;
	}
	
	public function getLimit()
	{
		return Mage::app()->getRequest()->getParam('limit');
	}
}