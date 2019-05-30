<?php
class Eke_Ogmeta_Helper_Data extends Mage_Core_Helper_Abstract {

  public function isOgMetaEnabled(){
    return Mage::getStoreConfig("eke_ogmeta/og/enabled");
  }

	public function getOgImage(){
    return Mage::getStoreConfig("eke_ogmeta/og/image");
  }

	public function getSiteName(){
    return Mage::getStoreConfig("eke_ogmeta/og/sitename");
  }

  public function isProductShortEnabled(){
    return Mage::getStoreConfig("eke_ogmeta/og/productdescription");
 }

 public function getFacebookAppId(){
    return Mage::getStoreConfig("eke_ogmeta/og/appid");
 }

}
