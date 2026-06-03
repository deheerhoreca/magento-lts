<?php

declare(strict_types=1);

namespace Chefstore;

use Carbon\CarbonImmutable;
use \Illuminate\Support\Collection;
use \Illuminate\Support\Str;
use Mage_Catalog_Model_Product;
use Mage_Core_Model_Store;
use Mage;

class Catalog {
  
  /**
   * Get currently running promos, optionally filtered by product attributes (supplier and brand).
   *
   * @param   Mage_Catalog_Model_Product|null  $_product  The OpenMage product object. Use NULL to get all running promos without product filtering.
   * @return  Collection<int, array{
   *  supplierCode: string,
   *  startDateCET: CarbonImmutable,
   *  startDateHumanNL: string,
   *  endDateCET: CarbonImmutable,
   *  endDateHumanNL: string,
   *  promoCode: string
   *  label: string
   *  labelShort: string
   * }>  An array of currently running promos, each containing supplier code, promo start and end dates in CET, and the promo code.
   */
  public static function getRunningPromos(Mage_Catalog_Model_Product|null $_product = null): Collection {
    $now                  = CarbonImmutable::now();
    static $promoData     = null;
    static $runningPromos = null;
    $promoData ??= self::getPromoData();
    
    // Remove expired and future promos
    $runningPromos ??= $promoData
      ->filter(fn($promo) => $now->isBefore($promo["endDateCET"]) && $now->isAfter($promo["startDateCET"]))
      ->values();
    
    // If no product provided, return all running promos
    if(blank($_product)) {
      return $runningPromos;
    }
    
    // Filter promos based on product attributes (supplierCode and brandCode)
    $runningPromos = $runningPromos->filter(function($promo) use ($_product) {
      $supplierCode = getOmDhhUtilHelper()->get_sys_supplier((string) _get_product_attribute($_product, "supplier"));
      if(!$promo["supplierCode"] || !sis($promo["supplierCode"], $supplierCode)) {
        return false;
      }
      $brandCode = Str::slug(_get_product_attribute($_product, "manufacturer"));
      if(isset($promo["brandCode"]) && !sis($promo["brandCode"], $brandCode)) {
        return false;
      }
      return true;
    })->values();
    
    return $runningPromos;
  }
  
  /**
   * Get the full list of promos with their details, including start and end dates as CarbonImmutable instances.
   * "HENDI5" as a keyword to find this function.
   * 
   * [
   * "supplierCode"  => "hendi",
   * "startDateCET"  => "2026-01-01 00:00:00",
   * "endDateCET"    => "2026-04-01 00:00:00",
   * "promoCode"     => "HENDI5",
   * "label"         => "5% korting op Hendi",    // Used in product detail view
   * "labelShort"    => "5% Promocode",           // Used in product listviews as label/badge
   * ]
   *
   * @return \Illuminate\Support\Collection
   */
  private static function getPromoData(): Collection {
    return collect([
      [
        "supplierCode"  => "hendi",
        "startDateCET"  => "2026-06-01 00:00:00",
        "endDateCET"    => "2026-07-01 00:00:00",
        "promoCode"     => "HENDI5",
        "label"         => "5% korting op Hendi",
        "labelShort"    => "5% Promocode",
      ],
      [
        "supplierCode"  => "diamond",
        "brandCode"     => Str::slug("Diverso by Diamond"),
        "startDateCET"  => "2026-03-26 00:00:00",
        "endDateCET"    => "2026-06-30 23:59:59",
        "promoCode"     => "DIVERSO-5",
        "label"         => "5% korting op Diverso by Diamond",
        "labelShort"    => "5% Promocode",
      ],
      [
        "supplierCode"  => "maxima",
        "brandCode"     => Str::slug("Maxima"),
        "startDateCET"  => "2026-04-23 00:00:00",
        "endDateCET"    => "2026-05-03 23:59:59",
        "promoCode"     => "KONINGSDAG2026",
        "label"         => "5% korting op alles van Maxima",
        "labelShort"    => "5% 👑 Maxima Code",
      ],
    ])->map(fn($promo) => array_merge($promo, [
      "endDateHumanNL"   => CarbonImmutable::createFromFormat("Y-m-d H:i:s", $promo["endDateCET"], "CET")->locale("nl_NL")->translatedFormat("j F Y"),
      "startDateHumanNL" => CarbonImmutable::createFromFormat("Y-m-d H:i:s", $promo["startDateCET"], "CET")->locale("nl_NL")->translatedFormat("j F Y"),
    ]));
  }
}
