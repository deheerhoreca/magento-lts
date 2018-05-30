<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Adminhtml_Filter_Edit_Tab_Mapped extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $filter = Mage::registry('amshopby_filter');

        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('mapped_options',
            array('legend' => Mage::helper('amshopby')->__('General')));


        $fieldset->addField('use_mapping', 'select', array(
            'label'     => $this->__('Use Mapping'),
            'name'      => 'use_mapping',
            'values'    => array($this->__('No'), $this->__('Yes'))
        ));

        $fieldset->addField('show_child_filter', 'select', array(
            'label'     => $this->__('Show Child Filter'),
            'name'      => 'show_child_filter',
            'values'    => array($this->__('No'), $this->__('Yes'))
        ));

        $fieldset->addType('multistoreinput','Amasty_Shopby_Lib_Varien_Data_Form_Element_Multistoreinput');

        $fieldset->addField('child_filter_name', 'multistoreinput', array(
            'label'     => $this->__('Child Filter Name'),
            'name'      => 'child_filter_name',
            'style'      => 'child_filter_name'
        ));

        $fieldset = $form->addFieldset('mapping_grid',
            array('legend' => Mage::helper('amshopby')->__('Mapping Grid')));

        $fieldset->addType('mapping_grid', 'Amasty_Shopby_Block_Adminhtml_Filter_Renderer_Mappinggrid');
        $fieldset->addField('value_id', 'mapping_grid', array(
            'label'     => '',
            'name'      => 'mapping_grid',
            'onclick' => "",
            'onchange' => "",
            'class' => "test",
            'disabled' => false,
            'readonly' => false,
            'tabindex' => 1
        ));

        $this->setForm($form);
        $data = Mage::getSingleton('adminhtml/session')->getFormData();

        if ($data) {
            $form->setValues($data);
            Mage::getSingleton('adminhtml/session')->setFormData(null);
        } elseif ($filter) {
            $form->setValues($filter->getData());
        }

        return parent::_prepareForm();
    }
}
