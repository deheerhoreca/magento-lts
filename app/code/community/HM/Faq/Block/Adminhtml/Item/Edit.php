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
class HM_Faq_Block_Adminhtml_Item_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	/**
	 * Constructor for the FAQ edit form
	 *
	 */
	public function __construct()
	{
		$this->_objectId = 'faq_id';
        $this->_blockGroup = 'hm_faq';
		$this->_controller = 'adminhtml_item';
		
		parent::__construct();
		
		$this->_updateButton('save', 'label', Mage::helper('hm_faq')->__('Save FAQ item'));
		$this->_updateButton('delete', 'label', Mage::helper('hm_faq')->__('Delete FAQ item'));
		
		$this->_addButton('saveandcontinue', array (
				'label' => Mage::helper('hm_faq')->__('Save and continue edit'), 
				'onclick' => 'saveAndContinueEdit()', 
				'class' => 'save' ), -100);
		
		$this->_formScripts[] = "
		function toggleEditor() {
								if (tinyMCE.getInstanceById('block_content') == null) {
									tinyMCE.execCommand('mceAddControl', false, 'block_content');
								} else {
									tinyMCE.execCommand('mceRemoveControl', false, 'block_content');
								}
							}
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
	}
	protected function _prepareLayout() {
			parent::_prepareLayout();
			if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
				$this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
			}
		}
	
	/**
	 * Helper function to edit the header of the current form
	 *
	 * @return string Returns an "edit" or "new" text depending on the type of modifications.
	 */
	public function getHeaderText()
	{
		if (Mage::registry('faq')->getFaqId()) {
			return Mage::helper('hm_faq')->__("Edit FAQ item '%s'", $this->htmlEscape(Mage::registry('faq')->getQuestion()));
		}
		else {
			return Mage::helper('hm_faq')->__('New FAQ item');
		}
	}
	
	public function getFormActionUrl()
	{
        return $this->getUrl('*/faq/save');
    }

    /**
     * Returns the CSS class for the header
     * 
     * Usually 'icon-head' and a more precise class is returned. We return
     * only an empty string to avoid spacing on the left of the header as we
     * don't have an icon.
     * 
     * @return string
     */
    public function getHeaderCssClass()
    {
        return '';
    }
}
