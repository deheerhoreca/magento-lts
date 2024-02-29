<?php

require __DIR__ . '/vendor/autoload.php';

$modules = apache_get_modules();

echo "<h1>Apache Modules:<h1>";
dump($modules);

