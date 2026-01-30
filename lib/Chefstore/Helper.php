<?php

declare(strict_types=1);

namespace Chefstore;

class Helper {
  
  /**
   * Return the deheerhoreca_util/util helper. OpenMage will cache a singleton for us.
   *
   * @return \DeHeerHoreca_Util_Helper_Util
   */
  public static function loadOmHelperDhhUtil(): \DeHeerHoreca_Util_Helper_Util {
    return \Mage::helper("deheerhoreca_util/util");
  }
  
  /**
   * Normalize an email address for consistent storage and comparison.
   * @todo Consider adding more rules via a lib, e.g., removing dots for Gmail addresses.
   *
   * @param  string|null $email
   * @return string|null
   */
  public static function normalizeEmailAddress(string|null $email): string|null {
    if($email === null) {
      return null;
    }
    $email = trim(strtolower($email));
    return preg_replace('/\s+/', '', $email);
  }
}
