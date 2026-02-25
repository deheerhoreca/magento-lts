<?php

/**
 * OpenMage
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available at https://opensource.org/license/osl-3-0-php
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://www.magento.com)
 * @copyright  Copyright (c) 2021-2023 The OpenMage Contributors (https://www.openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class DeHeerHoreca_Util_Adminhtml_Block_Catalog_Product_Grid extends Mage_Adminhtml_Block_Catalog_Product_Grid
{
    protected function _prepareColumns()
    {
				
				
        $this->addColumn("image", array(
            "header"    => "",
            "align"     => "center",
            "index"     => "image",
            "width"     => "60px",
            "filter"    => false,
            "sort"      => false,
            "renderer"  => "DeHeerHoreca_Util_Block_Adminhtml_Template_Grid_Renderer_Image"
        )); 
				
				parent::_prepareColumns();
				
				$this->addColumn("product_type", [
						"header"  	=> "Type",
						"width"   	=> "100px",
						"type"    	=> "options",
						"index"   	=> "product_type",
						"options" 	=> Mage::getSingleton("firegento_gridcontrol/utility")->getDropdownAttributeLabelOptionArray("product_type"),
				]);
				
        return $this;
    }
    
    // Keep in sync with Mage_Adminhtml_Block_Catalog_Product_Grid::_prepareCollection()
    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
            ->addAttributeToSelect('brand_series')
            ->addAttributeToSelect('product_type')
        ;
        
        // DHH: Not needed
        // if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            // $collection->joinField('qty',
                // 'cataloginventory/stock_item',
                // 'qty',
                // 'product_id=entity_id',
                // '{{table}}.stock_id=1',
                // 'left');
        // }
        if ($store->getId()) {
            //$collection->setStoreId($store->getId());
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            $collection->addStoreFilter($store);
            $collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $adminStore
            );
            // $collection->joinAttribute(
            //     'custom_name',
            //     'catalog_product/name',
            //     'entity_id',
            //     null,
            //     'inner',
            //     $store->getId()
            // );
            $collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'price',
                'catalog_product/price',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
            $collection->joinAttribute(
								'product_type',
								'catalog_product/product_type',
								'entity_id',
								null,
								'inner'
						);
        } else {
            $collection->addAttributeToSelect('price');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }
        
        $collection->joinAttribute('image', 'catalog_product/image', 'entity_id', null, 'left');
				
        $this->setCollection($collection);
				
				// DHH: Calling the parent will remove all our custom collection modifications, so we skip it and call the grandparent directly.
        // parent::_prepareCollection();
				
        Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
        // $this->getCollection()->addWebsiteNamesToResult();
        return $this;
    }
}
