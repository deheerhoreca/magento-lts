<?php

class TM_FireCheckout_Block_Adminhtml_Form_Field_Multirow extends Mage_Core_Block_Abstract
{
    /**
     * Return option HTML node
     *
     * @param array $option
     * @param boolean $selected
     * @return string
     */
    protected function _optionToHtml($option)
    {
        $selectedHtml = ' #{option_extra_attr_' . self::calcOptionHash($option['value'], $option['salt']) . '}';

        return sprintf(
            '<option value="%s"%s>%s</option>',
            $this->htmlEscape($option['value']),
            $selectedHtml,
            $this->htmlEscape($option['label'])
        );
    }

    public function getHtml()
    {
        return $this->toHtml();
    }

    public function calcOptionHash($optionValue, $optionSalt = null)
    {
        return sprintf('%u', crc32($this->getName() . $this->getId() . $optionValue . $optionSalt));
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
