<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Block_Adminhtml_Renderer_Shipping_Export extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Input
{
    public function render(Varien_Object $row)
    {
        $ret = '';
        
        $order = Mage::getModel('sales/order')->load($row->getId());
        if ($order->canShip()) {
            
            
            
        }    
        else {
            
            $field = 'track_number';
            if (version_compare(Mage::getVersion(), '1.5.1.0') <= 0){
                $field = 'number';
            }            
            
            $collection = Mage::getModel('sales/order_shipment_track')
                ->getCollection()
                ->addAttributeToSelect($field)
                ->addAttributeToSelect('title')
                ->setOrderFilter($row->getId());
                
            $numbers = array();
            $carriers = array();
            foreach ($collection as $track) {
                $numbers[]  = $track->getData($field);
                $carriers[] = $track->getTitle();
            }

            if($carriers){
                $ret =  $this->__('Carrier:');
                $ret .= "\r\n" . implode(', ', $carriers) . "\r\n";
                $ret .= $this->__('Tracking Number:');
                $ret .= "\r\n" .implode(', ', $numbers);
            }
        }

        return $ret;
    }
    
    public function getFilter()
    {
        return false;
    }
    
    private function getCarriers($code)
    {
        $hash = array();
        
        //convert array to hash
        $vals = Mage::getModel('sales/order_shipment_api_v2')->getCarriers($code);
        foreach ($vals as $v){
            $hash[$v['key']] = $v['value'];
        }
        
        // add custom carrier as dropdown option
        $title = Mage::getStoreConfig('amoaction/ship/title');
        if ($title && isset($hash['custom']) && !Mage::getStoreConfig('amoaction/ship/comment')){
            $hash['custom'] = $title;
        } 
        
        return $hash;
    }
}