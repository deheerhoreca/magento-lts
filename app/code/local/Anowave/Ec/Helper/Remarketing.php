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

class Anowave_Ec_Helper_Remarketing extends Anowave_Ec_Helper_Data
{
	/**
	 * Get AdWords Dynamic Remarketing Parameters
	 * 
	 * @param string|JSON $item
	 */
	public function getParameters(Mage_Sales_Model_Quote_Item $item)
	{
		$args = $this->getDefaultProductIdentifiers($item);
		
		$data = array();
		
		$data['i'] = $this->getAdWordsRemarketingItemId($item);
		
		if ($item->getProduct()->isConfigurable())
		{
			$parent = Mage::getModel('catalog/product')->load
			(
				$item->getProductId()
			);
			
			/**
			 * Swap configurable data
			 *
			 * @var stdClass
			 */
			$args = $this->getConfigurableProductIdentifiers($args, $parent);
			
			if ($this->useConfigurableParent())
			{
				$data['i'] =  $this->getAdWordsRemarketingId($parent);
			}
		}
		
		/**
		 * Set price
		 * 
		 * @var float
		 */
		$data['v'] = $item->getPriceInclTax();
		
		/**
		 * Set name
		 * 
		 * @var float
		 */
		$data['p'] = $args->name;
		
		return Mage::helper('ec/json')->encode($data);
	}
	
	/**
	 * Retrieve ecomm_prodid attribute from product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 */
	public function getAdWordsRemarketingId(Mage_Catalog_Model_Product $product)
	{
		$attribute = Mage::getStoreConfig('ec/dynamic_remarketing/attribute');
	
		if ('' !== $attribute)
		{
			if ('id' == $attribute)
			{
				return $product->getId();
			}
			else
			{
				$value = $product->getData($attribute);
	
				if (is_string($value))
				{
					return $this->jsQuoteEscape($value);
				}
			}
		}
	
		return $this->jsQuoteEscape
		(
			$product->getSku()
		);
	}

	/**
	 * Retrieve ecomm_prodid attribute from quote item
	 *
	 * @param Mage_Sales_Model_Order_Item $product
	 */
	public function getAdWordsRemarketingItemId($item)
	{
	    if ($item->getProduct())
	    {
	        return $this->getAdWordsRemarketingId($item->getProduct());
	    }
	        
		$attribute = Mage::getStoreConfig('ec/dynamic_remarketing/attribute');

		if ('' !== $attribute)
		{
			if ('id' == $attribute)
			{
				return $item->getId();
			}
			else
			{
				$value = $item->getData($attribute);
	
				if (is_string($value))
				{
					return $value;
				}
			}
		}
	
		return $this->jsQuoteEscape
		(
			$item->getSku()
		);
	}
}