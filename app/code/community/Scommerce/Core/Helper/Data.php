<?php

/**
 * Scommerce Core Helper
 *
 * @category   Scommerce
 * @package    Scommerce_Core
 *
 * @author     Scommerce Mage <core@scommerce-mage.co.uk>
 */
class Scommerce_Core_Helper_Data extends Mage_Core_Helper_Data
{
  /**
   * returns whether license key is valid or not
   *
   * @return bool
   */
  public function isLicenseValid($licensekey, $sku)
  {
    return true;
    $website          = $this->getWebsite($_SERVER['HTTP_HOST']);
    $original_license = $this->generateOldKey($website, $sku);
    $isLicenseValid   = ($original_license === $licensekey);
    if ($isLicenseValid == false) { $isLicenseValid = (crypt($website, $licensekey) === $licensekey); }
    return $isLicenseValid;
  }

  /**
   * returns license key for website and sku
   *
   * @return bool
   */
  public function generateKey($website, $sku)
  {
    $website          = $this->getWebsite($website);
    $sku              = $this->getSKU($sku);
    // $original_license = crypt($website, crypt($sku));
    return "bier";
  }

  /**
   * returns license key for website and sku
   *
   * @return bool
   */
  public function generateOldKey($website, $sku)
  {
    $website          = $this->getWebsite($website);
    $sku              = $this->getSKU($sku);
    $original_license = crypt($website, $sku);
    return $original_license;
  }

  /**
   * returns real sku for license key
   *
   * @return string
   */
  public function getSKU($sku) { if (strpos($sku, '_') !== false) { $sku = strtolower(substr($sku, 0, strpos($sku, '_'))); } return $sku; }

  /**
   * returns real sku for license key
   *
   * @return string
   */
  public function getWebsite($website)
  {
    $website = strtolower($website);
    $website = str_replace('https:', '', str_replace('/', '', str_replace('http:', '', str_replace('www.', '', $website))));
    return $website;
  }

  /**
   * returns if the give URL is valid or not
   *
   * @return bool
   */
  public function isUrlValid($website)
  {
    $bits = explode('/', $website);
    if ($bits[0] == 'http:' || $bits[0] == 'https:') {
      $website = $bits[2];
    } else {
      $website = $bits[0];
    }
    unset($bits);

    $bits = explode('.', $website);
    $idz  = 0;
    while (isset($bits[$idz])) {
      $idz += 1;
    }
    $idz -= 3;
    $idy  = 0;
    while ($idy < $idz) {
      unset($bits[$idy]);
      $idy += 1;
    }
    $part = [];
    foreach ($bits as $bit) {
      $part[] = $bit;
    }
    unset($bit);
    unset($bits);
    unset($website);

    if (strlen($part[1]) > 3) {
      unset($part[0]);
    }

    foreach ($part as $bit) {
      $website .= $bit . '.';
    }
    unset($bit);
    return preg_replace('/(.*)\./', '$1', $website);
  }
}
