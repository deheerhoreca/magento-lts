<?php
class Magiecommerce_Faqs_Block_Adminhtml_Categories_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm() {
        $_model = Mage::registry('faqs_data');

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $form->setHtmlIdPrefix('faqs_');

        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(array('tab_id' => 'form_section'));
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
        $fieldset->addField('cat_name', 'text', array(
            'label'     => Mage::helper('faqs')->__('Name'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'cat_name',
            'value'     => $_model->getName()
        ));
        $categoryCollection = Mage::getModel('faqs/categories')->getCollection();
       $catid = $this->getRequest()->getParam('id');
       $categoryCollection->addFieldToFilter('parentcatid','0');
       $categoryCollection->addFieldToFilter('cat_id', array('neq' =>$catid ));

            $category = "";
            $category[] = array('value'=>"0",'label'=>"Root Category");
            foreach ($categoryCollection as $_category) {
                $category[]= array('value'=>$_category->getId(),'label'=>$_category->getCatName());

            }

            $fieldset->addField('parentcatid', 'select', array(
                'label' => Mage::helper('faqs')->__('Select Parent Category'),
                'required' => false,
                'name' => 'parentcatid',
                'selected' => 'selected',
                'values' => $category,
            ));
            $fieldset->addField('urlkey', 'text', array(
            'label'     => Mage::helper('faqs')->__('URL key'),
            'required'  => false,
            'name'      => 'urlkey',
            'value'     => $_model->getUrlkey()
        ));
        $fieldset->addField('page_layout', 'select', array(
                        'label' => Mage::helper('faqs')->__('Page Layout'),
                        'required' => false,
                        'name' => 'page_layout',
                        'selected' => 'selected',
                       'values' => Mage::getSingleton('page/source_layout')->toOptionArray(),
                    ));




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
        $fieldset->addField('description', 'editor', array(
            'label'     => Mage::helper('faqs')->__('Description'),
            'required'  => false,
            'name'      => 'description',
            'config'    => $wysiwygConfig,
            'value'     => $_model->getDescription(),
            'wysiwyg'   => true
        ));

        $fieldset->addField('cat_sortorder', 'text', array(
            'label'     => Mage::helper('faqs')->__('Sort Order'),
            'required'  => false,
            'name'      => 'cat_sortorder',
            'value'     => $_model->getSortOrder()
        ));

        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('faqs')->__('Is Active'),
            'name'      => 'status',
            'values'    => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'value'     => $_model->getIsActive()
        ));


        if ( Mage::registry('faqs_data') ) {
            $form->setValues(Mage::registry('faqs_data')->getData());
        }

        return parent::_prepareForm();
    }
}