<?php

declare(strict_types=1);

namespace Chefstore;

use Mage;
use Throwable;
use voku\helper\HtmlMin;

class Html {
  
  /**
   * Add an already-encoded JavaScript statement for echo'ing just before </body>.
   * Usage: \Chefstore\Html\addEncodedJsStatement("var x=1;");
   *
   * @param  string $statement
   */
  public static function addEncodedJsStatement(string $statement): void {
    $GLOBALS["footer_js_statements"] ??= [];
    $GLOBALS["footer_js_statements"][] = $statement;
  }
  
  /**
   * Echo all stored JavaScript statements just before </body>
   * Statements should be encoded before storing them!
   *
   * @return void
   */
  public static function writeJsStatements(): void {
    if (isset($GLOBALS["footer_js_statements"])) {
      foreach ($GLOBALS["footer_js_statements"] as $statement) {
        echo "<script>" . $statement . "</script>" . PHP_EOL;
      }
    }
  }
  
  /**
   * Minify HTML content, conservatively.
   *
   * @param  string $html
   * @param  bool   $isFragment
   *
   * @return string
   */
  public static function minifyHtml(string $html, bool $isFragment = true): string {
    static $htmlMin = null;
    if($htmlMin === null) {
      $htmlMin = new HtmlMin();
      // $htmlMin->doOptimizeViaHtmlDomParser();                   // optimize html via "HtmlDomParser()"
      // $htmlMin->doSumUpWhitespace();                            // sum-up extra whitespace from the Dom (depends on "doOptimizeViaHtmlDomParser(true)")
      // $htmlMin->doRemoveWhitespaceAroundTags();                 // remove whitespace around tags (depends on "doOptimizeViaHtmlDomParser(true)")
      // $htmlMin->doOptimizeAttributes();                         // optimize html attributes (depends on "doOptimizeViaHtmlDomParser(true)")
      // $htmlMin->doRemoveHttpPrefixFromAttributes();             // remove optional "http:"-prefix from attributes (depends on "doOptimizeAttributes(true)")
      // $htmlMin->doRemoveHttpsPrefixFromAttributes();            // remove optional "https:"-prefix from attributes (depends on "doOptimizeAttributes(true)")
      // $htmlMin->doKeepHttpAndHttpsPrefixOnExternalAttributes(falfse); // keep "http:"- and "https:"-prefix for all external links 
      // $htmlMin->doRemoveDefaultAttributes();                    // remove defaults (depends on "doOptimizeAttributes(true)" | disabled by default)
      // $htmlMin->doRemoveDeprecatedScriptCharsetAttribute();     // remove deprecated charset-attribute - the browser will use the charset from the HTTP-Header, anyway
      // $htmlMin->doRemoveDeprecatedTypeFromScriptTag();          // remove deprecated script-mime-types (depends on "doOptimizeAttributes(true)")
      // $htmlMin->doRemoveDeprecatedTypeFromStylesheetLink();     // remove "type=text/css" for css links (depends on "doOptimizeAttributes(true)")
      // $htmlMin->doRemoveDeprecatedTypeFromStyleAndLinkTag();    // remove "type=text/css" from all links and styles
      // $htmlMin->doRemoveValueFromEmptyInput();                  // remove 'value=""' from empty <input> (depends on "doOptimizeAttributes(true)")
      // $htmlMin->doRemoveEmptyAttributes();                      // remove some empty attributes (depends on "doOptimizeAttributes(true)")
      // $htmlMin->doSortCssClassNames();                          // sort css-class-names, for better gzip results (depends on "doOptimizeAttributes(true)")
      // $htmlMin->doSortHtmlAttributes();                         // sort html-attributes, for better gzip results (depends on "doOptimizeAttributes(true)")
      // $htmlMin->doRemoveOmittedQuotes();                        // remove quotes e.g. class="lall" => class=lall
      // $htmlMin->doRemoveOmittedHtmlTags();                      // remove ommitted html tags e.g. <p>lall</p> => <p>lall
      
      // if($isFragment) {
        
      // }
      
      // Needs work:
      $htmlMin->doRemoveDeprecatedAnchorName(false);                  // Good idea, but breaks some themes. @todo Move to anchor links via HTML IDs.
      $htmlMin->doRemoveDefaultMediaTypeFromStyleAndLinkTag(false);   // remove "media="all". Not sure if this is ready for production. We use media=all to defer loading CSS.
      $htmlMin->doRemoveSpacesBetweenTags(false);                     // remove more (aggressive) spaces in the dom (disabled by default)
      $htmlMin->doRemoveDefaultTypeFromButton(false);                 // remove type="submit" from button tags 
      
      // Disabled:
      
      // Danger: seems like a simple string replacement, so it will break HTML canonical links and similar which cannot be relative
      $htmlMin->doMakeSameDomainsLinksRelative([]);                   // make some links relative, by removing the domain from attributes
      $htmlMin->doRemoveComments(false);                              // remove default HTML comments (depends on "doOptimizeViaHtmlDomParser(true)")
    }
    
    try {
      return $htmlMin->minify($html);
    } catch(Throwable $e) {
      Mage::log("HTML minification failed: ".$e->getMessage());
      return $html;
    }
  }
}
