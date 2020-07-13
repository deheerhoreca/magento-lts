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

class Anowave_Ec_Helper_Data extends Anowave_Package_Helper_Data
{
	/**
	 * Variant delimiter
	 *
	 * @var string
	 */
	const VARIANT_DELIMITER = '-';
	
	/**
	 * Variant attributes delimiter
	 *
	 * @var string
	 */
	const VARIANT_DELIMITER_ATT = ':';
	
	/**
	 * AdWords Dynamic Remarketing Mapping Field
	 *
	 * @var string
	 */
	const DEFAULT_CUSTOM_OPTION_FIELD = 'sku';
	
	/**
	 * Package Stock Keeping Unit
	 *
	 * @var string
	 */
	protected $package = 'MAGE-GTM';
	
	/**
	 * License config key
	 *
	 * @var string
	 */
	protected $config = 'ec/config/license';
	
	/**
	 * Orders
	 *
	 * @var mixed
	 */
	protected $orders = null;
	
	/**
	 * Brand map
	 *
	 * @var array
	 */
	protected $brandMap = array();
	
	/**
	 * Check if Facebook Pixel Tracking is enabled
	 *
	 * @return boolean
	 */
	public function facebook()
	{
		return (bool) Mage::getStoreConfig('ec/facebook/enable');
	}
	
	/**
	 * Get visitor
	 *
	 * @return number
	 */
	public function getVisitorId()
	{
		if (Mage::getSingleton("customer/session")->isLoggedIn())
		{
			return (int) Mage::getSingleton("customer/session")->getCustomerId();
		}
		
		return 0;
	}
	
	/**
	 * Get visitor email
	 *
	 * @return string|null
	 */
	protected function getVisitorEmail()
	{
		if (Mage::getSingleton("customer/session")->isLoggedIn())
		{
			return Mage::getModel('customer/customer')->load(Mage::getSingleton("customer/session")->getCustomerId())->getEmail();
		}
		
		return null;
	}
	
	/**
	 * Check if module is active
	 */
	public function isActive()
	{
		return $this->filter((int) Mage::getStoreConfig('ec/config/active'));
	}
	
	/**
	 * Get visitor login state
	 *
	 * @return string
	 */
	public function getVisitorLoginState()
	{
		return Mage::getSingleton("customer/session")->isLoggedIn() ? 'Logged in':'Logged out';
	}
	
	/**
	 * Get visitor type
	 *
	 * @return string
	 */
	public function getVisitorType()
	{
		return (string) Mage::getModel('customer/group')->load(Mage::getSingleton("customer/session")->getCustomerGroupId())->getCode();
	}
	
	/**
	 * Get visitor lifetime value
	 *
	 * @return float
	 */
	public function getVisitorLifetimeValue()
	{
		$value = 0;
		
		foreach ($this->getOrders() as $order)
		{
			$value += $order->getGrandTotal();
		}
		
		if (Mage::getSingleton("customer/session")->isLoggedIn())
		{
			return round($value,2);
		}
		
		return 0;
	}
	
	/**
	 * Retrieve visitor's avarage purchase amount
	 *
	 * @return float
	 */
	public function getVisitorAvgTransValue()
	{
		$value = 0;
		$count = 0;
		
		foreach ($this->getOrders() as $order)
		{
			$value += $order->getGrandTotal();
			
			$count++;
		}
		
		if ($value && $count)
		{
			return round($value/$count,2);
		}
		
		return 0;
	}
	
	/**
	 * Get visitor existing customer
	 *
	 * @return string
	 */
	public function getVisitorExistingCustomer()
	{
		return $this->getVisitorLifetimeValue() ? 'Yes' : 'No';
	}
	
	/**
	 * Get standard custom dimensions
	 *
	 * @param void
	 * @return string JSON
	 */
	public function getCustomDimensions()
	{
		$dimensions = array
		(
			'pageType' => $this->getPageType()
		);
		
		/**
		 * Array of callbacks adding dimensions
		 */
		foreach (array
			(
				function ($dimensions)
				{
					$dimensions['pageName'] = Mage::app()->getLayout()->getBlock('head')->getTitle();
					
					return $dimensions;
		},
		function ($dimensions)
		{
			if(Mage::app()->getRequest()->getControllerName() == 'result' || Mage::app()->getRequest()->getControllerName() == 'advanced')
			{
				if (Mage::app()->getLayout()->getBlock('search_result_list'))
				{
					$dimensions['resultsCount'] = Mage::app()->getLayout()->getBlock('search_result_list')->getLoadedProductCollection()->getSize();
				}
			}
			
			return $dimensions;
		},
		function ($dimensions)
		{
			/**
			 * Check if category page
			 */
			if('catalog' == Mage::app()->getRequest()->getModuleName() && 'category' == Mage::app()->getRequest()->getControllerName())
			{
				/**
				 * Get applied layer filter(s)
				 */
				$filters = array();
				
				foreach ((array) Mage::getSingleton('catalog/layer')->getState()->getFilters() as $filter)
				{
					$filters[] = array
					(
						'label' => Mage::helper('ec')->getSanitized($filter->getName()),
						'value' => Mage::helper('ec')->getSanitized($filter->getLabel())
					);
				}
				
				$dimensions['filters'] = $filters;
				
				/**
				 * Count visible products
				 */
				if (Mage::app()->getLayout()->getBlock('product_list') && $filters)
				{
					if (null !== $collection = Mage::helper('ec/datalayer')->getLoadedProductCollection())
					{
						$dimensions['resultsCount'] = $collection->getSize();
					}
				}
			}
			
			return $dimensions;
		},
		function ($dimensions)
		{
			if (Mage::getSingleton("customer/session")->isLoggedIn())
			{
				$dimensions['avgTransVal'] = Mage::helper('ec')->getVisitorAvgTransValue();
			}
			
			return $dimensions;
		}
		) as $dimension)
		{
			$dimensions = (array) call_user_func($dimension, $dimensions);
		}
		
		return Mage::helper('ec/json')->encode($dimensions);
	}
	
	/**
	 * Get products in quote
	 *
	 * @return []
	 */
	public function getCheckoutProducts()
	{
		$products = array();
		
		foreach (Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems() as $item)
		{
			$args = $this->getDefaultProductIdentifiers($item);
			
			$variant = array();
			
			/**
			 * Bundled products
			 */
			if (Mage_Catalog_Model_Product_Type::TYPE_BUNDLE == $item->getProduct()->getTypeId())
			{
				$product = Mage::getModel('catalog/product')->load
				(
					$item->getProductId()
					);
				
				$options = $item->getData('product_options');
				
				if ($options)
				{
					/**
					 * Get options
					 *
					 * @var array
					 */
					$options = unserialize($options);
					
					/**
					 * Get buy request
					 *
					 * @var array $request
					 */
					$request = $options['info_buyRequest'];
					/**
					 * Get bundles
					 *
					 * @var array $bundles
					 */
					$bundles = $request['bundle_option'];
					
					$selections = $product->getTypeInstance(true)->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);
					
					foreach ($bundles as $option => $bundle)
					{
						foreach($selections as $selection)
						{
							if ($bundle == $selection->getSelectionId())
							{
								$child = Mage::getModel('catalog/product')->load($selection->getProductId());
								
								$variant[] = join(self::VARIANT_DELIMITER_ATT, array
									(
										$this->jsQuoteEscape($child->getName()),
										$this->jsQuoteEscape($child->getSku())
									));
								
								break;
							}
						}
						
					}
				}
			}
			
			/**
			 * Handle configurable products
			 */
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
				
				if ($item instanceof Mage_Sales_Model_Quote_Item)
				{
					$request = new Varien_Object(unserialize($item->getOptionByCode('info_buyRequest')->getValue()));
				}
				else if ($item instanceof Mage_Sales_Model_Order_Item)
				{
					$request = new Varien_Object($item->getProductOptions());
				}
				
				$options = $request->getData();
				
				if (isset($options['super_attribute']) && is_array($options['super_attribute']))
				{
					foreach ($options['super_attribute'] as $id => $option)
					{
						$attribute = Mage::getModel('catalog/resource_eav_attribute')->load($id);
						
						if ($attribute->usesSource())
						{
							$variant[] = join(self::VARIANT_DELIMITER_ATT, array
							(
								$attribute->getFrontendLabel(),
								$attribute->getSource()->getOptionText($option)
							));
						}
					}
				}
			}
			
			/**
			 * Handle products with custom options
			 */
			if (1 === (int) $item->getProduct()->getHasOptions())
			{
				if ($item instanceof Mage_Sales_Model_Quote_Item)
				{
					$request = new Varien_Object(unserialize($item->getOptionByCode('info_buyRequest')->getValue()));
				}
				else if ($item instanceof Mage_Sales_Model_Order_Item)
				{
					$request = new Varien_Object($item->getProductOptions());
				}
				
				if ((int) $request->getProduct() > 0)
				{
					$parent = Mage::getModel('catalog/product')->load
					(
						$request->getProduct()
					);
					
					if ($this->useConfigurableParent())
					{
						$args->id 	= $parent->getSku();
						$args->name = $parent->getName();
					}
					
					/**
					 * Get field to use for variants
					 *
					 * @var string
					 */
					$field = Mage::helper('ec')->getOptionUseField();
					
					$options = array();
					
					foreach ($parent->getProductOptionsCollection() as $option)
					{
						$data = $parent->getOptionById($option['option_id']);
						
						switch($data->getType())
						{
							case 'drop_down':
								foreach ($data->getValues() as $value)
								{
									$options[] = array
									(
										'id' 	=> $value->getOptionTypeId(),
										'value' => $value->getData($field),
										'title' => $data->getTitle()
									);
									
								}
								break;
							case 'field':
								$options[] = array
								(
									'value' => (string) $data->getData($field)
								);
								break;
							case 'checkbox':
								$options[] = array
								(
									'value' => (string) $data->getData($field)
								);
								break;
						}
					}
					
					if ($request->getOptions() && is_array($request->getOptions()))
					{
						foreach ($options as $option)
						{
							foreach ($request->getOptions() as $current)
							{
								if (is_array($option) && isset($option['id']) && (int) $current === (int) $option['id'])
								{
									$variant[] = join(self::VARIANT_DELIMITER_ATT,array
									(
										$option['title'],
										$option['value']
									));
								}
							}
						}
					}
				}
			}
			
			$category = Mage::helper('ec/session')->getTrace()->get($item->getProduct());
			
			$data = (object) array
			(
				'id' 			=> $args->id,
				'price' 		=> Mage::helper('ec/price')->getPrice($item->getProduct()),
				'remarketingId' => $this->useConfigurableParent() ? Mage::helper('ec/remarketing')->getAdWordsRemarketingId($item->getProduct()) : Mage::helper('ec/remarketing')->getAdWordsRemarketingItemId($item),
				'name' 			=> $args->name,
				'category' 		=> $this->getCategory($category),
				'list'			=> $this->getCategoryList($category),
				'brand' 		=> $this->getBrandBySku($args->id),
				'quantity' 		=> $item->getQty(),
				'variant' 		=> join(self::VARIANT_DELIMITER, $variant)
			);
			
			$products[] = $data;
		}
		
		$attributes = Mage::helper('ec/attributes')->getAttributes();
		
		foreach ($products as &$product)
		{
			foreach ($attributes as $key => $value)
			{
				$product->$key = $value;
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
				'products' => $products
			)
		);
		
		Mage::dispatchEvent('ec_checkout_products_get_after', array
		(
			'object' => $object
		));
		
		return $object->getProducts();
	}
	
	/**
	 * Get order products
	 *
	 * @param Mage_Sales_Model_Order
	 */
	public function getOrderProducts($order)
	{
		/**
		 * Order products
		 *
		 * @var []
		 */
		$products = array();
		
		/**
		 * Google Tag Params (AdWords Dynamic Remarketing
		 * 
		 * @var array $google_tag_params
		 */
		$google_tag_params = array();
		
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
			$args = $this->getDefaultProductIdentifiers($item);
			
			/**
			 * AdWords Dynamic Remarketing product identifier
			 */
			$args->ecomm_prodid = Mage::helper('ec/remarketing')->getAdWordsRemarketingItemId($item);
			
			/**
			 * @global Configurable support
			 */
			if ($product->isConfigurable())
			{
				$args = $this->getConfigurableProductIdentifiers($args, $product);
				
				/**
				 * Get AdWords Dynamic Remarketing Id
				 */
				if ($this->useConfigurableParent())
				{
					if ($this->useConfigurableParentEcommProdid())
					{
						/**
						 * Load configurable parent SKU
						 */
						$args->ecomm_prodid = Mage::helper('ec/remarketing')->getAdWordsRemarketingId($product);
					}
					else 
					{
						/**
						 * Load simple item SKU
						 */
						$args->ecomm_prodid = Mage::helper('ec/remarketing')->getAdWordsRemarketingItemId($item);
					}
				}
			}
			
			$variant = array();
			
			/**
			 * @global Custom options support
			 */
			if ($product->getHasOptions())
			{
				$options = (array) $item->getProductOptions();
				
				if ($options && isset($options['options']))
				{
					foreach ($options['options'] as $option)
					{
						$variant[] = join(Anowave_Ec_Helper_Data::VARIANT_DELIMITER_ATT, array($option['label'],$option['value']));
					}
				}
			}
			
			/**
			 * @global Configurable support (Variations)
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
							$variant[] = join(Anowave_Ec_Helper_Data::VARIANT_DELIMITER_ATT, array($attribute->getFrontendLabel(),$attribute->getSource()->getOptionText($option)));
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
				 * @global Switch to configurable properties (Use Configurable)
				 */
				if ($parent->getId() && Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE === $parent->getTypeId())
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
			 */
			$category = $this->getCategory($category);
			
			/**
			 * Data push
			 *
			 * @var array $data
			 */
			$data = array
			(
				'name' 							=>     $args->name,
				'id' 							=>     $args->id,
				'category' 						=>     $category,
				'brand' 						=>     $this->getBrand($item->getProduct()),
				'price' 						=>     $this->getPriceItem($item, $order),
				'price_excl_tax' 				=>     $this->getPriceItemExclTax($item, $order),
				'quantity'  					=> 	   $item->getQtyOrdered(),
				'coupon_discount_amount' 		=>     $item->getDiscountAmount(),
				'coupon_discount_amount_abs' 	=> abs($item->getDiscountAmount()),
				'variant' 						=> join
				(
					self::VARIANT_DELIMITER, $variant
				)
			);
			
			/**
			 * Google Tag Params push
			 */
			$google_tag_params[] = array
			(
				'ecomm_prodid' => $args->ecomm_prodid,
				'ecomm_pvalue' => $data['price'],
				'ecomm_pname'  => $data['name']	
			);
			
			if ($item->getAppliedRuleIds())
			{
				/**
				 * Check if item has any sales rules applied
				 *
				 * @var array $rules
				 */
				$rules = array_filter
				(
					explode(chr(44), (string) $item->getAppliedRuleIds())
				);
				
				if ($rules)
				{
					if ('' !== $order->getCouponCode())
					{
						$data['coupon'] = $order->getCouponCode();
					}
					else
					{
						$coupon = array();
						
						foreach ($rules as $rule_id)
						{
							$rule = Mage::getModel('salesrule/rule')->load($rule_id);
							
							if ($rule && $rule->getId())
							{
								$coupon[] = $rule->getName();
							}
						}
						
						if ($coupon)
						{
							$data['coupon'] = join(chr(44), $coupon);
						}
					}
				}
			}
			
			/**
			 * Add custom dimensions to product
			 */
			foreach ($this->getDimensions($product, $order, Mage::getSingleton('customer/session')->getCustomer()) as $dimension => $value)
			{
				$data[$dimension] = $value;
			}
			
			$products[] = $data;
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
		
		/**
		 * Create transport object
		 *
		 * @var \Varien_Object
		 */
		$object = new Varien_Object
		(
			array
			(
				'products' 			=> $products,
				'google_tag_params' => $google_tag_params
			)
		);
		
		/**
		 * Notify others
		 */
		Mage::dispatchEvent('ec_order_products_get_after', array
		(
			'object' => $object
		));
		
		return (object) array
		(
			'products' 			=> $object->getProducts(),
			'google_tag_params' => $object->getGoogleTagParams()
		);
	}
	
	/**
	 * Get dimensions
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Sales_Model_Order $order
	 * @param string $customer
	 */
	public function getDimensions(Mage_Catalog_Model_Product $product = null,  Mage_Sales_Model_Order $order = null, $customer = null)
	{
		if (!(int)Mage::getStoreConfig('ec/definitions/dimensions'))
		{
			return array();
		}
		
		$dimensions = array();
		
		$model = Mage::getModel('ec/dimensions');
		
		foreach (range(1, 18) as $dimension)
		{
			$dimensions["dimension$dimension"] = $model->dispatch
			(
				Mage::getStoreConfig("ec/definitions/dimension$dimension"), $product, $order, $customer
				);
		}
		
		return array_filter($dimensions, function($value)
		{
			return !empty($value) || $value === 0;
		});
	}
	
	/**
	 * Prevent XSS attacks
	 *
	 * @param string $content
	 */
	public function getSanitized($content)
	{
		return strip_tags($content);
	}
	
	/**
	 * Determine page type
	 *
	 * @return string
	 */
	public function getPageType()
	{
		/**
		 * Add compatibility with Wordpress/Magento integrations
		 */
		if (function_exists('wp'))
		{
			return 'wordpress';
		}
		
		if (Mage::getBlockSingleton('page/html_header')->getIsHomePage())
		{
			return 'home';
		}
		else if('catalog' == Mage::app()->getRequest()->getModuleName() && 'category' == Mage::app()->getRequest()->getControllerName())
		{
			return 'category';
		}
		else if ('catalog' == Mage::app()->getRequest()->getModuleName() && 'product' == Mage::app()->getRequest()->getControllerName())
		{
			return 'product';
		}
		else if('checkout' == Mage::app()->getRequest()->getModuleName() && 'cart' == Mage::app()->getRequest()->getControllerName() && 'index' == Mage::app()->getRequest()->getActionName())
		{
			return 'cart';
		}
		else if('checkout' == Mage::app()->getRequest()->getModuleName() && 'onepage' == Mage::app()->getRequest()->getControllerName() && 'index' == Mage::app()->getRequest()->getActionName())
		{
			return 'checkout';
		}
		else if(Mage::app()->getRequest()->getControllerName() == 'result' || Mage::app()->getRequest()->getControllerName() == 'advanced')
		{
			return 'searchresults';
		}
		else
		{
			return 'other';
		}
	}
	
	/**
	 * Load customer orders
	 */
	protected function getOrders()
	{
		if (!$this->orders)
		{
			$this->orders = Mage::getResourceModel('sales/order_collection')->addFieldToSelect('*')->addFieldToFilter('customer_id',Mage::getSingleton("customer/session")->getId());
		}
		
		return $this->orders;
	}
	
	/**
	 * Check if GTM snippet is located after <body> opening tag
	 *
	 * @return boolean
	 */
	public function isAfterBody()
	{
		return true;
	}
	
	/**
	 * Check if GTM install snippet is located before </body> closing tag
	 *
	 * @return boolean
	 */
	public function isBeforeBodyClose()
	{
		return false;
	}
	
	/**
	 * Check if GTM install snippet is located inside <head></head> tag
	 *
	 * @return boolean
	 */
	public function isInsideHead()
	{
		return Anowave_Ec_Model_System_Config_Position::GTM_LOCATION_HEAD == (int) Mage::getStoreConfig('ec/config/code_position');
	}
	
	/**
	 * Escape string for JSON
	 *
	 * @see Mage_Core_Helper_Abstract::jsQuoteEscape()
	 */
	public function jsQuoteEscape($data, $quote='\'')
	{
		return Mage::helper('core')->jsQuoteEscape($data);
	}
	
	/**
	 * Escape quotes used in attribute(s)
	 *
	 * @param unknown $data
	 */
	public function jsQuoteEscapeDataAttribute($data)
	{
		return str_replace(array(chr(34), chr(39)),array('&quot;','&apos;'),$data);
	}
	
	/**
	 * Prepare GTM install snippet for <head> insertion
	 *
	 * @return string
	 */
	public function getHeadSnippet()
	{
		return Mage::getStoreConfig('ec/config/code_head');
	}
	
	public function getBodySnippet()
	{
		return Mage::getStoreConfig('ec/config/code_body');
	}
	
	/**
	 * Get list name
	 *
	 * @param Mage_Catalog_Model_Category $category
	 */
	public function getCategoryList(Mage_Catalog_Model_Category $category = null)
	{
		if(Mage::app()->getRequest()->getControllerName() == 'result' || Mage::app()->getRequest()->getControllerName() == 'advanced')
		{
			return Mage::helper('ec')->__('Search Results');
		}
		
		if ($category)
		{
			return trim
			(
				$category->getName()
				);
		}
		
		return Mage::helper('ec')->__('');
	}
	
	/**
	 * Get category name
	 *
	 * @param Mage_Catalog_Model_Category $category
	 */
	public function getCategory(Mage_Catalog_Model_Category $category)
	{
		if (Mage::getStoreConfig('ec/preferences/use_category_segments'))
		{
			return $this->getCategorySegments($category);
		}
		else
		{
			return trim
			(
				$category->getName()
				);
		}
	}
	
	/**
	 * Retrieve category and it's parents separated by chr(47)
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @return string
	 */
	public function getCategorySegments(Mage_Catalog_Model_Category $category)
	{
		$segments = array();
		
		foreach ($category->getParentCategories() as $parent)
		{
			$segments[] = $parent->getName();
		}
		
		if (!$segments)
		{
			$segments[] = $category->getName();
		}
		
		return trim(join(chr(47), $segments));
	}
	
	/**
	 * Get product manufacturer
	 *
	 * @param Mage_Catalog_Model_Product $product
	 */
	public function getBrand(Mage_Catalog_Model_Product $product)
	{
		try
		{
			if (array_key_exists($product->getId(), $this->brandMap))
			{
				return $this->brandMap[$product->getId()];
			}
			
			$code = trim
			(
				Mage::getStoreConfig('ec/preferences/brand')
				);
			
			$attributes = array();
			
			if ($code)
			{
				$attributes = array($code);
			}
			else
			{
				$attributes = array('brand','manufacturer');
			}
			
			foreach ($attributes as $code)
			{
				/**
				 * $attribute = Mage::getResourceModel('catalog/eav_attribute')->loadByCode(\Mage_Catalog_Model_Product::ENTITY,$code);
				 */
				
				$attribute = Mage::getSingleton('eav/config')->getAttribute(\Mage_Catalog_Model_Product::ENTITY, $code);
				
				if ($attribute->getId() && $attribute->usesSource())
				{
					$brand = (string) $product->getAttributeText($code);
					
					$this->brandMap[$product->getId()] = $brand;
					
					return $brand;
				}
			}
		}
		catch (Exception $e){}
		
		return '';
	}
	
	/**
	 * Load product by SKU and get its brand.
	 *
	 * @param string $sku
	 */
	public function getBrandBySku($identifier)
	{
		if ('' !== $identifier)
		{
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $identifier);
			
			if ($product && $product instanceof Mage_Catalog_Model_Product && $product->getId())
			{
				return $this->getBrand($product);
			}
		}
		
		return '';
	}
	
	/**
	 * Get option use field
	 *
	 * @return string
	 */
	public function getOptionUseField()
	{
		$field = (string) Mage::getStoreConfig('ec/preferences/use_custom_option_field');
		
		if ('' === $field)
		{
			$field = self::DEFAULT_CUSTOM_OPTION_FIELD;
		}
		
		return $field;
	}
	
	/**
	 * Get eventTimeout config value
	 *
	 * @return int
	 */
	public function getTimeoutValue()
	{
		$timeout = (int) Mage::getStoreConfig('ec/blocker/eventTimeout');
		
		if (!$timeout)
		{
			$timeout = 2000;
		}
		
		return $timeout;
	}
	
	/**
	 * Check if module should send child SKU instead of configurable parent SKU
	 *
	 * @return bool
	 */
	public function useConfigurableParent()
	{
		if (1 === (int) Mage::getStoreConfig('ec/preferences/use_child'))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Use configurable parent for ecomm_prodid[] array
	 * 
	 * @return boolean
	 */
	public function useConfigurableParentEcommProdid()
	{
		if (1 === (int ) Mage::getStoreConfig('ec/dynamic_remarketing/use_child'))
		{
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * Check if measurement protocol is active
	 *
	 * @return boolean
	 */
	public function useMeasurementProtocol()
	{
		return (1 === (int) Mage::getStoreConfig('ec/gmp/use_measurement_protocol'));
	}
	
	/**
	 * Check if measurement protocol is active and order cancel tracking is enabled
	 *
	 * @return boolean
	 */
	public function useMeasurementProtocolCancel()
	{
		return (1 === (int) Mage::getStoreConfig('ec/gmp/use_measurement_protocol_cancel'));
	}
	
	/**
	 * Get default identifiers
	 *
	 * @param unknown $item
	 */
	public function getDefaultProductIdentifiers(\Mage_Core_Model_Abstract $item)
	{
		$args = new \stdClass();
		
		if ($item->getProduct()->isConfigurable())
		{
			$options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
			
			/**
			 * Test order item
			 */
			if (!isset($options['simple_sku']) || !isset($options['simple_name']))
			{
				$options = $item->getProductOptions();
			}
			
			if (isset($options['simple_sku']) && isset($options['simple_name']))
			{
				$args->id 	= $options['simple_sku'];
				$args->name = $options['simple_name'];
				
				return $args;
			}
		}
		
		/**
		 * Default data
		 */
		$args->id 	= $item->getSku();
		$args->name = $item->getName();
		
		return $args;
	}
	
	/**
	 * Swap child product with it's parent name and SKU
	 *
	 * @param \stdClass $args
	 */
	public function getConfigurableProductIdentifiers(\stdClass $args, \Mage_Catalog_Model_Product $configurable)
	{
		if ($this->useConfigurableParent())
		{
			$args->id		= $configurable->getSku();
			$args->idParent = $configurable->getSku();
			$args->name 	= $configurable->getName();
		}
		
		return $args;
	}
	
	/**
	 * Get item price
	 *
	 * @param Mage_Sales_Model_Order_Item $item
	 * @param Mage_Sales_Model_Order $order
	 * @return number
	 */
	public function getPriceItem(Mage_Sales_Model_Order_Item $item,  Mage_Sales_Model_Order $order = null)
	{
		return 1 === (int) Mage::getStoreConfig('ec/revenue/tax') ? $this->getPriceItemInclTax($item, $order) : $this->getPriceItemExclTax($item, $order);
	}
	
	/**
	 * Get item price (excl. tax)
	 *
	 * @param Mage_Sales_Model_Order_Item $item
	 * @param Mage_Sales_Model_Order $order
	 */
	public function getPriceItemExclTax(Mage_Sales_Model_Order_Item $item,  Mage_Sales_Model_Order $order = null)
	{
		/**
		 * Get price incl.tax 
		 * 
		 * @var Ambiguous $price
		 */
		$price = $this->getPriceItemInclTax($item, $order);
		
		if ($item->getQtyOrdered() > 0)
		{
			$price = $item->getRowTotal()/$item->getQtyOrdered();
		}
		
		return (float) $price;
	}
	
	/**
	 * Get item price
	 *
	 * @param Mage_Sales_Model_Order_Item $item
	 * @param Mage_Sales_Model_Order $order
	 */
	public function getPriceItemInclTax(Mage_Sales_Model_Order_Item $item,  Mage_Sales_Model_Order $order = null)
	{
		if ((float) $item->getPriceInclTax() > 0)
		{
			return (float) $item->getPriceInclTax();
		}
		else
		{
			return (float) $item->getPrice();
		}
	}
	
	
	/**
	 * Get credit memo item price
	 *
	 * @param Mage_Sales_Model_Order_Item $item
	 * @param Mage_Sales_Model_Order $order
	 */
	public function getCreditMemoPriceItem(Mage_Sales_Model_Order_Creditmemo_Item $item,  Mage_Sales_Model_Order $order = null)
	{
		if ((float) $item->getPriceInclTax() > 0)
		{
			return (float) $item->getPriceInclTax();
		}
		else
		{
			return (float) $item->getPrice();
		}
	}
	
	
	/**
	 * Get order revenue with/without VAT depending on system configuration
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return number
	 */
	public function getRevenue(Mage_Sales_Model_Order $order)
	{
		$revenue = (float) $order->getGrandTotal();
		
		if (!Mage::getStoreConfig('ec/revenue/tax'))
		{
			$revenue -= (float) $order->getTaxAmount();
		}
		
		if (!Mage::getStoreConfig('ec/revenue/shipping'))
		{
			$revenue -= (float) $order->getShippingAmount();
		}
		
		return $revenue;
	}
	
	/**
	 * Get supper attributes
	 *
	 * @param void
	 * @return JSON
	 */
	public function getSuper()
	{
		$super = array();
		
		if (Mage::registry('current_product'))
		{
			$product = Mage::registry('current_product');
			
			if ($product->isConfigurable())
			{
				$attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
				
				if ($attributes)
				{
					foreach($attributes as $attribute)
					{
						$object = $attribute->getProductAttribute();
						
						/**
						 * Add Super Attribute
						 *
						 * @var []
						 */
						$super[] = array
						(
							'id' 	=> $object->getAttributeId(),
							'label' => $object->getFrontendLabel() //$object->getStoreLabel() to obtain store specific label
						);
					}
				}
			}
		}
		
		return Mage::helper('ec/json')->encode($super);
	}
	
	/**
	 * Get onclick binding type
	 *
	 * @return boolean
	 */
	public function useClickHandler()
	{
		return 0 === (int) Mage::getStoreConfig('ec/selectors/handler');
	}
	
	/**
	 * Get jQuery binding type
	 *
	 * @return boolean
	 */
	public function useOnHandler()
	{
		return !$this->useClickHandler();
	}
	
	/**
	 * Check whether event callback should be used
	 *
	 * @return number
	 */
	public function getEventCallback()
	{
		if ($this->useClickHandler())
		{
			return Mage::helper('ec/json')->encode(true);
		}
		
		return Mage::helper('ec/json')->encode(false);
	}
	
	/**
	 * Get Google Analytics Client Id
	 *
	 * return float
	 */
	public function getClientId()
	{
		if (null !== $client = Mage::getSingleton("customer/session")->getClientId())
		{
			return $client;
		}
		else
		{
			if ($_COOKIE && isset($_COOKIE['_ga']))
			{
				/**
				 * Get version, depth and client
				 */
				list($version, $depth, $client) = @explode(chr(46), $_COOKIE['_ga'], 3);
				
				/**
				 * Set client in session
				 */
				Mage::getSingleton("customer/session")->setClientId($client);
				
				return $client;
			}
			else
			{
				return Mage::helper('ec/json')->encode(null);
			}
		}
	}
	
	/**
	 * Additional dataLayer[] parameters
	 *
	 * @return JSON|NULL
	 */
	public function getGeneralPush()
	{
		$general = array();
		
		/**
		 * Create transport object
		 *
		 * @var \Varien_Object
		 */
		$object = new Varien_Object
		(
			array
			(
				'general' => $general
			)
			);
		
		Mage::dispatchEvent('ec_get_general_data', array
			(
				'object' => $object
			));
		
		$general = array_filter
		(
			$object->getGeneral()
			);
		
		if (is_array($general) && count($general))
		{
			return Mage::helper('ec/json')->encode
			(
				$general
				);
		}
		
		return null;
	}
	
	/**
	 * Get Facebook Advanced Matching Parameters
	 *
	 * @return array
	 */
	public function getFacebookAdvancedMatchingParams()
	{
		$params = array();
		
		/**
		 * Check if Facebook Advanced Matching is enabled
		 */
		if ($this->supportFacebookAdvancedMatching())
		{
			if (Mage::getSingleton("customer/session")->isLoggedIn())
			{
				$customer = Mage::getModel('customer/customer')->load
				(
					Mage::getSingleton("customer/session")->getCustomerId()
				);
				
				$params['em'] = $customer->getEmail();
				$params['fn'] = $customer->getFirstname();
				$params['ln'] = $customer->getLastname();
				$params['ge'] = $customer->getGender();
				$params['db'] = $customer->getDob();
			}
		}
		
		return Mage::helper('ec/json')->encode($params);
	}
	
	/**
	 * Extend visitor parameters
	 *
	 * @return JSON
	 */
	public function extendVisitor()
	{
		$visitor = array();
		
		/**
		 * Create transport object
		 *
		 * @var \Varien_Object
		 */
		$object = new Varien_Object
		(
			array
			(
				'visitor' => $visitor
			)
		);
		
		Mage::dispatchEvent('ec_get_visitor_data', array
		(
			'object' => $object
		));
		
		return Mage::helper('ec/json')->encode
		(
			$object->getVisitor()
		);
	}
	
	/**
	 * Smart viewport support
	 *
	 * @return boolean
	 */
	public function supportViewport()
	{
		return 1 === (int) Mage::getStoreConfig('ec/preferences/use_viewport');
	}
	
	/**
	 * Check for AMP support
	 *
	 * @return boolean
	 */
	public function supportsAmp()
	{
		return 1 === (int) Mage::getStoreConfig('ec/amp/enable');
	}
	
	/**
	 * Support for AdWords Dynamic Remarketing (Other site types)
	 *
	 * @return boolean
	 */
	public function supportsDynx()
	{
		return 1 === (int)  Mage::getStoreConfig('ec/dynamic_remarketing/dynx');
	}
	
	/**
	 * Support for Facebook Advanced Matching
	 *
	 * @return boolean
	 */
	public function supportFacebookAdvancedMatching()
	{
		return 1 === (int)  Mage::getStoreConfig('ec/facebook/enable_advanced_matching');
	}
}