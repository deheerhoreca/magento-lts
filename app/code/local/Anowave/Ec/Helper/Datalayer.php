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

class Anowave_Ec_Helper_Datalayer extends Anowave_Package_Helper_Data
{
	const LIST_UPSELLS 			= 'Up-sells';
	const LIST_CROSS_SELLS 		= 'Cross-sells';
	const LIST_RECENTLY_VIEWED	= 'Recently Viewed';
	const LIST_RELATED			= 'Related';
	
	/**
	 * Customer registration 
	 * 
	 * @return JSON
	 */
	public function getPushEventRegistration(Mage_Customer_Model_Customer $customer)
	{
		/**
		 * Check if customer is subscriber 
		 */
		$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail
		(
			$customer->getEmail()
		);
		
		return Mage::helper('ec/json')->encode
		(
			array
			(
				'event' 		=> 'registration',
				'eventCategory' => Mage::helper('ec')->__('Registration'),
				'eventAction'	=> Mage::helper('ec')->__('Register'),
				'eventLabel' 	=> $this->jsQuoteEscape
				(
					Mage::app()->getStore()->getFrontendName()
				),
				'userId'		=> $customer->getId(),
				'subscribed'	=> ($subscriber && $subscriber->getId()) ? true : false
			)
		);
	}
	
	/**
	 * Impressions push JSON
	 * 
	 * @return JSON
	 */
	public function getPushImpressions()
	{
		$block = Mage::app()->getLayout()->getBlock('product_list');
		
		if ($block)
		{
			if(Mage::registry('current_category'))
			{
				$category = Mage::registry('current_category');
			}
			else
			{
				$in = array();
			
				if (!$in)
				{
					$in[] = Mage::app()->getStore()->getRootCategoryId();
				}
				
				/**
				 * Load category model
				 *
				 * @var Mage_Catalog_Model_Category $category
				 */
				$category = $this->getCategoryModel((int) end($in));
			}
			
			/**
			 * DataLayer push
			 *
			 * @var array
			 */
			$data = array
			(
				'ecommerce' => array
				(
					'currencyCode'  => Mage::app()->getStore()->getCurrentCurrencyCode(),
					'impressions' 	=> array()
				)
			);
			
			$position = 1;
			
			/**
			 * Get category data 
			 * 
			 * @var StdClass $taxonomy
			 */
			$taxonomy = (object) array
			(
				'name' => Mage::helper('ec')->getCategory($category),
				'list' => Mage::helper('ec')->getCategoryList($category)
			);
			
			foreach ($this->getLoadedProductCollection($block) as $product)
			{
				$data['ecommerce']['impressions'][] = array
				(
					'list' 		=> $taxonomy->list,
					'id' 		=> $product->getSku(),
					'name' 		=> $product->getName(),
					'price' 	=> Mage::helper('ec/price')->getPrice($product),
					'brand'		=> Mage::helper('ec')->getBrand($product),
					'category' 	=> $taxonomy->name,
					'position' 	=> $position++
				);
			}
			
			$attributes = Mage::helper('ec/attributes')->getAttributes();
			
			foreach ($data['ecommerce']['impressions'] as &$impression)
			{
				foreach ($attributes as $key => $value)
				{
					$impression[$key] = $value;
				}
			}
			
			unset($impression);
			
			/**
			 * Create transport object
			 *
			 * @var \Varien_Object
			 */
			$object = new Varien_Object
			(
				array
				(
					'impressions' => $data
				)
			);
			
			Mage::dispatchEvent('ec_get_impression_data_after', array
			(
				'object' => $object
			));
			
			/**
			 * Get data from transport 
			 * 
			 * @var []
			 */
			$data = $object->getImpressions();
			
			$content_ids = array();
			
			foreach ($data['ecommerce']['impressions'] as $entity)
			{
				$content_ids[] = $entity['id'];
			}
			
			$content_name =  $taxonomy->name;
			
			$fbq = array
			(
				'content_name'		=> $content_name,
				'content_category' 	=> $content_name,
				'content_type' 		=> 'product',
				'content_ids' 		=> $content_ids
			);

			$response = (object) array
			(
				'fbq'				=> Mage::helper('ec/json')->encode($fbq),
				'data' 				=> Mage::helper('ec/json')->encode($data),
				'google_tag_params' => array
				(
					'ecomm_category' => $this->jsQuoteEscape($taxonomy->list)
				)
			); 
			
			/**
			 * Cache data
			 */
			if (Mage::helper('ec/cache')->useCache())
			{
				Mage::helper('ec/cache')->save(Mage::helper('ec/json')->encode($response), Anowave_Ec_Helper_Cache::CACHE_LISTING_DATA);
			}
			
			return $response;
		}
		else 
		{
			if (Mage::app()->getFrontController()->getAction() && Mage::helper('ec/cache')->useCache() && Anowave_Ec_Helper_Cache::LISTING === Mage::app()->getFrontController()->getAction()->getFullActionName())
			{
				if (false !== $data = Mage::helper('ec/cache')->load(Anowave_Ec_Helper_Cache::CACHE_LISTING_DATA))
				{
					return (object) Mage::helper('ec/json')->decode($data, true);
				}	
			}
		}
		
		return false;
	}
	
	/**
	 * Get recently viewed products 
	 * 
	 * @return StdClass|boolean
	 */
	public function getPushRecentlyViewed()
	{
		/**
		 * @todo: Test/check combined impressions push. Ensure it does NOT collide with category impression push
		 */
		if (false)
		{
			/**
			 * DataLayer push
			 *
			 * @var array
			 */
			$impressions = $this->getRecentlyViewed();
			
			if ($impressions)
			{
				$data = array
				(
					'event' 	=> 'impressionsRecentlyViewed',
					'ecommerce' => array
					(
						'currencyCode'  => Mage::app()->getStore()->getCurrentCurrencyCode(),
						'impressions' 	=> $impressions
					)
				);
				
				return (object) array
				(
					'data' => Mage::helper('ec/json')->encode($data)
				);
			}
		}
		
		return false;
		
		
	}
	
	/**
	 * Get push details & AdWords Dynamic remarketing data
	 * 
	 * @return array
	 */
	public function getPushDetail()
	{
		$block = Mage::app()->getLayout()->getBlock('product.info');
		
		if ($block)
		{
			if(Mage::registry('current_category'))
			{
				$category = Mage::registry('current_category');
			}
			else
			{
				$in = (array) $block->getProduct()->getCategoryIds();
				
				if (!$in)
				{
					$in[] = Mage::app()->getStore()->getRootCategoryId();
				}
				
				/**
				 * Load category model 
				 * 
				 * @var Mage_Catalog_Model_Category $category
				 */
				$category = $this->getCategoryModel((int) end($in));
			}
			
			$ecomm = array
			(
				'i' => array(),
				'p' => array(),
				'v' => array()
			);
			
			/**
			 * Grouped products collection
			 * 
			 * @var ArrayAccess
			 */
			$grouped = array();
			
			/* Check if product is configurable */
			if ('grouped' == $block->getProduct()->getTypeId())
			{
				foreach ($block->getProduct()->getTypeInstance(true)->getAssociatedProducts($block->getProduct()) as $product)
				{
					$child = $product;
					
					/**
					 * Set category
					 */
					$child->setCategory($category);
					
					$grouped[] = $child;
				}
			}
			
			$data = array
			(
				'ecommerce' => array
				(
					'currencyCode' => Mage::app()->getStore()->getCurrentCurrencyCode(),
					'detail' => array
					(
						'actionField' => array
						(
							'list' => Mage::helper('ec')->getCategoryList($category)
						),
						'products' => array()
					)
				)
			);

			$products = array();

			if (!$grouped)
			{
				/**
				 * Push produuct
				 */
				$products[] = array
				(
					'name' 		=> $block->getProduct()->getName(),
					'id' 		=> $block->getProduct()->getSku(),
					'brand' 	=> Mage::helper('ec')->getBrand($block->getProduct()),
					'category' 	=> Mage::helper('ec')->getCategory($category),
					'price' 	=> Mage::helper('ec/price')->getPrice($block->getProduct())
				);
				
	
				$ecomm['i'] = Mage::helper('ec/remarketing')->getAdwordsRemarketingId($block->getProduct());
				$ecomm['p'] = $block->getProduct()->getName();
				$ecomm['v'] = Mage::helper('ec/price')->getPrice
				(
					$block->getProduct()
				);
			}
			else 
			{
				/**
				 * Push grouped products
				 */
				foreach ($grouped as $entity)
				{
					$products[] = array
					(
						'name' 		=> $entity->getName(),
						'id' 		=> $entity->getSku(),
						'brand' 	=> Mage::helper('ec')->getBrand($entity),
						'category' 	=> Mage::helper('ec')->getCategory($category),
						'price' 	=> Mage::helper('ec/price')->getPrice($entity)
					);
				}
				
				$ecomm['i'] = Mage::helper('ec/remarketing')->getAdwordsRemarketingId($block->getProduct());
				$ecomm['p'] = $block->getProduct()->getName();
				$ecomm['v'] = Mage::helper('ec/price')->getPrice
				(
					$block->getProduct()
				);
			}
			
			$attributes = Mage::helper('ec/attributes')->getAttributes();
			
			foreach ($products as &$product)
			{
				foreach ($attributes as $key => $value)
				{
					$product[$key] = $value;
				}
			}
			
			unset($product);
			
			$data['ecommerce']['detail']['products'] = $products;
			
			/**
			 * Combine detail & impressions (related products, up-sell, cross-sell)
			 */
			
			if ($combine = $this->getUpSells())
			{
				foreach ($combine as $item)
				{
					$data['ecommerce']['impressions'][] = $item;
				}
			}
			
			/**
			 * Combine recently viewed products
			 */
			if ($combine = $this->getRecentlyViewed())
			{
				foreach ($combine as $item)
				{
					$data['ecommerce']['impressions'][] = $item;
				}
			}
			
			/**
			 * Combine related products
			 */
			if ($combine = $this->getRelatedProducts($block->getProduct()))
			{
				foreach ($combine as $item)
				{
					$data['ecommerce']['impressions'][] = $item;
				}
			}
			
			/**
			 * Create transport object
			 *
			 * @var \Varien_Object
			 */
			$object = new Varien_Object
			(
				array
				(
					'detail' => $data
				)
			);
			
			/**
			 * Notify others
			 */
			Mage::dispatchEvent('ec_get_detail_data_after', array
			(
				'object' => $object
			));
			
			$data = $object->getDetail();
			
			/**
			 * Get response object 
			 * 
			 * @var StdClass $response
			 */
			$response = (object) array
			(
				'data' 				=> Mage::helper('ec/json')->encode($data),
				'grouped'			=> $grouped,
				'google_tag_params' => array
				(
					'ecomm_pagetype' 	=> 'product',
					'ecomm_prodid' 		=> Mage::helper('ec/json')->encode($ecomm['i']),
					'ecomm_pname'		=> Mage::helper('ec/json')->encode($ecomm['p']),
					'ecomm_pvalue'		=> Mage::helper('ec/json')->encode($ecomm['v']),
					'ecomm_totalvalue'	=> Mage::helper('ec/price')->getPrice
					(
						$block->getProduct()
					),
					'ecomm_category' => $this->jsQuoteEscape(Mage::helper('ec')->getCategoryList($category))
				),
				'fbq' => Mage::helper('ec/json')->encode
				(
					array
					(
						'content_type' 		=> 'product',
						'content_name' 		=> $this->jsQuoteEscape($block->getProduct()->getName()),
						'content_category' 	=> $this->jsQuoteEscape(Mage::helper('ec')->getCategoryList($category)),
						'content_ids' 		=> array
						(
							$this->jsQuoteEscape($block->getProduct()->getSku())
						),
						'currency' 			=> Mage::app()->getStore()->getCurrentCurrencyCode(),
						'value' 			=> Mage::helper('ec/price')->getPrice($block->getProduct())
					)
				)
			);
			
			if (Mage::helper('ec/cache')->useCache())
			{
				Mage::helper('ec/cache')->save(Mage::helper('ec/json')->encode($response), Anowave_Ec_Helper_Cache::CACHE_DETAILS_DATA);
			}
			
			return $response;
		}
		else 
		{
			if (Mage::helper('ec/cache')->useCache() && Anowave_Ec_Helper_Cache::DETAILS === Mage::app()->getFrontController()->getAction()->getFullActionName())
			{
				if (false !== $data = Mage::helper('ec/cache')->load(Anowave_Ec_Helper_Cache::CACHE_DETAILS_DATA))
				{
					/**
					 * Return response
					 */
					return (object) json_decode($data, true);
				}
			}
		}
		
		return false;
	}
	
	public function getPushSearch()
	{
		$block = Mage::app()->getLayout()->getBlock('search_result_list');
		
		if ($block)
		{
			/**
			 * DataLayer push
			 *
			 * @var array
			 */
			$data = array
			(
				'ecommerce' => array
				(
					'currencyCode'  => Mage::app()->getStore()->getCurrentCurrencyCode(),
					'actionField' => array
					(
						'list' => Mage::helper('ec')->__('Search Results')
					),
					'impressions' 	=> array()
				)
			);
			
			/**
			 * Default starting position 
			 * 
			 * @var integer $position
			 */
			$position = 1;
			
			foreach ($this->getLoadedProductCollection($block) as $product)
			{
				$in = $product->getCategoryIds();
				
				if (!$in)
				{
					$in[] = Mage::app()->getStore()->getRootCategoryId();
				}
				
				/**
				 * Load category model 
				 * 
				 * @var Mage_Catalog_Model_Category $category
				 */
				$category = $this->getCategoryModel((int) end($in));
				
				$data['ecommerce']['impressions'][] = array
				(
					'list' 		=> Mage::helper('ec')->getCategoryList($category),
					'id' 		=> $product->getSku(),
					'name' 		=> $product->getName(),
					'price' 	=> $product->getFinalPrice(),
					'brand'		=> Mage::helper('ec')->getBrand($product),
					'category' 	=> Mage::helper('ec')->getCategory($category),
					'position' 	=> $position++
				);
			}
			
			if (!isset($category))
			{
				$category = null;
			}
			
			return (object) array
			(
				'data' 				=> Mage::helper('ec/json')->encode($data),
				'google_tag_params' => array('ecomm_category' => $this->jsQuoteEscape(Mage::helper('ec')->getCategoryList($category)))
			);
		}
		
		return false;
	}
	
	/**
	 * Get purchase push 
	 * 
	 * @param Anowave_Ec_Block_Track $block
	 * @return JSON|boolean
	 */
	public function getPushPurchase(Anowave_Ec_Block_Track $block)
	{
		if ($block->getOrderIds())
		{
			$data = array
			(
				'data' 				=> array(),
				'google_tag_params' => array
				(
					'ecomm_prodid' 		=> array(),
					'ecomm_pvalue' 		=> array(),
					'ecomm_pname'  		=> array(),
					'ecomm_totalvalue' 	=> 0
				)
			);
			
			$collection = Mage::getResourceModel('sales/order_collection')->addFieldToFilter('entity_id', array
			(
				'in' => $block->getOrderIds())
			);
			
			foreach ($collection as $order)
			{
				/**
				 * Get order revenue
				 *
				 * @var float $revenue
				 */
				$revenue = Mage::helper('ec')->getRevenue($order);
				
				/**
				 * Get order data 
				 * 
				 * @var array $products
				 */
				$order_data = Mage::helper('ec')->getOrderProducts($order);

				/**
				 * Get products 
				 * 
				 * @var [] $products
				 */
				$products = $order_data->products; 
				
				/**
				 * Get Google Tag Params
				 */
				foreach ($order_data->google_tag_params as $item)
				{
					$data['google_tag_params']['ecomm_prodid'][] = $item['ecomm_prodid'];
					$data['google_tag_params']['ecomm_pvalue'][] = $item['ecomm_pvalue'];
					$data['google_tag_params']['ecomm_pname'][]	 = $item['ecomm_pname'];
				}

				/**
				 * Set AdWords Dynamic Remarketing Revenue
				 */
				$data['google_tag_params']['ecomm_totalvalue'] = (float) $revenue;
				
				/**
				 * Sales coupon
				 * 
				 * @var string $coupon
				 */
				$coupon = null;
				
				if ($order->getCouponCode())
				{
					$code = Mage::getModel('salesrule/coupon')->load($order->getCouponCode(), 'code');
					
					if ($code)
					{
						$rule = Mage::getModel('salesrule/rule')->load
						(
							$code->getRuleId()
						);
						
						$coupon = $rule->getName();
					}
				}
				else 
				{
					$rules = array();
					
					$rule_ids = explode(chr(44), (string) $order->getAppliedRuleIds());
					
					if ($rule_ids)
					{
						foreach ($rule_ids as $rule_id)
						{
							$rule = Mage::getModel('salesrule/rule')->load($rule_id);
							
							if ($rule && $rule->getId())
							{
								$rules[] = $rule->getName();
							}
						}
					}
					
					if ($rules)
					{
						$coupon = join(chr(44), $rules);
					}
				}
				
				$data['data'] = array
				(
					'ecommerce' => array
					(
						'currencyCode' => Mage::app()->getStore()->getCurrentCurrencyCode(),
						'purchase' => array
						(
							'actionField' => array
							(
								'id' 							=> 		   $order->getIncrementId(),
								'affiliation' 					=> 		   Mage::helper('ec/affiliation')->getAffiliation(),
								'revenue' 						=> (float) $revenue,
								'revenue_base' 					=> (float) $order->getBaseGrandTotal(),
								'tax' 							=> (float) $order->getTaxAmount(),
								'tax_base' 						=> (float) $order->getBaseTaxAmount(),
								'shipping' 						=> (float) $order->getShippingInclTax(),
								'shipping_base' 				=> (float) $order->getBaseShippingAmount(),
								'coupon' 						=> 		   $coupon,
								'coupon_code' 					=> 		   $coupon,
								'coupon_discount_amount' 		=> 		   $order->getDiscountAmount(),
								'coupon_discount_amount_abs' 	=> 	   abs($order->getDiscountAmount())
							),
							'products' => $products
						)
					)
				);
			}
			
			$object = new Varien_Object
			(
				array
				(
					'purchase' => $data
				)
			);
			
			Mage::dispatchEvent('ec_order_purchase_get_after', array
			(
				'object' => $object
			));
			
			/**
			 * Get data from transport
			 *
			 * @var []
			 */
			$data = $object->getPurchase();
			
			return (object) array
			(
				'data' 				=> Mage::helper('ec/json')->encode($data['data']),
				'google_tag_params' => Mage::helper('ec/json')->encode($data['google_tag_params'])
			);
		}

		return false;
	}
	
	/**
	 * Get recently viewed products
	 * 
	 * @return array
	 */
	public function getRecentlyViewed()
	{
		$impressions = array();
		
		foreach (array(Mage::app()->getLayout()->getBlock('left.reports.product.viewed'), Mage::app()->getLayout()->getBlock('right.reports.product.viewed')) as $block)
		{
			if ($block && $this->isRenderable($block))
			{
				$position = 1;
					
				foreach ($block->getItemsCollection() as $product)
				{
					$in = (array) $product->getCategoryIds();
			
					if (!$in)
					{
						$in[] = Mage::app()->getStore()->getRootCategoryId();
					}
					
					/**
					 * Load category model 
					 * 
					 * @var Mage_Catalog_Model_Category $category
					 */
					$category = $this->getCategoryModel((int) end($in));

					$impressions[] = array
					(
						'id' 		=> $product->getSku(),
						'name' 		=> $product->getName(),
						'price' 	=> $product->getFinalPrice(),
						'list' 		=> Mage::helper('ec')->__(self::LIST_RECENTLY_VIEWED),
						'brand'		=> Mage::helper('ec')->getBrand($product),
						'category' 	=> Mage::helper('ec')->getCategory($category),
						'position' 	=> $position++
					);
				}
			}
		}
		
		return $impressions;
	}

	/**
	 * Get up-sell products 
	 * 
	 * @return array
	 */
	public function getUpSells()
	{
		$block = Mage::app()->getLayout()->getBlock('product.info.upsell');
		
		if ($block && $block->getProduct() && $this->isRenderable($block))
		{
			$impressions = array();
			
			$position = 1;
			
			$collection = $block->getProduct()->getUpSellProductCollection()->setPositionOrder()->addStoreFilter()->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
			
			foreach ($collection as $product)
			{
				$in = (array) $product->getCategoryIds();
				
				if (!$in)
				{
					$in[] = Mage::app()->getStore()->getRootCategoryId();
				}
				
				/**
				 * Load category model 
				 * 
				 * @var Mage_Catalog_Model_Category $category
				 */
				$category = $this->getCategoryModel((int) end($in));

				$impressions[] = array
				(
					
					'id' 		=> $product->getSku(),
					'name' 		=> $product->getName(),
					'price' 	=> $product->getFinalPrice(),
					'list' 		=> Mage::helper('ec')->__(self::LIST_UPSELLS),
					'brand'		=> Mage::helper('ec')->getBrand($product),
					'category' 	=> Mage::helper('ec')->getCategory($category),
					'position' 	=> $position++
				);
			}

			return $impressions;
		}
		
		return array();
	}
	
	/**
	 * Get related products 
	 * 
	 * @param Mage_Catalog_Model_Product $current
	 */
	public function getRelatedProducts(Mage_Catalog_Model_Product $current)
	{
		if ($current->getRelatedProductIds())
		{
			$impressions = array();
			
			$position = 1;
			
			foreach ((array) $current->getRelatedProductIds() as $id) 
			{
				$product = Mage::getModel('catalog/product')->load($id);
				
				$in = (array) $product->getCategoryIds();
				
				if (!$in)
				{
					$in[] = Mage::app()->getStore()->getRootCategoryId();
				}
				
				/**
				 * Load category model
				 *
				 * @var Mage_Catalog_Model_Category $category
				 */
				$category = $this->getCategoryModel((int) end($in));
				
				$impressions[] = array
				(
					'id' 		=> $product->getSku(),
					'name' 		=> $product->getName(),
					'price' 	=> $product->getFinalPrice(),
					'list' 		=> Mage::helper('ec')->__(self::LIST_RELATED),
					'brand'		=> Mage::helper('ec')->getBrand($product),
					'category' 	=> Mage::helper('ec')->getCategory($category),
					'position' 	=> $position++
				);
			}

			return $impressions;
		}
		
		return array();
	}
	
	/**
	 * Get current loaded collection
	 * 
	 * @param Mage_Catalog_Block_Product_List $block
	 */
	public function getLoadedProductCollection(Mage_Catalog_Block_Product_List $block = null)
	{
		if (!$block)
		{
			$block = Mage::app()->getLayout()->getBlock('product_list');
		}
		
		if ($block)
		{
			$collection = $block->getLoadedProductCollection();
			
			if ($collection)
			{
				/**
				 * Simulate _beforeToHtml()
				 */
				$toolbar = $block->getToolbarBlock();
				
				if ($toolbar)
				{	
					if ($orders = $block->getAvailableOrders()) 
					{
						$toolbar->setAvailableOrders($orders);
					}
					if ($sort = $block->getSortBy()) 
					{
						$toolbar->setDefaultOrder($sort);
					}
					
					if ($dir = $block->getDefaultDirection()) 
					{
						$toolbar->setDefaultDirection($dir);
					}
					
					if ($modes = $block->getModes()) 
					{
						$toolbar->setModes($modes);
					}
					
					if ('all' == $limit = $toolbar->getLimit())
					{
						$limit = 0;
					}

					/**
					 * Configure collection
					 */
					$collection->setCurPage($toolbar->getCurrentPage())->setPageSize($limit)->setOrder($toolbar->getCurrentOrder(), $toolbar->getCurrentDirection());
					
					/**
					 * Create transport object
					 *
					 * @var \Varien_Object
					 */
					$object = new Varien_Object
					(
						array
						(
							'collection' => $collection
						)
					);
					
					Mage::dispatchEvent('ec_get_toolbar_collection', array
					(
						'object' => $object
					));

					return $object->getCollection();
				}
				else 
				{
					if ($collection->getSize())
					{
						return $collection;
					}
				}
				
			}
		}
		
		return array();		
	}
	
	public function getCouponDiscountPush()
	{
		$discount = $this->getCouponCodeDiscount();
		
		if ($discount > 0)
		{
			return Mage::helper('ec/json')->encode(array
			(
				'coupon_discount_amount' 	=> $discount,
				'coupon_code' 				=> Mage::getSingleton('checkout/session')->getQuote()->getCouponCode()
			));
		}
		
		return false;
	}
	
	public function getCouponCode()
	{
		return Mage::getSingleton('checkout/session')->getQuote()->getCouponCode();
	}
	

	/**
	 * Check if coupon was applied and get the discount amount
	 * 
	 * @return int
	 */
	public function getCouponCodeDiscount()
	{
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		
		$code = $quote->getCouponCode();
		
		/**
		 * Default discount to 0
		 * 
		 * @var int
		 */
		$discount = 0;
	
		if ('' !== (string) $code)
		{
			foreach ($quote->getAllItems() as $item)
			{
				$discount += (float) $item->getDiscountAmount();
			}
			
			$discount += (float) $quote->getGiftCardsAmountUsed();
			
			return $discount;
		}
		
		return $discount;
	}
	
	/**
	 * Get newsletter event 
	 * 
	 * @return JSON|NULL
	 */
	public function getNewsletterEvent()
	{
		$event = Mage::getSingleton('core/session')->getNewsletterEvent();
		
		if ($event)	
		{
			Mage::getSingleton('core/session')->unsetData('newsletter_event');
			
			return $event;
		}
		
		return null;
	}
	
	/**
	 * Get contact event
	 *
	 * @return JSON|NULL
	 */
	public function getContactEvent()
	{
		$event = Mage::getSingleton('core/session')->getContactEvent();
		
		if ($event)
		{
			Mage::getSingleton('core/session')->unsetData('contact_event');
			
			return $event;
		}
		
		return null;
	}
	
	/**
	 * Escape string for JSON 
	 * 
	 * @see Mage_Core_Helper_Abstract::jsQuoteEscape()
	 */
	public function jsQuoteEscape($data, $quote='\'')
	{
		return trim
		(
			Mage::helper('ec')->jsQuoteEscape($data)
		);
	}
	
	/**
	 * Get category model 
	 * 
	 * @param number $id
	 * 
	 * @return Mage_Catalog_Model_Category
	 */
	public function getCategoryModel($id = 0)
	{
		if (!isset($this->categories[$id]))
		{
			try 
			{
				$this->categories[$id] = Mage::getModel('catalog/category')->load($id);
			}
			catch (\Exception $e)
			{
				$this->categories[$id] =  Mage::getModel('catalog/category')->load
				(
					Mage::app()->getStore()->getRootCategoryId()
				);
				
				return $this->categories[$id];
			}
		}
		
		return $this->categories[$id];
	}
	
	/**
	 * Check if block is to be rendered
	 * 
	 * @param string $block
	 */
	protected function isRenderable($block)
	{
		$handles = Mage::app()->getLayout()->getUpdate()->getHandles();
		
		switch ($block->getNameInLayout())
		{
			case 'left.reports.product.viewed':
			case 'right.reports.product.viewed':
				
				if (in_array('catalog_product_view', $handles))
				{
					return false;
				}
				
				break;
		}
		
		return true;
	}
}