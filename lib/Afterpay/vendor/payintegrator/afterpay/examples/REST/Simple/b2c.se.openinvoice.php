<?php

// Load AfterPay Library
require_once(__DIR__ . '/vendor/autoload.php'); // Change to correct url

// Create new AfterPay Object
$Afterpay = new \Afterpay\Afterpay();
$Afterpay->setRest();

// Set up the bill to address
$aporder['billtoaddress']['city'] = 'Påarp';
$aporder['billtoaddress']['housenumber'] = '';
$aporder['billtoaddress']['housenumberaddition'] = '';
$aporder['billtoaddress']['isocountrycode'] = 'SE';
$aporder['billtoaddress']['postalcode'] = '26033';
$aporder['billtoaddress']['referenceperson']['email'] = 'test@arvato.com';
$aporder['billtoaddress']['referenceperson']['firstname'] = 'Hanna';
$aporder['billtoaddress']['referenceperson']['lastname'] = 'Eklund';
$aporder['billtoaddress']['referenceperson']['isolanguage'] = 'SE';
$aporder['billtoaddress']['referenceperson']['ssn'] = '4702192222';
$aporder['billtoaddress']['streetname'] = 'Hagalundsgatan';

// Set up the ship to address
$aporder['shiptoaddress']['city'] = 'Påarp';
$aporder['shiptoaddress']['housenumber'] = '';
$aporder['shiptoaddress']['housenumberaddition'] = '';
$aporder['shiptoaddress']['isocountrycode'] = 'SE';
$aporder['shiptoaddress']['postalcode'] = '26033';
$aporder['shiptoaddress']['referenceperson']['email'] = 'test@arvato.com';
$aporder['shiptoaddress']['referenceperson']['firstname'] = 'Hanna';
$aporder['shiptoaddress']['referenceperson']['lastname'] = 'Eklund';
$aporder['shiptoaddress']['referenceperson']['isolanguage'] = 'SE';
$aporder['shiptoaddress']['referenceperson']['ssn'] = '4702192222';
$aporder['shiptoaddress']['streetname'] = 'Hagalundsgatan';

// Set up the additional information
$aporder['ordernumber'] = 'ORDER1234567-08';
$aporder['currency'] = 'SEK';
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
