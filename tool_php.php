<?php

// require_once "./vendor/autoload.php";

// define('MAGENTO_ROOT', getcwd());
// $mageFilename = MAGENTO_ROOT . '/app/Mage.php';
// $maintenanceFile = 'maintenance.flag';
// $maintenanceIpFile = 'maintenance.ip';
// require MAGENTO_ROOT . '/app/bootstrap.php';
// require_once $mageFilename;

ob_start();
phpinfo();
$phpinfoAsString = ob_get_contents();
ob_get_clean();

echo $phpinfoAsString;

// $phpInfo = new OutCompute\PHPInfo\PHPInfo();
// $phpInfo->setText($phpinfoAsString);
// echo var_export($phpInfo->get());

// $phpInfo = new OutCompute\PHPInfo\PHPInfo();
// $phpInfo->setText($phpinfoAsString);
// $phpinfo = $phpInfo->get();

// print_r($phpinfo);

// $cloner = new Symfony\Component\VarDumper\Cloner\VarCloner();
// $dumper = new Symfony\Component\VarDumper\Dumper\CliDumper();
// $output = fopen('php://memory', 'r+b');
// $dumper->dump($cloner->cloneVar($phpinfo), $output);
// $response = stream_get_contents($output, -1, 0);

// dump($response);
