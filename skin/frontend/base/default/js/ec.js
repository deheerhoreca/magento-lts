/**
 * Anowave Enhanced Ecommerce Tracking
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
 * @copyright 	Copyright (c) 2018 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

if ('undefined' === typeof log)
{
	var log = function (message) 
	{
	   	window.console && console.log ? console.log(message) : null;
	};
}

var AEC = 
{
	textarea:null,
	gtm: function()
	{
		if ("undefined" === typeof google_tag_manager)
		{
			/**
			 * Log error to console
			 */
			log('Unable to detect Google Tag Manager. Please verify if GTM install snippet is available.');
			
			return false;
		}

		return true;
	},
	ajax: function(context,dataLayer)
	{
		var element = jQuery(context), qty = jQuery(':radio[name=qty]:checked, :text[name=qty], select[name=qty]').eq(0).val(), variant = [];

		/**
		 * Default quantity
		 */
		if ('undefined' === typeof qty)
		{
			qty = 1;
		}
		
		/**
		 * Collection of products added to cart
		 */
		var products = [];
		
		if (AEC.forceSelectors)
		{
			jQuery.each(['id','name','category','brand','price','quantity', 'attributes'], function(index, attribute)
			{
				element.data(attribute, element.attr('data-' + attribute));
			});
		}
		
		var attributes = {};
		
		if (!AEC.gtm())
		{
			/**
			 * Invoke original click event(s)
			 */
			if (element.data('click'))
			{
				/**
				 * Track time 
				 */
				AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
				
				eval(element.data('click'));
			}
			
			return true;
		}
		
		if(element.data('configurable'))
		{
			var attributes = jQuery('[name^="super_attribute"]'), variants = [];

			/**
			 * Load super attsibutes
			 */

			jQuery.each(attributes, function(index, attribute)
			{
				if (jQuery(attribute).is('select'))
				{
					var name = jQuery(attribute).attr('name'), id = name.substring(name.indexOf('[') + 1, name.lastIndexOf(']'));

					var option = jQuery(attribute).find('option:selected');

					if (0 < parseInt(option.val()))
					{
						variants.push(
						{
							id: 	id,
							text: 	option.text()
						});
					}
				}
			});

			if (attributes.length == variants.length)
			{
				for (i = 0, l = variants.length; i < l; i++)
				{
					for (a = 0, b = AEC.SUPER.length; a < b; a++)
					{
						if (AEC.SUPER[a].id == variants[i].id)
						{
							variant.push([AEC.SUPER[a].label,variants[i].text].join(':'));
						}
					}
				}
			}

			if (!variant.length)
			{
				/**
				 * Invoke original click event(s)
				 */
				if (element.data('click'))
				{
					/**
					 * Track time 
					 */
					AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
					
					eval(element.data('click'));
				}
				
				return false;
			}
		}

		/**
		 * Handle products with custom options
		 */
		if (element.data('options'))
		{
			variant = variant || [], options = element.data('options');
			
			var variant = (function($, variant)
			{
				var options = element.data('options'), current = [];

				$('[name^="options"]').each(function()
				{
					$(this).find('option:selected').each(function()
					{
						current.push(parseInt($(this).attr('value')));
					});
				});

				var currentOptions = [];

				for (i = 0, l = current.length; i < l; i++)
				{
					$.each(options, function(index, option)
					{
						if (parseInt(option.id) == parseInt(current[i]))
						{
							currentOptions.push([option.label.toString().trim(),option.value.toString().trim()].join(':'));
						}
					});
				}

				variant.push(currentOptions.join('-'));

				/**
				 * Return variant
				 */
				return variant;
				
			})(jQuery, variant);
		}

		/**
		 * Handle grouped elements
		 */
		if (element.data('grouped'))
		{
			for (u = 0, y = window.G.length; u < y; u++)
			{
				var qty = Math.abs(jQuery('[name="super_group[' + window.G[u].id + ']"]').val());

				if (qty)
				{
					products.push(
					{
						'name': 		window.G[u].name,
						'id': 		    window.G[u].sku,
						'price': 		window.G[u].price,
						'category': 	window.G[u].category,
						'brand':		window.G[u].brand,
						'quantity': 	qty
					});
				}
			}
		}
		else
		{
			products.push(
			{
				'name': 		AEC.convert(element.data('name')),
				'id': 		    AEC.convert(element.data('id')),
				'price': 		AEC.convert(element.data('price')),
				'category': 	AEC.convert(element.data('category')),
				'brand':		AEC.convert(element.data('brand')),
				'variant':		variant.join('-'),
				'quantity': 	qty
			});
		}
		
		/**
		 * Affiliation attributes
		 */
		for (i = 0, l = products.length; i < l; i++)
		{
			(function(product)
			{
				jQuery.each(AEC.parseJSON(element.data('attributes')), function(key, value)
				{
					product[key] = value;
				});
			})(products[i]);
		}

		var data = 
		{
			'event': 'addToCart',
			'eventLabel': element.data('name'),
			'ecommerce': 
			{
				'currencyCode': AEC.currencyCode,
				'add': 
				{
					'products': products
				}
			},
			'eventCallback': function() 
			{
				if (AEC.eventCallback)
				{
					if (element.data('click'))
					{
						eval(element.data('click'));
					}
					else if (element.is('a'))
					{
						document.location = element.attr('href');
					}
					else if (element.is('img') && element.parent().is('a'))
					{
						document.location = element.parent().attr('href');
					}
					else 
					{
						return true;
					}
				}
	     	},
	     	'eventTimeout': AEC.eventTimeout
		};
		
		/**
		 * Cookie Consent
		 */
		(function(callback, element)
		{
			if (AEC.CookieConsent.support)
			{
				if (AEC.CookieConsent.granted)
				{
					AEC.CookieConsent.queue(callback).process();
				}
				else 
				{
					if (AEC.eventCallback)
					{
						if (element.data('click'))
						{
							eval(element.data('click'));
						}
						else if (element.is('a'))
						{
							document.location = element.attr('href');
						}
						else if (element.is('img') && element.parent().is('a'))
						{
							document.location = element.parent().attr('href');
						}
						else 
						{
							return true;
						}
					}
				}
			}
			else 
			{
				return callback.apply(window,[]);
			}
			
		})((function(dataLayer,data, element)
		{
			return function()
			{
				dataLayer.push(data);
				
				/**
				 * Track time 
				 */
				AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
			}
		})(dataLayer,data, element), element);

		/**
		 * Facebook Pixel Tracking
		 */
		if (AEC.facebook)
		{
			if ("undefined" !== typeof fbq)
			{
				var fb = [], price = 0;
	
				for (i = 0, l = products.length; i < l; i++)
				{
					fb.push(products[i].id);

					/**
					 * Accumulative price
					 */
					price += parseFloat(products[i].price);
				}

				(function(callback)
				{
					if (AEC.CookieConsent.support)
					{
						AEC.CookieConsent.queue(callback).process();
					}
					else 
					{
						callback.apply(window,[]);
					}
					
				})((function(price, element)
				{
					return function()
					{
						fbq('track', 'AddToCart', 
						{
							content_name: 	element.data('name'),
							content_ids: 	fb,
							content_type: 	'product',
							value: 			price,
							currency: 		AEC.currencyCode
						});
					}
				})(price, element));
			}
		}
		
		if (AEC.eventCallback)
		{
			return false;
		}

		return true;
	},
	click: function(context,dataLayer)
	{
		var element = jQuery(context);

		if (AEC.forceSelectors)
		{
			jQuery.each(['id','name','category','brand','price','quantity','position','attributes'], function(index, attribute)
			{
				element.data(attribute, element.attr('data-' + attribute));
			});
		}

		if (!AEC.gtm())
		{
			/**
			 * Track time 
			 */
			AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_PRODUCT_CLICK, element.data('name'), element.data('category'));
			
			return true;
		}
		
		var item = 
		{
			'name': 		AEC.convert(element.data('name')),
			'id': 			AEC.convert(element.data('id')),
			'price': 		AEC.convert(element.data('price')),
			'category': 	AEC.convert(element.data('category')),
			'brand':		AEC.convert(element.data('brand')),
			'quantity': 	AEC.convert(element.data('quantity')),
			'position':		AEC.convert(element.data('position'))
		};
		
		/**
		 * Affiliation attributes
		 */
		jQuery.each(AEC.parseJSON(element.data('attributes')), function(key, value)
		{
			item[key] = value;
		});

		var data = 
		{
			'event': 'productClick',
			'eventLabel': AEC.convert(element.data('name')),
			'ecommerce': 
			{
				'click': 
				{
					'actionField': 
					{
						'list': AEC.convert(element.data('list'))
					},
					'products': 
					[
						item
					]
				}
			},
			'eventCallback': function() 
			{
				if (AEC.eventCallback)
				{
					if (element.data('click'))
					{
						eval(element.data('click'));
					}
					else if (element.is('a'))
					{
						document.location = element.attr('href');
					}
					else if (element.is('img') && element.parent().is('a'))
					{
						document.location = element.parent().attr('href');
					}
					else 
					{
						return true;
					}
				}
	     	},
	     	'eventTimeout': AEC.eventTimeout, 
	     	'eventTarget': (function(element)
	     	{
	     		/**
	     		 * Default target
	     		 */
	     		var target = 'Default';
	     		
	     		/**
	     		 * Check if element is anchor
	     		 */
	     		if (element.is('a'))
	     		{
	     			target = 'Link';
	     			
	     			if (element.children().first().is('img'))
	     			{
	     				target = 'Image';
	     			}
	     		}
	     		
	     		if (element.is('button'))
	     		{
	     			target = 'Button';
	     		}
	     		
	     		return target;
	     		
	     	})(element)
		};

		/**
		 * Cookie Consent
		 */
		(function(callback, element)
		{
			if (AEC.CookieConsent.support)
			{
				if (AEC.CookieConsent.granted)
				{
					AEC.CookieConsent.queue(callback).process();
				}
				else 
				{
					if (AEC.eventCallback)
					{
						if (element.data('click'))
						{
							eval(element.data('click'));
						}
						else if (element.is('a'))
						{
							document.location = element.attr('href');
						}
						else if (element.is('img') && element.parent().is('a'))
						{
							document.location = element.parent().attr('href');
						}
						else 
						{
							return true;
						}
					}
				}
			}
			else 
			{
				return callback.apply(window,[]);
			}
			
		})((function(dataLayer,data, element)
		{
			return function()
			{
				dataLayer.push(data);
				
				/**
				 * Track time 
				 */
				AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_PRODUCT_CLICK, element.data('name'), element.data('category'));
			}
		})(dataLayer,data, element), element);	

		if (AEC.eventCallback)
		{
			return false;
		}
		
		return true;
	},
	ajaxList:function(context,dataLayer)
	{
		var element = jQuery(context);
		
		/**
		 * Collection of products added to cart
		 */
		var products = [];

		if (AEC.forceSelectors)
		{
			jQuery.each(['id','name','category','brand','price','quantity'], function(index, attribute)
			{
				element.data(attribute, element.attr('data-' + attribute));
			});
		}

		if (!AEC.gtm())
		{
			if (AEC.eventCallback)
			{
				/**
				 * Invoke original click event(s)
				 */
				if (element.data('click'))
				{
					/**
					 * Track time 
					 */
					AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
					
					eval(element.data('click'));
				}
			}
			
			return true;
		}

		products.push(
		{
			'name': 		AEC.convert(element.data('name')),
			'id': 		    AEC.convert(element.data('id')),
			'price': 		AEC.convert(element.data('price')),
			'category': 	AEC.convert(element.data('category')),
			'brand':		AEC.convert(element.data('brand')),
			'quantity': 	1
		});
		
		/**
		 * Affiliation attributes
		 */
		for (i = 0, l = products.length; i < l; i++)
		{
			(function(product)
			{
				jQuery.each(AEC.parseJSON(element.data('attributes')), function(key, value)
				{
					product[key] = value;
				});
			})(products[i]);
		}

		var data = 
		{
			'event': 'addToCart',
			'eventLabel': element.data('name'),
			'ecommerce': 
			{
				'currencyCode': AEC.currencyCode,
				'add': 
				{
					'actionField':
					{
						'list':AEC.convert(element.data('list'))
					},
					'products': products
				}
			}
		};

		/**
		 * Cookie Consent
		 */
		(function(callback, element)
		{
			if (AEC.CookieConsent.support)
			{
				if (AEC.CookieConsent.granted)
				{
					AEC.CookieConsent.queue(callback).process();
				}
				else 
				{
					if (element.data('click'))
					{
						/**
						 * Track time 
						 */
						AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
						
						/**
						 * Evaluate original click
						 */
						eval(element.data('click'));
					}
				}
			}
			else 
			{
				return callback.apply(window,[]);
			}
			
		})((function(dataLayer,data, element)
		{
			return function()
			{
				dataLayer.push(data);
				
				/**
				 * Track time 
				 */
				AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
			}
		})(dataLayer,data, element), element);	
		
		/**
		 * Invoke original click event(s)
		 */
		if (AEC.eventCallback)
		{
			if (element.data('click'))
			{
				eval(element.data('click'));
			}
		}

		if (AEC.facebook)
		{
			if ("undefined" !== typeof fbq)
			{
				var fb = [], price = 0;
	
				for (i = 0, l = products.length; i < l; i++)
				{
					fb.push(products[i].id);

					/**
					 * Accumulative price
					 */
					price += parseFloat(products[i].price);
				}

				(function(callback)
				{
					if (AEC.CookieConsent.support)
					{
						AEC.CookieConsent.queue(callback).process();
					}
					else 
					{
						callback.apply(window,[]);
					}
					
				})((function(price, element)
				{
					return function()
					{
						fbq('track', 'AddToCart', 
						{
							content_name: 	element.data('name'),
							content_ids: 	fb,
							content_type: 	'product',
							value: 			price,
							currency: 		AEC.currencyCode
						});
					}
				})(price, element));
			}
		}
		
		return true;
	},
	remove: function(context, dataLayer)
	{
		var element = jQuery(context);

		if (AEC.forceSelectors)
		{
			jQuery.each(['id','name','category','brand','price','quantity'], function(index, attribute)
			{
				element.data(attribute, element.attr('data-' + attribute));
			});
		}
		
		if (!AEC.gtm())
		{
			/**
			 * Track time 
			 */
			AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_REMOVE_FROM_CART, element.data('name'), element.data('category'));
			
			return true;
		}
		
		var item = 
		{
			'name': 		AEC.convert(element.data('name')),
			'id': 			AEC.convert(element.data('id')),
			'price': 		AEC.convert(element.data('price')),
			'category': 	AEC.convert(element.data('category')),
			'brand':		AEC.convert(element.data('brand')),
			'quantity': 	AEC.convert(element.data('quantity')),
			'variant':		AEC.convert(element.data('variant'))
		};
		
		/**
		 * Affiliation attributes
		 */
		jQuery.each(AEC.parseJSON(element.data('attributes')), function(key, value)
		{
			item[key] = value;
		});

		var data = 
		{
			'event': 'removeFromCart',
			'eventLabel': AEC.convert(element.data('name')),
			'ecommerce': 
			{
				'remove': 
				{   
					'products': 
					[
						item
					]
				}
			},
			'eventCallback': function() 
			{
				if (AEC.eventCallback)
				{
					if (element.data('click'))
					{
						eval(element.data('click'));
					}
					else if (element.is('a'))
					{
						document.location = element.attr('href');
					}
					else if (element.is('img') && element.parent().is('a'))
					{
						document.location = element.parent().attr('href');
					}
					else 
					{
						return true;
					}
				}
	     	},
	     	'eventTimeout': AEC.eventTimeout
		};

		if (element.data('mini-cart'))
		{
			if ('undefined' !== typeof Minicart)
			{
				(function(dataLayer, data, Minicart, element, stop)
				{
					Minicart.prototype.updateContentOnRemove = Minicart.prototype.updateContentOnRemove.wrap(function(parentMethod, result, el)
					{
						parentMethod(result, el);

						if (!stop)
						{
							stop = true;

							if ('undefined' != typeof result.success)
							{
								data['eventCallback'] 	= function(){};

								/**
								 * Fallback for private mode
								 */
								data['eventTimeout'] = AEC.eventTimeout;

								/**
								 * Push data
								 */
								dataLayer.push(data);
	
								/**
								 * Track time 
								 */
								AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_REMOVE_FROM_CART, element.data('name'));
							}
						}
					});
				})(dataLayer, data, Minicart, element, false);
			}
		}
		else 
		{
			/**
			 * Track event
			 */
			dataLayer.push(data);

			/**
			 * Track time 
			 */
			AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_REMOVE_FROM_CART, element.data('name'));
		}
		
		if (AEC.eventCallback)
		{
			return false;
		}
		
		return true;
	},
	wishlist: function(context,dataLayer)
	{
		var element = jQuery(context);
		
		if (AEC.forceSelectors)
		{
			jQuery.each(['id','name','category','brand','price','quantity'], function(index, attribute)
			{
				element.data(attribute, element.attr('data-' + attribute));
			});
		}
		
		if (!AEC.gtm())
		{
			/**
			 * Track time 
			 */
			AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_PRODUCT_WISHLIST, element.data('name'), element.data('category'));
			
			return true;
		}

		var data = 
		{
			'event': 'addToWishlist',
			'eventLabel': AEC.convert(element.data('name')),
			'eventCallback': function() 
			{
				if (AEC.eventCallback)
				{
					if (element.data('click'))
					{
						eval(element.data('click'));
					}
					else if (element.is('a'))
					{
						document.location = element.attr('href');
					}
					else if (element.is('img') && element.parent().is('a'))
					{
						document.location = element.parent().attr('href');
					}
					else 
					{
						return true;
					}
				}
	     	},
	     	'eventTimeout': AEC.eventTimeout
		};

		/**
		 * Push data
		 */
		dataLayer.push(data);

		/**
		 * Track time 
		 */
		AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_PRODUCT_WISHLIST, element.data('name'), element.data('category'));

		if (AEC.eventCallback)
		{
			return false;
		}
		
		return true;
	},
	compare: function(context,dataLayer)
	{
		var element = jQuery(context);
		
		if (AEC.forceSelectors)
		{
			jQuery.each(['id','name','category','brand','price','quantity'], function(index, attribute)
			{
				element.data(attribute, element.attr('data-' + attribute));
			});
		}
		
		if (!AEC.gtm())
		{
			/**
			 * Track time 
			 */
			AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_PRODUCT_COMPARE, element.data('name'), element.data('category'));
			
			return true;
		}

		var data = 
		{
			'event': 'addToCompare',
			'eventLabel': AEC.convert(element.data('name')),
			'eventCallback': function() 
			{
				if (AEC.eventCallback)
				{
					if (element.data('click'))
					{
						eval(element.data('click'));
					}
					else if (element.is('a'))
					{
						document.location = element.attr('href');
					}
					else if (element.is('img') && element.parent().is('a'))
					{
						document.location = element.parent().attr('href');
					}
					else 
					{
						return true;
					}
				}
	     	},
	     	'eventTimeout': AEC.eventTimeout
		};

		/**
		 * Push data
		 */
		dataLayer.push(data);

		/**
		 * Track time 
		 */
		AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_PRODUCT_COMPARE, element.data('name'), element.data('category'));

		if (AEC.eventCallback)
		{
			return false;
		}
		
		return true;
	},
	Callbacks:(function()
	{
		return {
			callbacks:[],
			queue: function(callback)
			{
				this.callbacks.push(callback);

				return this;
			},
			apply: function()
			{
				while (this.callbacks.length)
				{
					this.callbacks.shift().apply(AEC,arguments);
				}

				return this;
			}
		}
	})(),
	Time:(function()
	{
		var T = 
		{
			event: 			'trackTime',
			timingCategory:	'',
			timingVar:		'',
			timingValue:	-1,
			timingLabel:	''
		};

		var time = new Date().getTime();
		
		return {
			track: function(dataLayer, category, variable, label)
			{
				T.timingValue = (new Date().getTime()) - time;
				
				if (category)
				{
					T.timingCategory = category;
				}

				if (variable)
				{
					T.timingVar = variable;
				}

				if (label)
				{
					T.timingLabel = label;
				}
				
				/**
				 * Track time
				 */
				dataLayer.push(T);
			},
			trackContinue: function(dataLayer, category, variable, label)
			{
				this.track(dataLayer, category, variable, label);

				/**
				 * Reset time
				 */
				time = new Date().getTime();
			}
		}
	})(),
	Queue: (function()
	{
		return {
			queue: [],
			impressions: function(data)
			{
				if (AEC.Const.Viewport)
				{
					jQuery(window).on(
					{
						load: function()
						{
							AEC.Queue.bind(data);
						}
					});
				}
				else
				{
					dataLayer.push(data);
				}
				
				return this;
			},
			bind: function(data)
			{
				(function(queue, impressions)
				{
					jQuery.each(impressions, function(index, impression)
					{
						queue.push(impression);
					});

				})(this.queue, data.ecommerce.impressions);
	
				if (this.queue.length)
				{
					(function(Queue, $)
					{
						var scroll = function()
						{
							var items = 
							{
								noticeable: $.grep(Queue.queue, function(element, index)
								{ 
									return Queue.isOnScreen($('[data-id="' + element.id + '"][data-event=productClick]'));
								}),
								concealed: $.grep(Queue.queue, function(element, index)
								{ 
									return !Queue.isOnScreen($('[data-id="' + element.id + '"][data-event=productClick]'));
								})
							};
							
							if (items.noticeable.length)
							{
								/**
								 * Set event
								 */
								data.event = 'impression';
								
								/**
								 * Update impressions event
								 */
								data.ecommerce.impressions 	= items.noticeable;
								
								/**
								 * Push item to dataLayer
								 */
								dataLayer.push(data);
							}

							if (items.concealed.length)
							{
								Queue.queue = items.concealed;
							}
							else 
							{
								/**
								 * Empty queue
								 */
								Queue.queue = [];
								
								/**
								 * Remove scroll listener
								 */
								$(window).off('scroll.ec');
							}
						};

						$(window).on('scroll.ec',scroll).trigger('scroll');
						
					})(this, jQuery);
				}
			},
			isOnScreen: function(element)
			{
				/**
				 * Default viewport
				 */
				var viewport = 
				{
					top: 0, bottom: 0
				};
				
				/**
				 * Check if element exists
				 */
				if ('undefined' === typeof element)
				{
					return false;
				}
				
				/**
				 * Get top
				 */
			    viewport.top = jQuery(window).scrollTop();
			    
			    /**
			     * Get bottom
			     */
			    viewport.bottom = viewport.top + jQuery(window).height();
			    
			    /**
			     * Get bounds
			     */
			    var bounds = {}, offset = element.offset();
			    
			    if ('undefined' === typeof offset)
			    {
			    	return false;
			    }
			    
			    if (offset == null)
			    {
			    	return false;
			    }

			    if (!offset.hasOwnProperty('top'))
			    {
			    	return false;
			    }

			    /**
			     * Get bounds top
			     */
			    bounds.top = element.offset().top;
 
			    /**
			     * Get bounds bottom
			     */
			    bounds.bottom = bounds.top + element.outerHeight();
			    
			    return ((bounds.top <= viewport.bottom) && (bounds.bottom >= viewport.top));
			}
		}
	})(),
	Cookie: (function() //This is an experimental feature to overcome FPC (Full Page Cache) related issues (beta)
	{
		return {
			data: null,
			privateData: null,
			push: function(dataLayer)
			{
				if (this.data)
				{
					/**
					 * Push data
					 */
					dataLayer.push(this.data);

					/**
					 * Reset data to prevent further push
					 */
					this.data = null;
				}
				
				return this;
			},
			pushPrivate: function()
			{
				var data = this.getPrivateData();
				
				if (data)
				{
					dataLayer.push(
					{
						privateData: data
					});
				}
				
				return this;
			},
			visitor: function(data)
			{
				/**
				 * Set data
				 */
				this.data = data;
				
				return this;
			},
			detail: function(data)
			{
				this.data = data;
				
				return this;
			},
			purchase: function(data)
			{
				this.data = data;
				
				return this;
			},
			impressions: function(data)
			{
				this.data = data;
				
				return this;
			},
			checkout: function(data)
			{
				this.data = data;
				
				return this;
			},
			promotion: function(data)
			{
				this.data = data;
				
				return this;
			},
			promotionClick: function(data)
			{
				this.data = data;
				
				return this;
			},
			getPrivateData: function()
			{
				if (!this.privateData)
				{
					var cookie = this.get('privateData');
					
					if (cookie)
					{
						this.privateData = this.parse(cookie);
					}
				}
				
				return this.privateData;
			},
			get: function(name)
			{
				var start = document.cookie.indexOf(name + "="), len = start + name.length + 1;
				
				if ((!start) && (name != document.cookie.substring(0, name.length))) 
				{
				    return null;
				}
				
				if (start == -1) 
				{
					return null;
				}
									
				var end = document.cookie.indexOf(String.fromCharCode(59), len);
									
				if (end == -1) 
				{
					end = document.cookie.length;
				}
				
				return decodeURIComponent(document.cookie.substring(len, end));
			},
			remove: function(name) 
			{   
                document.cookie = name + "=" + "; path=/; expires=" + (new Date(0)).toUTCString();
                
                return this;
            },
			parse: function(json)
			{
				var json = decodeURIComponent(json.replace(/\+/g, ' '));
				
                return JSON.parse(json);
			},
			Storage: (function(cookie) //@todo: Replace Cookie with localStorage
			{
				return {
					set: function(property, value)
					{
						if ('undefined' !== typeof(Storage))
						{
							localStorage.setItem(property, JSON.stringify(value));
						}
						
						return this;
						
					},
					get: function(property)
					{
						if ('undefined' !== typeof(Storage))
						{
							return JSON.parse(localStorage.getItem(property));
						}
						
						return null;
					}
				}
			})(this)
		}
	})(),
	CookieConsent: (function()
	{
		return {
			support: false,
			granted: false,
			chain: [],
			queue: function(callback)
			{
				this.chain.push(callback);
				
				return this;
			},
			dispatch: function(consent)
			{
				this.granted = true;
				
				dataLayer.push(consent);
				
				return this.process();
			},
			process: function()
			{
				if (this.granted)
				{
					for (i = 0, l = this.chain.length; i < l; i++)
					{
						this.chain[i].apply(this,[]);
					}
					
					/**
					 * Reset chain
					 */
					this.chain = [];
				}
				
				return this;
			}
		}
	})(),
	convert: function(content)
	{
		if ('undefined' !== typeof content && '' !== content && -1 !== content.toString().indexOf('&'))
		{
			if (null == this.textarea)
			{
				this.textarea = jQuery('<textarea/>');
			}
			
			return this.textarea.html(content).text();
		}

		return content;
	},
	getClientId: function()
	{
		if ('undefined' !== typeof ga)
		{
			return ga.getAll()[0].get('clientId');
		}
	},
	parseJSON: function(content)
	{
		if ('object' === typeof content)
		{
			return content;
		}
		
		if ('string' === typeof content)
		{
			try 
			{
				return JSON.parse(content);
			}
			catch (e){}
		}
		
		return {};
	}
};

var GOOGLE_PAYLOAD_SIZE = 8192;

/**
 * Calculate payload size (approx.)
 *  
 * @return int bytes
 */
var getPayloadSize = function(object) 
{
    var objects = [object];
    var size = 0;

    for (var index = 0; index < objects.length; index++) 
    {
        switch (typeof objects[index]) 
        {
            case 'boolean':
                size += 4;
                break;
            case 'number':
                size += 8;
                break;
            case 'string':
                size += 2 * objects[index].length;
                break;
            case 'object':
                if (Object.prototype.toString.call(objects[index]) != '[object Array]') 
                {
                    for (var key in objects[index]) size += 2 * key.length;
                }
                for (var key in objects[index]) 
                {
                    var processed = false;
                    
                    for (var search = 0; search < objects.length; search++) 
                    {
                        if (objects[search] === objects[index][key]) {
                            processed = true;
                            break;
                        }
                    }
                    if (!processed) objects.push(objects[index][key]);
                }
        }
    }
    return size;
};

/**
 * Chunk payload
 */
var getPayloadChunks = function(arr, len) 
{
	var chunks = [],i = 0, n = arr.length;

	while (i < n) 
	{
	    chunks.push(arr.slice(i, i += len));
	}

	return chunks;
};