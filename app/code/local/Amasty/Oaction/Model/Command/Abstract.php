<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Model_Command_Abstract
{ 
    protected $_type       = '';
    protected $_label      = '';
    protected $_fieldLabel = '';
    
    protected $_errors    = array();    
    
    public function __construct($type='')
    {
        $this->_type = $type;
    }
    
    /**
     * Factory method. Creates a new command object by its type
     *
     * @param string $type command type
     * @return Amasty_Oaction_Model_Command_Abstract
     */
    public static function factory($type)
    {
        $className = 'Amasty_Oaction_Model_Command_' . ucfirst($type);
        return new $className($type);
    }
    
    
    /**
     * Command name.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }
    
    /**
     * Executes the command
     *
     * @param array $ids product ids
     * @param string $val field value
     * @return string success message if any
     */
    public function execute($ids, $val)
    {
        $this->_errors = array();
        
        $hlp = Mage::helper('amoaction');
        if (!is_array($ids)) {
            throw new Exception($hlp->__('Please select order(s)')); 
        }
        
        return '';
    }
    
    /**
     * Adds the command label to the mass actions list
     *
     * @param Mage_Adminhtml_Block_Widget_Grid_Massaction $block
     * @return Amasty_Oaction_Model_Command_Abstract
     */
    public function addAction($block)
    {
        $block->addItem('amoaction_' . $this->_type, array(
            'label'      => $block->__($this->_label),
		    'url'        => Mage::helper('adminhtml')->getUrl('adminhtml/amoaction/do/command/' . $this->_type),
            'additional' => $this->_getValueField($block->__($this->_fieldLabel)),		    
        ));
        
        return $this;         
    }    
    
    /**
     * Returns value field options for the mass actions block
     *
     * @param string $title field title
     * @return array
     */
    protected function _getValueField($title)
    {
        if (!$title)
            return null;
        
        $hlp = Mage::helper('amoaction');
        $yesno = array();
        $yesno[] = array('value' => 0, 'label' => $hlp->__('No'));
        $yesno[] = array('value' => 1, 'label' => $hlp->__('Yes'));
        
        $field = array('amoaction_value' => array(
            'name'   => 'amoaction_value',
            'type'   => 'select',
            'class'  => 'required-entry',
            'label'  => $title,
            'values' => $yesno,
            'value'  => $this->_getDefault(),
        )); 
        return $field;       
    }
    
    /**
     * Gets list of not critical errors after the command execution
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;       
    } 
    
    public function hasResponse()
    {
        return false;
    } 
    
    public function getResponseName()
    {
        return '';
    } 
    
    public function getResponseType()
    {
        return 'application/pdf';
    } 
    
    public function getResponseBody()
    {
        return 'application/pdf';
    }

    protected function _getDefault()
    {
        return (int)Mage::getStoreConfig('amoaction/' . $this->_type . '/notify');
    }
    
    public function orderUpdateNotify($status)
    {
        $notify = false;
        if (Mage::helper('core')->isModuleEnabled('Amasty_Orderstatus')) {
            $statusCollection = Mage::getResourceModel('amorderstatus/status_collection');
            $statusCollection->addFieldToFilter('is_system', array('eq' => 0));
            foreach ($statusCollection as $statusModel) {
                $pos = strpos($status, '_');
                if (false !== $pos
                && $statusModel->getAlias() == substr($status, $pos + 1)) {
                    if ($statusModel->getNotifyByEmail()) {
                        $notify = true;
                    }
                    break;
                }
            }
        }
        return $notify;
    }
}