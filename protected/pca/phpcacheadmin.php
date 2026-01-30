<?php

// https://www.chefstore.nl/protected/pca/phpcacheadmin.php

declare(strict_types=1);

require __DIR__."/vendor/autoload.php";
require __DIR__."/../../vendor/autoload.php";

if(!isDevIp()) {
  exit;
}

\RobiNN\Pca\Config::setConfigPath(__DIR__."/phpcacheadmin.config.php");
echo (new \RobiNN\Pca\Admin())->render(false);
