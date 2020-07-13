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

class Anowave_Ec_Model_Api_Measurement_Protocol
{
	/**
	 * Client ID
	 * 
	 * @var UUID
	 */
	private $cid = null;
	
	/**
	 * Track order 
	 * 
	 * @param Mage_Sales_Model_Order $order
	 */
	public function purchase(Mage_Sales_Model_Order $order, $reverse = false)
	{
		/**
		 * Get default parameters 
		 * 
		 * @var []
		 */
		$default = $this->getDefaultParameters($order);

		/**
		 * Purchase payload 
		 * 
		 * @var []
		 */
		$default['pa'] = 'purchase';
		$default['ni'] = 1;
		$default['ti'] = $order->getIncrementId();
		$default['tr'] = Mage::helper('ec')->getRevenue($order);
		$default['ts'] = $order->getShippingInclTax();
		$default['tt'] = $order->getTaxAmount();
		$default['ta'] = Mage::helper('ec')->jsQuoteEscape(Mage::app()->getStore()->getFrontendName());
		$default['cu'] = $order->getOrderCurrencyCode();
		
		/**
		 * Check if this is transaction reversal
		 */
		if ($reverse)
		{
			$default['tr'] *= -1;
			$default['ts'] *= -1;
			$default['tt'] *= -1;
		}
		
		/**
		 * Default start position
		 * 
		 * @var int
		 */
		$index = 1;
		
		foreach ($this->getProducts($order) as $product)
		{
			$default["pr{$index}id"] = 			$product['id'];
			$default["pr{$index}nm"] = 			$product['name'];
			$default["pr{$index}ca"] = 			$product['category'];
			$default["pr{$index}pr"] = (float)  $product['price'];
			$default["pr{$index}qt"] = (int)   @$product['quantity'];
			$default["pr{$index}br"] = 			$product['brand'];
			
			/**
			 * Check if reverse and reverse quantity
			 */
			if ($reverse)
			{
				$default["pr{$index}qt"] *= -1;
				$default["pr{$index}pr"] *= -1;
			}
			
			$index++;
		}

		/**
		 * Init CURL
		 * 
		 * @var Resource
		 */
		$analytics = curl_init('https://ssl.google-analytics.com/collect');
			
		curl_setopt($analytics, CURLOPT_HEADER, 		0);
		curl_setopt($analytics, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($analytics, CURLOPT_POST, 			1);
		curl_setopt($analytics, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($analytics, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($analytics, CURLOPT_USERAGENT,		'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
			
		/**
		 * Get Universal Analytics ID 
		 * 
		 * @var string
		 */
		$ua = $this->getUA($order);
			
		if ($ua)
		{
			$data = $default;
			
			curl_setopt($analytics, CURLOPT_POSTFIELDS, utf8_encode
			(
				http_build_query($data)
			));
		}
		
		try
		{
			$response = curl_exec($analytics);
		
			if (!curl_error($analytics) && $response)
			{
				if (!$reverse)
				{
					Mage::getSingleton('core/session')->addNotice("Transaction tracking data ({$order->getIncrementId()}) sent to Google Analytics successfully. (ID:$ua)");
				}
				else 
				{
					Mage::getSingleton('core/session')->addNotice("Transaction tracking data ({$order->getIncrementId()}) reversed in Google Analytics successfully. (ID:$ua)");
				}

				return true;
			}
			else if(curl_error($ch)) 
			{
				throw new \Exception(curl_error($ch));
			}
		}
		catch (Exception $e)
		{
			Mage::getSingleton('core/session')->addError
			(
				$e->getMessage()
			);
		}
		
		return false;
	}
	
	/**
	 * Default parameters
	 * 
	 * @return []
	 */
	protected function getDefaultParameters(Mage_Sales_Model_Order $order)
	{
		return array
		(
			'v' 	=> 1,
			'tid' 	=> $this->getUA($order),
			'cid' 	=> $this->getCID(),
			't'		=> 'pageview',
			'dp'	=> "/{$this->getDp()}",
			'dh'	=> $_SERVER['HTTP_HOST'],
			'ua'	=> $_SERVER['HTTP_USER_AGENT']
		);	
	}
	
	/**
	 * Get Client ID
	 * 
	 * @var UUID
	 */
	protected function getCID()
	{
		if (!$this->cid)
		{
			/**
			 * Load CID from session
			 * 
			 * @var UUID
			 */
			$this->cid = Mage::getSingleton('core/session')->getCID();
			
			if (!$this->cid)
			{
				$this->cid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',mt_rand(0, 0xffff), mt_rand(0, 0xffff),mt_rand(0, 0xffff),mt_rand(0, 0x0fff) | 0x4000,mt_rand(0, 0x3fff) | 0x8000,mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
				
				Mage::getSingleton('core/session')->setCID($this->cid);
			}
		}
		
		return $this->cid;
	}
	
	/**
	 * Return Google Analytics UA-ID
	 * 
	 * @return string
	 */
	protected function getUA(Mage_Sales_Model_Order $order)
	{
		return Mage::getStoreConfig('ec/config/refund', $order->getStore()->getId());
	}
	
	/**
	 * Get document path
	 * 
	 * @return string
	 */
	protected function getDp()
	{
		return ltrim(str_replace(array('http://', 'https://', $_SERVER['HTTP_HOST']), '', $_SERVER['HTTP_REFERER']),'/');
	}
	
	/**
	 * Get order products array
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return []
	 */
	protected function getProducts(Mage_Sales_Model_Order $order)
	{
		$products = array();
	
		if ($order->getIsVirtual())
		{
			$address = $order->getBillingAddress();
		}
		else
		{
			$address = $order->getShippingAddress();
		}
	
		foreach ($order->getAllVisibleItems() as $item)
		{
			$product = Mage::getModel('catalog/product')->load
			(
				$item->getProductId()
			);
	
			$category = Mage::helper('ec/session')->getTrace()->get($product);
	
			/**
			 * Get product name
			*/
			$args = Mage::helper('ec')->getDefaultProductIdentifiers($item);
	
			/**
			 * AdWords Dynamic Remarketing product identifier
			*/
			$args->ecomm_prodid = Mage::helper('ec/remarketing')->getAdWordsRemarketingId($product);
	
			if ($product->isConfigurable())
			{
				$args = Mage::helper('ec')->getConfigurableProductIdentifiers($args, $product);
			}
	
			/**
			 * Construct variant
			 *
			 * @var []
			 */
			$variant = array();
				
			if ($product->getHasOptions())
			{
				$options = (array) $item->getProductOptions();
	
				if ($options && isset($options['options']))
				{
					foreach ($options['options'] as $option)
					{
						$variant[] = join(Anowave_Ec_Helper_Data::VARIANT_DELIMITER_ATT, array($option['label'], $option['value']));
					}
				}
			}
				
			/**
			 * Configurable product
			 */
			if ('configurable' == $product->getTypeId())
			{
				if ($item instanceof Mage_Sales_Model_Quote_Item)
				{
					$request = new Varien_Object(unserialize($item->getOptionByCode('info_buyRequest')->getValue()));
				}
				else if ($item instanceof Mage_Sales_Model_Order_Item)
				{
					$request = new Varien_Object($item->getProductOptions());
				}
	
				$options = $request->getData('info_buyRequest');
					
				if (isset($options['super_attribute']) && is_array($options['super_attribute']))
				{
					foreach ($options['super_attribute'] as $id => $option)
					{
						$attribute = Mage::getModel('catalog/resource_eav_attribute')->load($id);
							
						if ($attribute->usesSource())
						{
							$variant[] = join(Anowave_Ec_Helper_Data::VARIANT_DELIMITER_ATT, array($attribute->getFrontendLabel(), $attribute->getSource()->getOptionText($option)));
						}
					}
				}
			}
				
				
			@list($parents) = @Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild
			(
				$product->getId()
			);
				
			if ($parents)
			{
				/**
				 * Get parent product(s)
				 */
				$parent = Mage::getModel('catalog/product')->load((int) $parents);
	
				/**
				 * Change name to parent product name and pass variant instead
				*/
				if ($parent->getId())
				{
					if (Mage::helper('ec')->useConfigurableParent())
					{
						$args = Mage::helper('ec')->getConfigurableProductIdentifiers($args, $parent);
	
						$args->ecomm_prodid = Mage::helper('ec/remarketing')->getAdWordsRemarketingId($parent);
					}
						
					$category = Mage::helper('ec/session')->getTrace()->get($parent);
				}
			}
				
			/**
			 * Get category
			 *
			 * @var string
			 */
			$category = Mage::helper('ec')->getCategory($category);
				
			/**
			 * Add product to array of products
			 *
			 * @var []
			*/
			$products[] = array
			(
				'name' 							=> Mage::helper('ec')->jsQuoteEscape($args->name),
				'id' 							=> Mage::helper('ec')->jsQuoteEscape($args->id),
				'category' 						=> Mage::helper('ec')->jsQuoteEscape($category),
				'brand' 						=> Mage::helper('ec')->jsQuoteEscape(Mage::helper('ec')->getBrand($item->getProduct())),
				'price' 						=> Mage::helper('ec')->getPriceItem($item, $order),
				'price_excl_tax' 				=> Mage::helper('ec')->getPriceItemExclTax($item, $order),
				'quantity' 						=> $item->getQtyOrdered(),
				'coupon_discount_amount' 		=> $item->getDiscountAmount(),
				'coupon_discount_amount_abs' 	=> abs($item->getDiscountAmount()),
				'variant' 						=> $this->getVariant($variant)
			);
		}
	
		return $products;
	}
	
	/**
	 * Build variant parameter
	 *
	 * @var [] $variant
	 * @return string
	 */
	private function getVariant($variant = array())
	{
		return join(Anowave_Ec_Helper_Data::VARIANT_DELIMITER, $variant);
	}
}