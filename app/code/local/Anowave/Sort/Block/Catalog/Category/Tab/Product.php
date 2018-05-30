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

class Anowave_Sort_Block_Catalog_Category_Tab_Product extends Mage_Adminhtml_Block_Catalog_Category_Tab_Product
{
	public function __construct()
    {
        parent::__construct();

        $this->setDefaultSort('position');
        $this->setDefaultDir('ASC');
    }

    protected function _prepareCollection()
    {
    	if ($this->getCategory()->getId()) {
    		$this->setDefaultFilter(array('in_category'=>1));
    	}
    	$collection = Mage::getModel('catalog/product')->getCollection()
    	->addAttributeToSelect('name')
    	->addAttributeToSelect('sku')
    	->addAttributeToSelect('price')
    	->addStoreFilter($this->getRequest()->getParam('store'))
    	->joinField('position','catalog/category_product','position','product_id=entity_id','category_id='.(int) $this->getRequest()->getParam('id', 0),'left')
    	->joinField('qty','cataloginventory/stock_item','qty','product_id=entity_id','{{table}}.stock_id=1','left');

    	$this->setCollection($collection);

    	if ($this->getCategory()->getProductsReadonly()) {
    		$productIds = $this->_getSelectedProducts();
    		if (empty($productIds)) {
    			$productIds = 0;
    		}
    		$this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
    	}

    	return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

	protected function _prepareColumns()
	{
		parent::_prepareColumns();

		$this->addColumn('qty', array(
			'header'    => Mage::helper('sales')->__('Stock Level'),
			'width'     => '20',
			'type'      => 'number',
			'index'     => 'qty'
		));

		$this->addColumn('arrange', array
		(
			'header'    => Mage::helper('catalog')->__('Sort'),
			'width'     => '20px',
			'type'      => 'text',
			'align'		=> 'center',
			'index'     => 'arrange',
			'renderer'  => 'sort/drag'
        ));

		$this->addColumnAfter('image', array
		(
			'header'    => Mage::helper('catalog')->__('Image'),
			'width'     => '100px',
			'type'      => 'text',
			'align'		=> 'center',
			'renderer'  => 'sort/image'
		),'entity_id');
	}

	protected function _preparePage()
    {
    	if (!Mage::helper('sort')->isLimitApplied())
    	{
    		$this->getCollection()->setCurPage(1);
			$this->getCollection()->setPageSize(false);
    	}
    	else return parent::_preparePage();
    }

/* DHH
/* Needs to be disabled to prevent fatal error in Admin -> Manage categories
	public function getRowUrl()
	{
		return null;
	}
*/
}
