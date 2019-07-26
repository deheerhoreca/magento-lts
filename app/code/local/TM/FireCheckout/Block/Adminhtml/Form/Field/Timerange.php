<?php

class TM_FireCheckout_Block_Adminhtml_Form_Field_Timerange extends TM_FireCheckout_Block_Adminhtml_Form_Field_Multirow
{
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml()) {
            return '';
        }

        $html = '<select name="' . $this->getName() . '[]" ' . $this->getExtraParams() . '>';
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $html .= $this->_optionToHtml(array(
                'value' => $hour,
                'label' => $hour,
                'salt'  => 'hour'
            ));
        }
        $html .= '</select>';

        $html .= '&nbsp;:&nbsp;<select name="' . $this->getName() . '[]" ' . $this->getExtraParams() . '>';
        for ($i = 0; $i < 60; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $html .= $this->_optionToHtml(array(
                'value' => $hour,
                'label' => $hour,
                'salt'  => 'minute'
            ));
        }
        $html .= '</select>';

        $column = $this->getColumn();
        $html = '<div style="' . $column['style'] . '">' . $html . '</div>';
        return $html;
    }
}
