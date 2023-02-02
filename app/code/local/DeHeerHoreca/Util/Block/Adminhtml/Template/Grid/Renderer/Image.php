<?php 
class DeHeerHoreca_Util_Block_Adminhtml_Template_Grid_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row) {
      return $this->_getValue($row);
    }
    
    protected function _getValue(Varien_Object $row)
    {       
      $val = $row->getData($this->getColumn()->getId());
      $out = "-";
      if(strlen($val) > 0 && $val !== "no_selection") {
        $url = Mage::getBaseUrl("media")."catalog/product{$val}";
        $out = "<img src='{$url}' style='width: 60px; height: 60px;'>";
      }
      return $out;
    }
}
