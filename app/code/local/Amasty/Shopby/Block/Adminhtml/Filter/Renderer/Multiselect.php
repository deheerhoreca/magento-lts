<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Adminhtml_Filter_Renderer_Multiselect
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $options = Mage::registry('amshopby_value_links');
        if (($size = count($options))) {
            $name = $this->getColumn()->getId();
            $elementId = $name . '-' . $row->getId();
            $name .= '[' .  $row->getId() . '][]';
            $selectBlock = new Mage_Core_Block_Html_Select(array(
                'name'         => $name,
                'id'           => $elementId,
                'class'        => 'multiselect',
                'extra_params' => 'multiple="multiple" size="' . ($size > 5 ? 5 : ($size < 2 ? 2 : $size))
                    . '" style="width:100%"'
            ));
            $value = (explode(',', $row->getData($this->getColumn()->getIndex())));
            $html = $selectBlock->setOptions($options)
                ->setValue($value)
                ->getHtml();

            $html .= '
                <script type="text/javascript">
                //<![CDATA[
                    new Chosen($("' . $elementId . '"), {
                        width: "99%",
                        placeholder_text: "' .  Mage::helper('amshopby')->__('Select Options') . '"
                    });
                //]]></script>';
            return $html;
        } else {
            return Mage::helper('amshopby')->__('Attribute does not has options, so appending is impossible');
        }
    }
}