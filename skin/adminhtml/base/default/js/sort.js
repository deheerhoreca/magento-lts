/**
* Grid Sort
* 
* @copyright Anowave
* @author Angel Kostadinov
*/

jQuery.noConflict(); 

varienGrid = Class.create(varienGrid, 
{
	cache: [],
	initGrid: function ($super) 
	{
		$super();
		
		/* Sandbox jQuery code */
		(function($)
		{
			var grid = this, tbody = $('[id=catalog_category_products_table] tbody'), limit = $('div[id=catalog_category_products] select[name=limit]').val();
			
			/* Augment rows */
			if (tbody.length && 0 == limit)
			{
				try 
				{
					var sortable = tbody.sortable(
					{
						forcePlaceholderSizeType: false,
						handle: 'img.handle',
						helper: function(event, ui) 
						{
							ui.children().each(function() 
							{
								$(this).width($(this).width()).addClass('highlight');
							});
							
							return ui;
						},
						stop: function(event, ui)
						{
							ui.item.children().removeClass('highlight');
							
							for (var row = 0; row < grid.rows.length; row++) 
							{
				                if(row % 2==0)
				                {
				                    Element.addClassName(this.rows[row], 'even');
				                }
				                else 
				                {
				                	Element.removeClassName(this.rows[row], 'even');
				                }
							}
							
							sortable.children().each(function(index)
							{
								var element = $(this).find('input.input-text').get(0);
								
								element.value 					= 1 + index;
								element.checkboxElement.checked = true;
								
								/* Update products set */
								categoryProducts.set(element.checkboxElement.value, element.value);
								
								/* Update JS Object */
								catalog_category_productsJsObject.setCheckboxChecked(element.checkboxElement, true);
							});
							
							/* Set current checkbox as not checked */
							ui.item.find('input:checkbox').prop('checked', true);
							
							/* Serialize */
							$(':hidden[name=in_category_products]').val(categoryProducts.toQueryString());
							
							grid.reloadParams = 
							{
								'selected_products[]' : categoryProducts.keys()
							};
		
							return true;
						}
					});
				}
				catch (error)
				{
					alert(error);
				}
			}
			
		}).apply(this,[jQuery]);
	}
});