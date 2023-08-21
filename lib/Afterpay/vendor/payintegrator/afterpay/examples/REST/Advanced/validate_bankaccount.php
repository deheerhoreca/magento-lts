<?php

// Load AfterPay Library
require_once(__DIR__ . '/vendor/autoload.php'); // Change to correct url

// Create new AfterPay Object
$Afterpay = new \Afterpay\Afterpay();
$Afterpay->setRest();

$Afterpay->set_ordermanagement('validate_bankaccount');

// Set up the additional information
$aporder['bankCode'] = 'DEUTDEBB';
$aporder['bankAccount'] = 'DE89370400440532013000';

// Create the order object for order management (OM)
$Afterpay->set_order($aporder, 'OM');

// Set up the AfterPay credentials and sent the order
$authorisation['apiKey'] = '';
$modus = 'test'; // for production set to 'live'

// Show request in debug
echo '<pre>' . print_r(array('AfterPay Request' => $Afterpay), 1) . '</pre>';

$Afterpay->do_request($authorisation, $modus);

// Show result in debug
echo '<pre>' . print_r(array('AfterPay Result' => $Afterpay->order_result), 1) . '</pre>';
