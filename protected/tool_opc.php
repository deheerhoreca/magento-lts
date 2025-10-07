<?php

declare(strict_types=1);

// Emergency cache clearing:
if(isset($_GET["jatognietdan"])) {
  opcache_reset();
  echo "opcache reset";
  exit;
}

require __DIR__."/_ip_check.php";

require_once __DIR__."/../vendor/amnuts/opcache-gui/index.php";
