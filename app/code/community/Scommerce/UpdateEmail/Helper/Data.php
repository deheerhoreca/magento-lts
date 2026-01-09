<?php

/**
 * Default helper for Scommerce_UpdateEmail Module
 *
 * @package 	Scommerce_UpdateEmail
 * @category 	Scommerce
 * @author	 	Scommerce Mage <core@scommerce-mage.co.uk>
 */
class Scommerce_UpdateEmail_Helper_Data extends Mage_Catalog_Helper_Data
{
  const XML_PATH_ENABLE      = 'scommerce_updateemail/general/enabled';
  const XML_PATH_LICENSE_KEY = 'scommerce_updateemail/general/license_key';

  /**
   * checks to see if the extension is enabled in admin
   *
   * @return boolean
   */
  public function getEnable()
  {
    return Mage::getStoreConfig(self::XML_PATH_ENABLE) && $this->isLicenseValid();
  }

  /**
   * returns license key administration configuration option
   *
   * @return boolean
   */
  public function getLicenseKey()
  {
    return Mage::getStoreConfig(self::XML_PATH_LICENSE_KEY);
  }

  /**
   * returns whether license key is valid or not
   *
   * @return bool
   */
  public function isLicenseValid()
  {
    return true;
    $sku = strtolower(str_replace('_Helper_Data', '', str_replace('Scommerce_', '', get_class($this))));
    return Mage::helper("scommerce_core")->isLicenseValid($this->getLicenseKey(), $sku);
  }
}
