<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
class Amasty_Shopby_Block_Adminhtml_Value_New_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        //create form structure
        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('filter_id' => $this->getRequest()->getParam('filter_id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data')
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $fldMain = $this->_form->addFieldset('main', array('legend'=> $this->__('General')));

        $note = new Varien_Data_Form_Element_Note(array(
            'text' => $this->__('You will be able to configure other settings when option is created.'),
        ));
        $note->setId('general_note');
        $fldMain->addElement($note);

        $fldMain->addField('title', 'text', array(
                'label'     => $this->__('Title'),
                'name'      => 'title',
            )
        );

        return parent::_prepareForm();
    }
}
