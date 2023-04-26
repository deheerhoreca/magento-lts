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
        $url    = Mage::getBaseUrl("media")."catalog/product{$val}";
        $url_1x = "/cdn-cgi/image/fit=pad,width=60,height=60/{$url}";
        $url_2x = "/cdn-cgi/image/fit=pad,width=60,height=60/{$url}";
        $out = "<img srcset='{$url_1x}, {$url_2x} 2x' height=60 width=60 loading=lazy>";
      }
      return $out;
    }
}
