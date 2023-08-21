<?php

// Load AfterPay Library
require_once(__DIR__ . '/vendor/autoload.php'); // Change to correct url

// Create new AfterPay Object
$Afterpay = new \Afterpay\Afterpay();
$Afterpay->setRest();

$Afterpay->set_ordermanagement('refund_partial');

// Set up the additional information
$aporder['invoicenumber'] = '1';
$aporder['ordernumber'] = 'ORDER1234567-04';
$aporder['creditinvoicenumber'] = 'ORDER1234567-04';

// Set up order lines, repeat for more order lines
$sku = 'PRODUCT1';
$name = 'Product name 1';
$qty = 1;
$price = -3000; // in cents
$tax_amount = 4.79; // in euros
$Afterpay->create_order_line(
    $sku,
    $name,
    $qty,
    $price,
    null, // Tax category not needed for DE
    $tax_amount
);

// Create the order object for order management (OM)
$Afterpay->set_order($aporder, 'OM');

// Set up the AfterPay credentials and sent the order
$authorisation['apiKey'] = '';
$modus = 'test'; // for production set to 'live'

// Show request in debug
var_dump(array('AfterPay Request' => $Afterpay));

$Afterpay->do_request($authorisation, $modus);

// Show result in debug
var_dump(array('AfterPay Result' => $Afterpay->order_result));
