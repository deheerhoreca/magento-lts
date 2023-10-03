<?php

// Load AfterPay Library
require_once(__DIR__ . '/vendor/autoload.php'); // Change to correct url

// Create new AfterPay Object
$Afterpay = new \Afterpay\Afterpay();
$Afterpay->setRest();

$Afterpay->set_ordermanagement('customer_lookup');

// Set up the additional information
$aporder['identificationNumber'] = '4702192222';
// optional: $aporder['email'] = 'Test1@arvato.com';
// optional: $aporder['postalCode'] = '26033';
// optional: $aporder['countryCode'] = 'SE';
// optional: $aporder['mobilePhone'] = '0708151617';

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
