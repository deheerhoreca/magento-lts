<?php

if(substr($_SERVER['HTTP_HOST'], 0, 3) === "dev") {
  define("DHH_FPC_ENABLED", false);
} else {
  define("DHH_FPC_ENABLED", true);
}

$apm_transaction = Mage::app()->getFrontController()->getAction()->getFullActionName();
echo "<script language='javascript'>var apm_trx = '{$apm_transaction}';</script>";

/*
- 1column.phtml:
  - https://www.prokoeling.nl/koelkasten/alle-koelkasten/display-koelkasten/display-koelkast-zwart-wit-met-slot-klapdeuren-800ltr-exquisit.html
- 2columns-left.phtml:
  - https://www.prokoeling.nl/koelkasten/alle-koelkasten.html
*/
