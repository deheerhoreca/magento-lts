<?php
class Magiecommerce_Faqs_Block_Adminhtml_Faqs_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
   public function __construct()
   {

       parent::__construct();

       $this->setId('faqs_id');
       $this->setDefaultSort('itemsortorder');
      $this->setDefaultDir('DESC');
       $this->setSaveParametersInSession(true);

   }
   protected function _prepareCollection()
   {
    $collection = Mage::getModel('faqs/items')->getCollection();
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

  
   protected function _prepareColumns()
   {
       $this->addColumn('faqs_id',
             array(
                    'header' => 'ID',
                    'align' =>'right',
                    'width' => '50px',
                    'index' => 'faqs_id',
               ));
       $this->addColumn('quetion',
               array(
                    'header' => 'Question',
                    'align' =>'left',
                    'index' => 'quetion',
              ));
             if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('faqs')->__('Store View'),
                'index' => 'store_id',
                'type' => 'store',
                'width' => '150px',
                'store_all' => true,
                'store_view' => true,
                'sortable' => false,
                'filter_condition_callback' => array($this, '_filterStoreCondition'),
            ));
        }
       $this->addColumn('cat_name',
             array(
                    'header' => 'Category Name',
                    'width' => '150px',
                    'index' => 'cat_name',
               ));
       $this->addColumn('status', array(
                    'header' => 'Status',
                    'width' => '50px',
                    'index' => 'status',
             'type' => 'options',

                 'options' => array(
                    1 => 'Enabled',
                    2 => 'Disabled',
                ),

             ));
       $this->addColumn('itemsortorder', array(
                    'header' => 'Sort Order',
                    'width' => '50px',
           'align' =>'right',
                    'index' => 'itemsortorder',
             ));
       $this->addColumn('action',
            array(
                'header'    => Mage::helper('faqs')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('faqs')->__('Edit'),
                        'url'     => array(
                            'base'=>'*/*/edit',
                            'params'=>array('store'=>$this->getRequest()->getParam('store'))
                        ),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
        ));
         return parent::_prepareColumns();
    }
    public function getRowUrl($row)
    {
         return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
 protected function _filterStoreCondition($collection, $column) {

        if (!$value = $column->getFilter()->getValue()) {

            return $this;
        }
        //echo "<pre>"; print_r($this->getCollection()->addStoreFilter($value));
        $this->getCollection()->addStoreFilter($value);
    }
    protected function _prepareMassaction() {
        $this->setMassactionIdField('faqs_id');
        $this->getMassactionBlock()->setFormFieldName('faqs');

        $this->getMassactionBlock()->addItem('delete', array(
                'label'    => Mage::helper('faqs')->__('Delete'),
                'url'      => $this->getUrl('*/*/massDelete'),
                'confirm'  => Mage::helper('faqs')->__('Are you sure?')
        ));

        $statuses = array(
                1 => Mage::helper('faqs')->__('Enabled'),
                2 => Mage::helper('faqs')->__('Disabled'));
        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
                'label'=> Mage::helper('faqs')->__('Change status'),
                'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
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

}
