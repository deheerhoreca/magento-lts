<?php

// Load AfterPay Library
require_once(__DIR__ . '/vendor/autoload.php'); // Change to correct url

// Create new AfterPay Object
$Afterpay = new \Afterpay\Afterpay();

$Afterpay->set_ordermanagement('request_order_status');

// Set up the additional information
$aporder['ordernumber'] = 'ORDER123456-46';

// Create the order object for order management (OM)
$Afterpay->setOrderCountry('NL');
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
