<?php

// Load AfterPay Library
require_once(__DIR__ . '/vendor/autoload.php'); // Change to correct url

// Create new AfterPay Object
$Afterpay = new \Afterpay\Afterpay();
$Afterpay->setRest();

// Set up the bill to address
$aporder['billtoaddress']['city'] = 'Kopenhagen';
$aporder['billtoaddress']['housenumber'] = '';
$aporder['billtoaddress']['housenumberaddition'] = '';
$aporder['billtoaddress']['isocountrycode'] = 'DK';
$aporder['billtoaddress']['postalcode'] = '12345';
$aporder['billtoaddress']['referenceperson']['email'] = 'test@arvato.com';
$aporder['billtoaddress']['referenceperson']['firstname'] = 'Hanna';
$aporder['billtoaddress']['referenceperson']['lastname'] = 'Eklund';
$aporder['billtoaddress']['referenceperson']['isolanguage'] = 'DK';
$aporder['billtoaddress']['referenceperson']['ssn'] = '12345';
$aporder['billtoaddress']['streetname'] = 'Street';

// Set up the ship to address
$aporder['shiptoaddress']['city'] = 'Kopenhagen';
$aporder['shiptoaddress']['housenumber'] = '';
$aporder['shiptoaddress']['housenumberaddition'] = '';
$aporder['shiptoaddress']['isocountrycode'] = 'DK';
$aporder['shiptoaddress']['postalcode'] = '12345';
$aporder['shiptoaddress']['referenceperson']['email'] = 'test@arvato.com';
$aporder['shiptoaddress']['referenceperson']['firstname'] = 'Hanna';
$aporder['shiptoaddress']['referenceperson']['lastname'] = 'Eklund';
$aporder['shiptoaddress']['referenceperson']['isolanguage'] = 'DK';
$aporder['shiptoaddress']['referenceperson']['ssn'] = '12345';
$aporder['shiptoaddress']['streetname'] = 'Street';

// Set up the additional information
$aporder['ordernumber'] = 'ORDER1234567-08';
$aporder['currency'] = 'DKK';
$aporder['ipaddress'] = '89.153.113.224';

// Set up order lines, repeat for more order lines
$sku = 'PRODUCT1';
$name = 'Product name 1';
$qty = 3;
$price = 3000; // in cents
$tax_amount = 4.79; // in euros
$product_url = 'https://www.testsite.com/producturl'; // Url to product detail page
$product_image = 'https://www.testsite.com/productimage.jpg'; // Url to product image
$Afterpay->create_order_line(
    $sku,
    $name,
    $qty,
    $price,
    null, // Tax category not needed for DE
    $tax_amount,
    null, // When available fill in Google Product Category ID
    null, // When available fill in Google Product ID
    $product_url,
    $product_image
);

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
