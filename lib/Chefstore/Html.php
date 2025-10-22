<?php

declare(strict_types=1);

namespace Chefstore;

class Html {
  
  // \Chefstore\Html\addEncodedJsStatement("var x=1;");
  // Add an already-encoded JavaScript statement for echo'ing just before </body>
  public static function addEncodedJsStatement(string $statement): void {
    $GLOBALS["footer_js_statements"] ??= [];
    $GLOBALS["footer_js_statements"][] = $statement;
  }
  
  // Echo any queued JavaScript statements that can wait until just before </body>
  // Statements should be encoded before storing them!
  public static function writeJsStatements(): void {
    if (isset($GLOBALS["footer_js_statements"])) {
      foreach ($GLOBALS["footer_js_statements"] as $statement) {
        echo "<script>" . $statement . "</script>" . PHP_EOL;
      }
    }
  }
}
