<?php declare(strict_types=1);

class DeHeerHoreca_Util_Block_Adminhtml_Template_Grid_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
	
	/**
	 * Renders the image thumbnail column for the products grid.
	 *
	 * @param  Varien_Object $row
	 */
  public function render(Varien_Object $row) {
    return $this->_getValue($row);
  }
  
  /**
   * Renders the image thumbnail column for the products grid.
   *
   * @param  Varien_Object $row
   * @return string
   */
  protected function _getValue(Varien_Object $row): string {
    $val = (string) $row->getData($this->getColumn()->getId());
    if($val !== "" && $val !== "no_selection") {
      $cdn_img_options  = [
        "add_mod_time"    => true,
        "height"          => 50,
        "identifier"      => $row->getData("sku"),
        "lazy"            => true,
        "url"             => omGetProductImageUrl($val),
        "width"           => 50,
        "xform"           => "omcatprdlstfr",
      ];
      
      return (string) _cdn_img($cdn_img_options);
    }
    
    return "";
  }
}
