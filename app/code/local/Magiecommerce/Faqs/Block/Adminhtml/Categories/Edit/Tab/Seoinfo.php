<?php
class Magiecommerce_Faqs_Block_Adminhtml_Categories_Edit_Tab_Seoinfo extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
            $_model = Mage::registry('faqs_data');
		$form = new Varien_Data_Form();
		$this->setForm($form);
                 $fieldset = $form->addFieldset('faqs_form', array('legend'=>Mage::helper('faqs')->__('SEO information')));
		 $fieldset->addField('metatitle', 'text', array(
            'label'     => Mage::helper('faqs')->__('Meta Title'),
            'name'      => 'metatitle',
            'values'    => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'value'     => $_model->getMetatitle()
        ));
              $fieldset->addField('metakeyword', 'textarea', array(
            'label'     => Mage::helper('faqs')->__('Meta Keywords'),
            'name'      => 'metakeyword',
            'values'    => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'value'     => $_model->getMetakeyword()
        ));
                 $fieldset->addField('metadescription', 'textarea', array(
            'label'     => Mage::helper('faqs')->__('Meta Description'),
            'name'      => 'metadescription',
            'values'    => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'value'     => $_model->getMetadescription()
        ));

        if ( Mage::registry('faqs_data') ) {
            $form->setValues(Mage::registry('faqs_data')->getData());
        }


		return parent::_prepareForm();
	}
}
?>
