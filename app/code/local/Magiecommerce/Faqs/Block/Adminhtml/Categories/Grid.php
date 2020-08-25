<?php

class Magiecommerce_Faqs_Block_Adminhtml_Categories_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('cat_id');
        $this->setDefaultSort('cat_sortorder');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
       $collection = Mage::getModel('faqs/Categories')->getCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();
        foreach ($collection as $link) {
            if ($link->getStoreId() && $link->getStoreId() != 0) {
                $link->setStoreId(explode(',', $link->getStoreId()));
            } else {
                $link->setStoreId(array('0'));
            }
        }
        return $this;
    }

    protected function _prepareColumns() {
        $this->addColumn('cat_id', array(
            'header' => 'ID',
            'align' => 'right',
            'width' => '50px',
            'index' => 'cat_id',
        ));
        $this->addColumn('cat_name', array(
            'header' => 'Category Name',
            'align' => 'left',
            'index' => 'cat_name',
        ));


$this->addColumn('parentcatid', array(
            'header' => 'Parent Category Name',
            'align' => 'left',
            'index' => 'parentcatid',
            'renderer'  => 'faqs/adminhtml_categories_renderer_parentname'
        ));


        $this->addColumn('description', array(
            'header' => 'Description',
            'align' => 'left',
            'index' => 'description',
        ));
       if ( !Mage::app()->isSingleStoreMode() ) {
    $this->addColumn('store_id', array(
        'header' => Mage::helper('faqs')->__('Store View'),
        'index' => 'store_id',
        'type' => 'store',
        'store_all' => true,
        'store_view' => true,
        'sortable' => true,
        'filter_condition_callback' => array($this, '_filterStoreCondition'),
    ));
}
        $this->addColumn('cat_sortorder', array(
            'header' => 'Sort Order',
            'align' => 'right',
            'index' => 'cat_sortorder',
        ));
        $this->addColumn('status', array(
            'header' => 'Status',
            'align' => 'left',
            'index' => 'status',
            'type' => 'options',
            'options' => array(
                1 => Mage::helper('faqs')->__('Enabled'),
                2 => Mage::helper('faqs')->__('Disabled'),
            ),
        ));
        $this->addColumn('action', array(
            'header' => Mage::helper('faqs')->__('Action'),
            'width' => '50px',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('faqs')->__('Edit'),
                    'url' => array(
                        'base' => '*/*/edit',
                        'params' => array('store' => $this->getRequest()->getParam('store'))
                    ),
                    'field' => 'id'
                )
            ),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
        ));

        return parent::_prepareColumns();
    }

    protected function _filterStoreCondition($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $this->getCollection()->addStoreFilter($value);
    }

    protected function _prepareMassaction() {
        $this->setMassactionIdField('cat_id');
        $this->getMassactionBlock()->setFormFieldName('faqs');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('faqs')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('faqs')->__('Are you sure?')
        ));

        $statuses = array(
            1 => Mage::helper('faqs')->__('Enabled'),
            2 => Mage::helper('faqs')->__('Disabled'));
        array_unshift($statuses, array('label' => '', 'value' => ''));
        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('faqs')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
            'additional' => array(
                'visibility' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('faqs')->__('Status'),
                    'values' => $statuses
                )
            )
        ));
        return $this;
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}




