<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Adminhtml_Filter_Edit_Tab_Values_Mapped extends Amasty_Shopby_Block_Adminhtml_Filter_Edit_Tab_Values
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('mappedGrid');
        $this->setPagerVisibility(false);
    }

    /**
     * Disable pagination, as it get broken by ajax
     */
    protected function _preparePage()
    {
        $this->getCollection()->setPageSize(10000);
        $this->getCollection()->setCurPage(1);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setChild('add_option_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('amshopby')->__('Add Grouped Option'),
                    'onclick'   => "saveAndCreateMapped()",
                ))
        );
        return $this;
    }

    public function getMainButtonsHtml()
    {
        $html = parent::getMainButtonsHtml();
        $html.= $this->getAddValueButtonHtml();
        return $html;
    }

    public function getAddValueButtonHtml()
    {
        return $this->getChildHtml('add_option_button');
    }

    protected function _initCollection()
    {
        return Mage::getModel('amshopby/value')->getParentCollection()
            ->addChildren()
            ->addFieldToFilter('filter_id', Mage::registry('amshopby_filter')->getId());
    }

    protected function addIdColumn()
    {
        $this->addColumn('value_id', array(
            'header'    =>  Mage::helper('amshopby')->__('Value ID'),
            'index'     => 'value_id',
            'width'     => '50px',
            'filter' => false,
        ));
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn('children', array(
            'header' => Mage::helper('amshopby')->__('Child Options'),
            'filter'    => false,
            'sortable'  => false,
            'index' => 'children',
            'width'     => '600px',
            'renderer'  => 'amshopby/adminhtml_filter_renderer_multiselect',
        ));

        $this->addColumn('mapped_position', array(
            'header'    => Mage::helper('amshopby')->__('Position'),
            'align'     => 'left',
            'index'     => 'mapped_position',
            'type'      => 'number',
            'width'     => '80px',
            'sortable'  => true,
            'editable'  => true,
            'renderer'  => 'amshopby/adminhtml_filter_renderer_position'
        ));

        $this->addColumn('action2', array(
            'header'    => $this->__('Delete'),
            'width'     => '15px',
            'sortable'  => false,
            'filter'    => false,
            'type'      => 'action',
            'actions'   => array(
                array(
                    'url'       => $this->getUrl('*/amshopby_value/delete') . 'value_id/$value_id'
                        . '/filter_id/' .  Mage::registry('amshopby_filter')->getId(),
                    'caption'   => $this->__('Delete'),
                    'confirm'  =>  $this->__('Option will be deleted! Are you sure?')
                ),
            )
        ));

        $this->addColumn('action', array(
            'header'    => $this->__('Edit'),
            'width'     => '15px',
            'sortable'  => false,
            'filter'    => false,
            'type'      => 'action',
            'actions'   => array(
                array(
                    'url'       => '#',
                    'caption'   => $this->__('Edit'),
                    'onclick' => 'saveAndMapped($value_id)',
                )
            ),
        ));

        return $this;
    }

    public function getRowUrl($item)
    {
        return '';
    }

    protected function _afterToHtml($html)
    {
        $parentIds =  $this->getCollection()->getAllIds();
        $html .= '<input type="hidden" name="parents" value="' . implode(',', $parentIds) . '" />';
        $html .= '<style>.hor-scroll {overflow: visible;}</style>';
        return parent::_afterToHtml($html);
    }

    /**
     * @param Varien_Object $item
     * @return array
     */
    public function getMultipleRows($item)
    {
        /** fix for 1.9.3.x app/design/adminhtml/default/default/template/widget/grid.phtml#170  */
        return null;
    }

    protected function _toHtml()
    {
        $html =  parent::_toHtml();

        if (!Mage::registry('amshopby_filter')->getUseMapping()) {
            $html =  '<p><span style="color:red;font-weight: bold">'
                . $this->__('"%s" option should be enabled.', $this->__('Use Mapping'))
                . '</span></p>' . $html;
        }
        return $html;
    }
}
