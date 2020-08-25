<?php

class Magiecommerce_Faqs_Block_Adminhtml_Faqs_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {

        $_model = Mage::registry('items_data');
        $form = new Varien_Data_Form();
        $this->setForm($form);

          $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(

          array('tab_id' => 'form_section')

);

						$wysiwygConfig["files_browser_window_url"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg_images/index');

						$wysiwygConfig["directives_url"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg/directive');

						$wysiwygConfig["directives_url_quoted"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg/directive');

						$wysiwygConfig["widget_window_url"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/widget/index');

						$wysiwygConfig["files_browser_window_width"] = (int) Mage::getConfig()->getNode('adminhtml/cms/browser/window_width');

						$wysiwygConfig["files_browser_window_height"] = (int) Mage::getConfig()->getNode('adminhtml/cms/browser/window_height');

						$plugins = $wysiwygConfig->getData("plugins");

						$plugins[0]["options"]["url"] = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/system_variable/wysiwygPlugin');

						$plugins[0]["options"]["onclick"]["subject"] = "MagentovariablePlugin.loadChooser('".Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/system_variable/wysiwygPlugin')."', '{{html_id}}');";

						$plugins = $wysiwygConfig->setData("plugins",$plugins);

        $fieldset = $form->addFieldset('faqs_form', array('legend'=>Mage::helper('faqs')->__('General Information')));
        $categoryCollection = Mage::getModel('faqs/categories')->getCollection();

            $category = "";
            foreach ($categoryCollection as $_category) {
                $category[]= array('value'=>$_category->getId(),'label'=>$_category->getCatName());
            }
            $fieldset->addField('cat_id', 'select', array(
                'label' => Mage::helper('faqs')->__('Categories'),
                'required' => false,
                'name' => 'cat_id',
                'selected' => 'selected',
                'values' => $category,
            ));

            /**
         * Check is single store mode
         */

        if (!Mage::app()->isSingleStoreMode()) {

            $fieldset->addField('store_id', 'multiselect',
                    array (
                            'name' => 'store_id[]',
                            'label' => Mage::helper('faqs')->__('Store View'),
                            'title' => Mage::helper('faqs')->__('Store View'),
                            'required' => true,
                            'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true)
                        ));
                        //echo "<pre>"; print_r(Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true)) ;
        }
        else {

            $fieldset->addField('store_id', 'hidden', array (
                    'name' => 'store_id[]',
                    'value' => Mage::app()->getStore(true)->getId() ));

            $_model->setStoreId(Mage::app()->getStore(true)->getId());

        }

        $data = Mage::registry('items_data')->getdata();
        $currentStoreIds = explode(',', $data['store_id']);
        $data['store_id'] = $currentStoreIds;
        $form->setValues($data);

        $fieldset->addField('quetion', 'editor', array(
            'label'     => Mage::helper('faqs')->__('Question'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'quetion',
            'value'     => $_model->getQuetion()
        ));

        $fieldset->addField('answer', 'editor', array(
            'label'     => Mage::helper('faqs')->__('Answer'),
            'required'  => true,
            'name'      => 'answer',
            'config'    => $wysiwygConfig,
            'wysiwyg'   => true,
            'value'     => $_model->getAnswer(),

        ));

        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('faqs')->__('Is Active'),
            'name'      => 'status',
            'values'    => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'value'     => $_model->getStatus()
        ));

        $fieldset->addField('itemsortorder', 'text', array(
            'label'     => Mage::helper('faqs')->__('Sort Order'),
            'required'  => false,
            'name'      => 'itemsortorder',
            'value'     => $_model->getItemsortorder()
        ));
        if ( Mage::registry('faqs_data') )
         {
            $form->setValues(Mage::registry('faqs_data')->getData());
          }

        return parent::_prepareForm();
    }
}
