<?php

// Load AfterPay Library
require_once(__DIR__ . '/vendor/autoload.php'); // Change to correct url

// Create new AfterPay Object
$Afterpay = new \Afterpay\Afterpay();
$Afterpay->setRest();

// Set up the bill to address
$aporder['billtoaddress']['city'] = 'Antwerpen';
$aporder['billtoaddress']['housenumber'] = '2';
$aporder['billtoaddress']['housenumberaddition'] = '';
$aporder['billtoaddress']['isocountrycode'] = 'BE';
$aporder['billtoaddress']['postalcode'] = '2050';
$aporder['billtoaddress']['referenceperson']['dob'] = '1980-12-12T00:00:00';
$aporder['billtoaddress']['referenceperson']['email'] = 'tester@afterpay.nl';
$aporder['billtoaddress']['referenceperson']['gender'] = 'V';
$aporder['billtoaddress']['referenceperson']['firstname'] = 'Anna';
$aporder['billtoaddress']['referenceperson']['isolanguage'] = 'NL';
$aporder['billtoaddress']['referenceperson']['lastname'] = 'de Tester';
$aporder['billtoaddress']['referenceperson']['phonenumber'] = '051374411';
$aporder['billtoaddress']['streetname'] =  'Katwilgweg';

// Set up the ship to address
$aporder['shiptoaddress']['city'] = 'Antwerpen';
$aporder['shiptoaddress']['housenumber'] = '2';
$aporder['shiptoaddress']['isocountrycode'] = 'BE';
$aporder['shiptoaddress']['postalcode'] = '2050';
$aporder['shiptoaddress']['referenceperson']['dob'] = '1980-12-12T00:00:00';
$aporder['shiptoaddress']['referenceperson']['email'] = 'tester@afterpay.nl';
$aporder['shiptoaddress']['referenceperson']['gender'] = 'V';
$aporder['shiptoaddress']['referenceperson']['firstname'] = 'Anna';
$aporder['shiptoaddress']['referenceperson']['isolanguage'] = 'NL';
$aporder['shiptoaddress']['referenceperson']['lastname'] = 'de Tester';
$aporder['shiptoaddress']['referenceperson']['phonenumber'] = '051374411';
$aporder['shiptoaddress']['streetname'] =  'Katwilgweg';

// Set up the additional information
$aporder['ordernumber'] = 'ORDER1234567-08';
$aporder['currency'] = 'EUR';
$aporder['ipaddress'] = '89.153.113.224';

// Set up order lines, repeat for more order lines
// Set up order lines, repeat for more order lines
$sku = 'PRODUCT1';
$name = 'Château Pouilly-Fuissée Côte-dOr Mâconnais 2018!';
$qty = 3;
$price = 3000; // in cents
$vat_category = 1; // 1 = high, 2 = low, 3, zero, 4 no tax
$vat_amount = 5.21;
$Afterpay->create_order_line($sku, $name, $qty, $price, $vat_category, $vat_amount);

// Create the order object for B2C or B2B
$Afterpay->set_order($aporder, 'B2C');

// Set up the AfterPay credentials and sent the order
$authorisation['apiKey'] = '';
$modus = 'test'; // for production set to 'live'

// Show request in debug
echo '<pre>' . print_r(array('AfterPay Request' => $Afterpay), 1) . '</pre>';

$Afterpay->do_request($authorisation, $modus);

// Show result in debug
echo '<pre>' . print_r(array('AfterPay Result' => $Afterpay->order_result), 1) . '</pre>';
