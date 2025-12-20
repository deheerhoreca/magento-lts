<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/

class Amasty_Shopby_Lib_Varien_Data_Form_Element_Multistoreinput extends Varien_Data_Form_Element_Abstract
{
    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setType('text');
        $this->addClass('input-text');
    }

    // DHH CORE HACK: Make IDs unique
    public function getElementHtml(): string
    {
        $valuesByStore = Mage::helper('amshopby')->unserialize($this->getValue());
        if(!$valuesByStore) $valuesByStore[0] = $this->getValue();
        
        $html = "";
        foreach(Mage::helper('amshopby')->getStores() as $_store) {
            $storeId = $_store->getId();
            $name = $this->getName();
            isset($valuesByStore[$storeId]) ? $value = $valuesByStore[$storeId] : $value = '';
            $store = "<label class='bold'>{$_store->getName()}</label>";
            $id    = $this->getMultistoreInputId($storeId);
            $inputName = "multistore[{$name}][{$storeId}]";
            $input = "<input id=\"{$id}\" name=\"{$inputName}\" ".$this->serialize($this->getHtmlAttributes())." value=\"{$value}\">";
            
            $html .= $store.$input.PHP_EOL;
        }
        
        return $html.PHP_EOL;
    }
    
    /**
     * Undocumented function
     *
     * @param  integer $storeId
     * @return string
     */
    public function getMultistoreInputId(?int $storeId): string {
        $id = parent::getId();
        
        if($storeId > 0) {
            return $id."_".$storeId;
        }
        
        return $id;
    }

}
