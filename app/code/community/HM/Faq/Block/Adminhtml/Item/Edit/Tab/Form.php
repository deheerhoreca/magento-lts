<?php
/**
 * FAQ accordion for Magento

 */

/**
 * FAQ accordion for Magento
 *
 * Website: www.hiremagento.com 
 * Email: hiremagento@gmail.com
 */
class HM_Faq_Block_Adminhtml_Item_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepares the page layout
     * 
     * Loads the WYSIWYG editor on demand if enabled.
     * 
     * @return HM_Faq_Block_Admin_Edit
     */
   
    
    /**
     * Preparation of current form
     *
     * @return HM_Faq_Block_Admin_Edit_Tab_Main Self
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('faq');
        
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('faq_');
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
        $fieldset = $form->addFieldset('base_fieldset', array (
                'legend' => Mage::helper('hm_faq')->__('General information'), 
                'class' => 'fieldset-wide' ));
        
        if ($model->getFaqId()) {
            $fieldset->addField('faq_id', 'hidden', array (
                    'name' => 'faq_id' ));
        }
        
        $fieldset->addField('question', 'text', array (
                'name' => 'question', 
                'label' => Mage::helper('hm_faq')->__('FAQ item question'), 
                'title' => Mage::helper('hm_faq')->__('FAQ item question'), 
                'required' => true ));
        
        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'multiselect', 
                    array (
                            'name' => 'stores[]', 
                            'label' => Mage::helper('cms')->__('Store view'), 
                            'title' => Mage::helper('cms')->__('Store view'), 
                            'required' => true, 
                            'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true) ));
        }
        else {
            $fieldset->addField('store_id', 'hidden', array (
                    'name' => 'stores[]', 
                    'value' => Mage::app()->getStore(true)->getId() ));
            $model->setStoreId(Mage::app()->getStore(true)->getId());
        }
        
        $fieldset->addField('is_active', 'select', 
                array (
                        'label' => Mage::helper('cms')->__('Status'), 
                        'title' => Mage::helper('hm_faq')->__('Item status'), 
                        'name' => 'is_active', 
                        'required' => true, 
                        'options' => array (
                                '1' => Mage::helper('cms')->__('Enabled'), 
                                '0' => Mage::helper('cms')->__('Disabled') ) ));
        
        /*$fieldset->addField('category_id', 'multiselect', 
            array (
                'label' => Mage::helper('hm_faq')->__('Category'), 
                'title' => Mage::helper('hm_faq')->__('Category'), 
                'name' => 'categories[]', 
                'required' => false,
                'values' => Mage::getResourceSingleton('hm_faq/category_collection')->toOptionArray(),
            )
        );*/
        
        $fieldset->addField('answer', 'editor', 
                array (
                        'name' => 'answer', 
                        'label' => Mage::helper('hm_faq')->__('Content'), 
                        'title' => Mage::helper('hm_faq')->__('Content'), 
                        'style' => 'height:36em;',
                        'config'    => $wysiwygConfig,
                        'required' => true ));
        
        $fieldset->addField('answer_html', 'select', 
                array (
                        'label' => Mage::helper('hm_faq')->__('HTML answer'), 
                        'title' => Mage::helper('hm_faq')->__('HTML answer'), 
                        'name' => 'answer_html', 
                        'required' => true, 
                        'options' => array (
                                '1' => Mage::helper('cms')->__('Enabled'), 
                                '0' => Mage::helper('cms')->__('Disabled') ) ));
        
        $form->setValues($model->getData());
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}
