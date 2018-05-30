<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Block_Adminhtml_Renderer_Shipping extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Input
{
    
    public function render(Varien_Object $row)
    {
        $html = '';
        
        $order = Mage::getModel('sales/order')->load($row->getId());
        
        $html = $this->_storeExists($order);
        
        if ($order->canShip() && !$html) {
            
            $default = Mage::getStoreConfig('amoaction/ship/carrier');
            
            $html = $this->__('Carrier:') . '<br />';
            $html .= '<select class="amasty-carrier" rel="'.$row->getId().'" style="width:90%">';
            
            foreach ($this->getCarriers($row->getIncrementId()) as $k => $v){
                $selected = '';
                if ($default == $k){
                    $selected = 'selected="selected"';
                }
                $html .= sprintf('<option value="%s" %s>%s</option>', $k, $selected, $v);
            }
            $html .= '</select><br />';

            if (Mage::getStoreConfig('amoaction/ship/comment')) {
                $html .= Mage::helper('sales')->__('Title:') . '<br />';
                $html .= '<input rel="'.$row->getId().'" class="input-text amasty-comment" value="'.Mage::getStoreConfig('amoaction/ship/title').'" /><br />';
            }
            
            $html .= $this->__('Tracking Number:') . '<br />';
            $html .= '<input rel="'.$row->getId().'" class="input-text amasty-tracking" value="" />';
            
        } else {
            
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
                $html =  $this->__('Carrier:');
                $html .= '<br /><strong>' . implode(', ', $carriers) . '</strong><br />';
                $html .= $this->__('Tracking Number:');
                $html .= '<br /><strong>' .implode(', ', $numbers). '</strong>';
            }
        }

        return $html;
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
    
    protected function _storeExists($order)
    {
        $result = '';
        foreach (Mage::app()->getStores() as $store) {
            $storeIds[] = $store->getId();
        }
        if (!in_array($order->getStoreId(), $storeIds)) {
            $result = '<span style="color: red;"><b>' . $this->__('Warning: Order from store, which no longer exists.') . '</b></span>';
        }
        return $result;
    }
}