<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */  
class Amasty_Shopby_Block_Adminhtml_Filter_Edit_Tab_Values extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('valuesGrid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setDefaultSort('option_id');
        $this->setDefaultDir('ASC');
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->unsetChild('search_button');
        $this->unsetChild('reset_filter_button');
        return $this;
    }

    protected function _prepareCollection()
    {
        $values = $this->_initCollection();
        $this->setCollection($values);
        return parent::_prepareCollection();
    }

    protected function _initCollection()
    {
        return Mage::getModel('amshopby/value')->getCollection()
            ->addFieldToFilter('filter_id', Mage::registry('amshopby_filter')->getId());
    }

    protected function addIdColumn()
    {
        $this->addColumn('option_id', array(
            'header'    => Mage::helper('amshopby')->__('ID'),
            'index'     => 'option_id',
            'width'     => '50px',
            'filter' => false,
        ));
    }

    protected function _prepareColumns()
    {
        $hlp = Mage::helper('amshopby');

        $this->addIdColumn();
       
        $this->addColumn('title', array(
            'header'    => $hlp->__('Title'),
            'index'     => 'title',
            'getter'    => 'getCurrentTitle',
            'sortable'    => false,
        ));

        $this->addColumn('url_alias', array(
            'header'    => $hlp->__('URL alias'),
            'index'     => 'url_alias',
            'getter'    => 'getCurrentUrlAlias',
            'sortable'    => false,
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/amshopby_value/edit', array('id' => $row->getValueId()));
    }
}
