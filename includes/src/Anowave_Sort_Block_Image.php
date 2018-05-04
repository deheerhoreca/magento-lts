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
 
class Anowave_Sort_Block_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
	{
		$product = Mage::getModel('catalog/product')->load($row->getEntityId());
		
		try 
		{
			$source = $this->helper('catalog/image')->init($product, 'image')->keepFrame(false)->constrainOnly(true)->resize(70,70);
			
			return "<img src='$source' alt='' title='' />"; 
		}
		catch (\Exception $e)
		{
			
		}
		
		return '';
	}
}