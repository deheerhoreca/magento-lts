<?php
/**
 * Anowave Magento Sort Category Products by Drag & Drop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Sort
 * @copyright 	Copyright (c) 2017 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

class Anowave_Sort_Block_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
	public function render(Varien_Object $_product): string {
    // DHH CORE HACK: Replace with CDN image
    if($_product && filled($_product->getThumbnail()) && $_product->getThumbnail() !== "no_selection") {
      static $media = null;
      $media ??= Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
      $image_url = "{$media}catalog/product".$_product->getThumbnail();
      $cdn_img_options  = [
        "identifier"      => $_product->getSku(),
        "url"             => $image_url,
        "width"           => 60,
        "height"          => 60,
        "lazy"            => true,
        "add_mod_time"    => true,
        "xform"           => "omcatprdlstfr",
      ];
      
      return _cdn_img($cdn_img_options);
    }
    
		return "";
	}
}
