<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Lib_Varien_Data_Form_Element_Multistoreselect extends Varien_Data_Form_Element_Select
{
    public function getElementHtml()
    {
        $this->addClass('select');
        $html = "<section>";
        $valuesByStore = @unserialize($this->getValue() );
        if( !$valuesByStore) $valuesByStore[0] = $this->getValue();

        foreach (Mage::helper('amshopby')->getStores() as $_store) {
            isset($valuesByStore[$_store->getId()]) ? $value = $valuesByStore[$_store->getId()] : $value = '';
            if (!is_array($value)) {
                $value = array($value);
            }
            $store = '<label style="display:block;font-weight: bold;" >'.$_store->getName().'</label>';
            $input  = '<select id="'.$this->getHtmlId() . '"';
            $input .= ' name="multistore['.$this->getName().']['.$_store->getId().']"';
            $input .= $this->serialize($this->getHtmlAttributes());
            $input .= ' style="display:block;float:left;width:156px;margin-right:10px" >'."\n";

            if ($values = $this->getValues()) {
                foreach ($values as $key => $option) {
                    if (!is_array($option)) {
                        $input .= $this->_optionToHtml(array(
                            'value' => $key,
                            'label' => $option),
                            $value
                        );
                    } elseif (is_array($option['value'])) {
                        $input .= '<optgroup label="' . $option['label'] . '">' . "\n";
                        foreach ($option['value'] as $groupItem) {
                            $input .= $this->_optionToHtml($groupItem, $value);
                        }
                        $input .= '</optgroup>' . "\n";
                    } else {
                        $input .= $this->_optionToHtml($option, $value);
                    }
                }
            }

            $input .= '</select>' . "\n";
            $html .= '<div style="float:left;">'.$store.$input.'</div>';
            $html .= $this->getAfterElementHtml();
        }
        return $html.'<br style="clear:both;" /></section>';
    }

}