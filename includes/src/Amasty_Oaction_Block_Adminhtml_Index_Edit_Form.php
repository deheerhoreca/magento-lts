<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Block_Adminhtml_Index_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form', 
            'action'  => $this->getUrl('*/*/save'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ));
        
        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('amoaction')->__('Import CSV File')));
        $fieldset->addField('csv_file', 'file', array(
            'name'     => 'csv_file',
            'label'    => Mage::helper('amoaction')->__('Select CSV File to Import'),
            'title'    => Mage::helper('amoaction')->__('Select CSV File to Import'),
            'note'     => Mage::helper('amoaction')->__('CSV file fields: Order#,TrackingNumber,CarrierCode,Title.'),
            //'required' => true,
        ));
        
        $form->setUseContainer(true);
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}