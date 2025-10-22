<?php

declare(strict_types=1);

namespace Chefstore;

class Helper {
  
  /**
   * Return the deheerhoreca_util/util helper.
   *
   * @return \DeHeerHoreca_Util_Helper_Util
   */
  public static function loadOmHelperDhhUtil(): \DeHeerHoreca_Util_Helper_Util {
    return \Mage::helper("deheerhoreca_util/util");
  }
}
