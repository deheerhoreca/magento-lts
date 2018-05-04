![AfterPay](https://developer.afterpay.io/resources/AfterPay_Logo_300.png)

[![Latest Stable Version](https://poser.pugx.org/payintegrator/afterpay/v/stable)](https://packagist.org/packages/payintegrator/afterpay)
[![Latest Unstable Version](https://poser.pugx.org/payintegrator/afterpay/v/unstable)](https://packagist.org/packages/payintegrator/afterpay)
[![Total Downloads](https://poser.pugx.org/payintegrator/afterpay/downloads)](https://packagist.org/packages/payintegrator/afterpay)
[![License](https://poser.pugx.org/payintegrator/afterpay/license)](https://packagist.org/packages/payintegrator/afterpay)

# AfterPay PHP API client
This package is a convenience wrapper to communicate with the AfterPay SOAP-API or AfterPay.io REST-API.

## Installation
For the installation of the client, use composer.

### Composer
Include the package in your `composer.json` file
``` json
{
    "require": {
        "payintegrator/afterpay": "^1.4"
    }
}
```

...then run `composer update` and load the composer autoloader:

``` php
<?php
require 'vendor/autoload.php';

// ...
```

## Getting started
To get started with connecting to the AfterPay API, please contact AfterPay (https://www.afterpay.nl) for test credentials and account to the AfterPay Order Management System.

## Examples
The folder Examples contains examples for all available operations: [Examples](https://bitbucket.org/afterpay-plugins/afterpay-composer-package/src/dcc0edd91ed4/examples/?at=master)

## Documentation
More documentation can be found at [mip.afterpay.nl](http://mip.afterpay.nl)

## Contributing
We love contributions, but please note that every contribution has to be reviewed and tested. If you have suggested changes, you may create a Pull Request.

## Release notes

2017.10.16 - version 1.4.0
 * DP-104 - Create debug log also when Exceptions happen
 * DP-105 - Calculate VAT Percentages when VAT Amounts has been sent in the REST Request

2017.09.26 - version 1.3.0
 * REST: Add vatAmount posibility to the requests
 * Bugfix: Return SOAP request as readable string to the debug

2017.09.12 - version 1.2.9
 * Bugfix: Added country back to function getWebserivceurl because of compatibility with Client abstract class
 * Bugfix: REST order management operations uses euros instead of eurocents
 * REST: Add backwards compatibility with SOAP client on order management tasks, do every requests in eurocents
 * REST: Send refund in reverse amount of the SOAP client
 * REST: ProductID cannot be more than 50 characters
 * REST: Add support for negative values to the convertPrice function
 * Update code to commit to coding standards
 * REST: Changed priority for setting country because of order management actions
 * REST: Check if DOB isset, else sent empty
 * REST: Added default country and new debug functionality

2017.07.17 - version 1.2.8
 * Bugfix: createOrderLine should have same structure as parent

2017.07.17 - version 1.2.7
 * Rest: Remove fields that are not filled
 * Rest: Added generic salutation
 * Added improvements to Rejection errors both dutch and english
 * Rest: Added country specific VAT rules
 * Bugfix: housenumberaddition was not being sent in SOAP

2017.06.16 - version 1.2.6
 * Rewritten check for using Soap or Rest to use variable $useRest because construct parameters are not always functioning.

2017.06.16 - version 1.2.5
 * Extra release because of composer not get updated to correct version

2017.06.15 - version 1.2.4
 * Bugfix: useRest made every request using REST Client

2017.06.15 - version 1.2.3
 * Checked if firstname isset in Rest Client

2017.06.10 - version 1.2.2
 * Changed guzzle request from json to body/json because of character encoding issues
 * Added more user friendly and clear validation messages
 * Added validation messages for business 2 business requests

2017.05.10 - version 1.2.1
 * Bugfix: typo in B2B person object
 * REST: Added specific fields for Google Data and Product URL
 * REST: Changed amount for totalGrossAmount in euro's from cents
 * REST: Added careOf field to person data
 * REST: Only add mobilephonenumber if available
 * REST: Added risk data ipAddress and existingCustomer
 * REST: Added additional data (pluginProvider, pluginVersion, shopUrl, shopPlatform, shopPlatformVersion)

2017.04.10 - version 1.2.0
 * Compatibility with special characters in order
 * Updated rejection text for missing bankaccountnumber
 * Big migration of old AfterPay Library to new library supporting old AfterPay SOAP aswel as new AfterPay.IO REST interface