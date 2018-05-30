<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Block_Adminhtml_Widget_Grid_Massaction extends Mage_Adminhtml_Block_Widget_Grid_Massaction
{
    public function isAvailable()
    {
        Mage::dispatchEvent('am_grid_massaction_actions', array(
            'block' => $this,
            'page'  => $this->getRequest()->getControllerName(),
        ));  
        
        return parent::isAvailable();
    }    
    
    public function getJavaScript()
    {
        $result = new Varien_Object(array(
            'js'   => parent::getJavaScript(),
            'page' => $this->getRequest()->getControllerName(),
        ));        
        
        Mage::dispatchEvent('am_grid_massaction_js', array('result' => $result));
        
        return $result->getJs();
    }
}
