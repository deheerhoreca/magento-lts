<?php

declare(strict_types=1);

namespace Chefstore;

class Helper {
  
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
