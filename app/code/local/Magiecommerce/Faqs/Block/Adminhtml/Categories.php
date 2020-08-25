<?php     

class Magiecommerce_Faqs_Block_Adminhtml_Categories extends Mage_Adminhtml_Block_Widget_Grid_Container 
{
    public function __construct() 
    {
        
    $this->_controller = 'adminhtml_categories';
    $this->_blockGroup = 'faqs';
    $this->_headerText = Mage::helper('faqs')->__('Manage FAQ Categories');
    $this->_addButtonLabel = Mage::helper('faqs')->__('Add FAQ Category');
    parent::__construct();
    }
} 
