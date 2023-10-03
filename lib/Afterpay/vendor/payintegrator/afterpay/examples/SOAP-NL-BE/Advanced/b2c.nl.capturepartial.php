<?php

// Load AfterPay Library
require_once(__DIR__ . '/vendor/autoload.php'); // Change to correct url

// Create new AfterPay Object
$Afterpay = new \Afterpay\Afterpay();

$Afterpay->set_ordermanagement('capture_partial');

// Set up the additional information
$aporder['invoicenumber'] = 'INVOICE123456-46';
$aporder['ordernumber'] = 'ORDER123456-46';

// Set order capture line
$sku = 'PRODUCT1';
$name = 'Product name 1';
$qty = 1;
$price = 3000; // in cents
$tax_category = 1; // 1 = high, 2 = low, 3, zero, 4 no tax
$Afterpay->create_order_line($sku, $name, $qty, $price, $tax_category);

// Create the order object for order management (OM)
$Afterpay->set_order($aporder, 'OM');

// Set up the AfterPay credentials and sent the order
$authorisation['merchantid'] = '';
$authorisation['portfolioid'] = '';
$authorisation['password'] = '';
$modus = 'test'; // for production set to 'live'

// Show request in debug
var_dump(array('AfterPay Request' => $Afterpay));

$Afterpay->do_request($authorisation, $modus);

// Show result in debug
var_dump(array('AfterPay Result' => $Afterpay->order_result));
