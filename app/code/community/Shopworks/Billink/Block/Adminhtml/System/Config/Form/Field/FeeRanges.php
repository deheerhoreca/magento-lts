<?php

/**
 * Class Shopworks_Billink_Block_Adminhtml_System_Config_Form_Field_Version
 */
class Shopworks_Billink_Block_Adminhtml_System_Config_Form_Field_FeeRanges extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @var Varien_Data_Form_Element_Abstract
     */
    private $_element;

    /**
     * This function is called from Magento
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;

        $html = '<table id="payment_billink_fee_ranges">';
        $html .= $this->getHeadersHtml();
        $html .= $this->getBodyHtml($element);
        $html .= $this->getFooterHtml($element);
        $html .= '</table>';
        $html .= $this->getJs();

        return $html;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    private function getBodyHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<tbody id="fee-range-body">';

        for($i = 1; $i <= $this->getCurrentRowCount(); $i++ )
        {
            $from = $element->getEscapedValue('from_' . $i);
            $until = $element->getEscapedValue('until_' . $i);
            $fee =  $element->getEscapedValue('fee_' . $i);

            $html .= $this->getRowTemplate($i, $from, $until, $fee);
        }

        $html .=  '</tbody>';
        return $html;
    }

    /**
     * @return string
     */
    private function getFooterHtml()
    {
        $html = '<tfoot>';
        $html .= '<tr>';
        $html .= '<td colspan="4"></td>';
        $html .= '<td><button class="scalable add" onclick="billink_fee_ranges_add_row()" type="button">Voeg een rij toe</button></td>';
        $html .= '</tr>';
        $html .= '</tfoot>';

        return $html;
    }

    /**
     * @return string
     */
    private function getHeadersHtml()
    {
        $headers = array(
            'van',
            'tot',
            'kosten',
        );

        $html = '<thead><tr>';
        foreach($headers as $header)
        {
            $html .= '<th>' .  $header . '</th>';
        }
        $html .= '</tr></thead>';

        return $html;
    }

    /**
     * @return string
     */
    private function getJs()
    {
        $html = '<script>';
        $html .= 'var billink_fee_ranges_count = ' . $this->getCurrentRowCount() . ';';
        $html .= 'var billink_fee_ranges_template = "'.addslashes($this->getRowTemplate('#id#')).'";';
        $html .= 'function billink_fee_ranges_add_row(){';
        $html .= '  billink_fee_ranges_count = billink_fee_ranges_count + 1;';
        $html .= '  var template = billink_fee_ranges_template.replace(/#id#/g, billink_fee_ranges_count);';
        $html .= '  $("fee-range-body").insert(template);';
        $html .= '}';
        $html .= '</script>';

        return $html;
    }

    /**
     * @return int
     */
    private function getCurrentRowCount()
    {
        $rows = 0;
        $value = $this->_element->getValue();
        if(is_array($value))
        {
            //count occurences for the 'from' field
            foreach($value as $k=>$v)
            {
                if(substr($k, 0, 5) == 'from_')
                {
                    $rows++;
                }
            }
        }
        return $rows;
    }

    /**
     * @param $id
     * @param string $from
     * @param string $until
     * @param string $fee
     * @return string
     */
    private function getRowTemplate($id, $from='', $until='', $fee='')
    {
        $elementId = 'feerange-'.$id;

        $html = '<tr id="'.$elementId.'">';
        $html .= '<td>' . $this->createInput($this->_element->getName() . '[from_' . $id . ']', $from) . '</td>';
        $html .= '<td>' . $this->createInput($this->_element->getName() . '[until_' . $id. ']', $until) . '</td>';
        $html .= '<td>' . $this->createInput($this->_element->getName() . '[fee_' . $id. ']', $fee) . '</td>';
        $html .= '<td><button class="scalable delete" onclick="$(\''.$elementId.'\').remove();" type="button">verwijderen</button></td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * @param $name
     * @param $value
     * @return string
     */
    private function createInput($name, $value)
    {
        return '<input size="6" name="'.$name.'" value="'.$value.'"/>';
    }

}