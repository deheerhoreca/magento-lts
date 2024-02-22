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
      if(strlen((string) $val) > 0 && $val !== "no_selection") {
        $media            = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $image_url        = "{$media}catalog/product{$val}";
        if(empty($image_url) === false) {
          $col_width        = 60;
          $media_dir        = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA);
          $image_path       = "{$media_dir}/catalog/product{$val}";
          $cdn_img_options  = [
            "identifier"      => $row->getData("sku"),
            "fs_path"         => $image_path,
            "url"             => $image_url,
            "width"           => $col_width,
            "height"          => $col_width,
            "lazy"            => true,
            "add_mod_time"    => true,
            "class"           => "",
            "style"           => "object-fit:contain;",
          ];
          $img_html         = Mage::helper("deheerhoreca_util/util")->_cdn_img($cdn_img_options);
          return $img_html;
        }
      }
      return $out;
    }
}
