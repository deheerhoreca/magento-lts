<img src="https://cdn.riverty.design/logo/riverty-checkout-logo.svg" width="200">

[![Latest Stable Version](https://poser.pugx.org/payintegrator/afterpay/v/stable)](https://packagist.org/packages/payintegrator/afterpay)
[![Latest Unstable Version](https://poser.pugx.org/payintegrator/afterpay/v/unstable)](https://packagist.org/packages/payintegrator/afterpay)
[![Total Downloads](https://poser.pugx.org/payintegrator/afterpay/downloads)](https://packagist.org/packages/payintegrator/afterpay)
[![License](https://poser.pugx.org/payintegrator/afterpay/license)](https://packagist.org/packages/payintegrator/afterpay)

# Riverty PHP API client
This package is a convenience wrapper to communicate with the Riverty REST API and the legacy SOAP API.

## Installation
For the installation of the client, use composer.

### Composer
Include the package in your `composer.json` file
``` json
{
    "require": {
        "payintegrator/afterpay": "<VERSION>"
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
To get started with connecting to the Riverty API, please check the Riverty developer portal (https://developer.riverty.com) for test credentials and more specific documentation on how to integrate.

## Examples
The folder Examples contains examples for all available operations.

## Documentation
More documentation can be found at [developer.riverty.com](https://developer.riverty.com)

## Contributing
We love contributions, but please note that every contribution has to be reviewed and tested. If you have suggested changes, you may create a Pull Request.

## Release notes

**2024.02.28 - version 4.4.0**

* DP-1777 - We updated guzzlehttp version constraints and quantity property data type at orderlines.

**2024.02.14 - version 4.3.0**

* DP-1764 - We updated logic for getting gross unit price when creating order lines and resolving issue related to different items prices at authorization and full capture requests when using decimal quantities for products. 

**2023.11.30 - version 4.2.0**

* DP-1717 - We updated our dependency on the GuzzleHTTP library to version 7.8.0. 

**2023.11.07 - version 4.1.0**

* DP-1660 - We have added optimizations to rounding amounts code logic for calculating vat perventage and vat amount to handle null values passed to number_format function
* DP-1661 - We have added support for the legalForm value in the authorisation call.

**2023.06.19 - version 4.0.0**

* DP-1535 - We have added rounding to two decimals for all the amounts that are communicated in the authorisation, capture and refund calls.
* DP-1559 - We have added support for the addressType value in the authorisation call.

**2022.12.20 - version 3.9.0**

* DP-1396 - We have updated the endpoints of the REST API to the new Riverty endpoints.
* DP-1383 - We have fixed an issue of zero vat values to be included in the API calls, to support zero vat value order lines.
* DP-1348 - We have updated the translation folder to be in line with the Riverty brand change.
* DP-1260 - We have changed the way we round numbers to 4 decimals from round to number_format, because of PHP compatibility.
* DP-1251 - We have updated the code structure for the new Pay in X payment method.

**2022.11.17 - version 3.8.0**

* DP-1251 - We have added support for the new Pay in X payment method.

**2022.09.29 - version 3.7.0**

* DP-1217 - We have updated the way of getting the plugin provider data. This is an internal change in the code. No changes are needed.
* DP-1225 - We changed the way we round to 4 decimals.
* DP-1224 - We added the element "country" to the available payment methods call. This to ensure to provide the correct legal information in the response.
* DP-1218 - We added validation on the submitted URLs for product links and images. If the URL's are not working, they will be left empty.
* DP-1249 - We updated our dependency on the GuzzleHTTP library to version 7.4.5.

**2022.09.01 - version 3.6.0**

* DP-1121 - Added support for additional plugin provider data in requests.
* DP-1026 - Added support for PHP data to the Authorization request.
* DP-1037 - Created default values for additionalData element.
* DP-1097 - Added support for additional platform data in SOAP request.
* DP-1025 - Improved the way of sending in B2B data for the Netherlands.
* DP-1158 - Added conversation language to available payment methods request.

**2022.05.16 - version 3.5.3**

* DP-771 - Update dependency on Guzzle 7.4.2.

**2022.05.03 - version 3.5.2**

* DP-771 - Update dependency on Guzzle 7.3.0.
* DP-823 - Adjusting the logic for phone number formatting.

**2022.03.15 - version 3.5.1**

* DP-840 - Unassigned vatCategory and dependencies.
* DP-823 - Adjusting the logic for phone number formatting.

**2022.01.18 - version 3.5.0**

* DP-805 - Do not show the 'too many open orders' rejection message.
* DP-789 - Fixed issue with too much decimals in vatAmount.

**2021.09.29 - version 3.4.0**

* DP-786 - Adding plugin data fields in the Authorize and Available payment methods calls.

**2021.09.23 - version 3.3.0**

* DP-773 - Removed BIC from bankaccount validation, direct debit and installments.

**2021.06.02 - version 3.2.0**

* DP-736 - Allow addresses without housenumbers and remove housenumber and housenumber element when empty.
* DP-749 - Remove MerchantID from API calls.

**2021.04.20 - version 3.1.0**

* DP-741 - Limit the amount of characters in housenumberaddition.

**2021.02.08 - version 3.0.0**

* DP-673 - Add support for Campaign payment method.
* DP-702 - Check initials on special characters (SOAP NL/BE).
* Update copyright.

**2020.11.23 - version 2.9.1**

* DP-462 - Update functionality to call a void request.

**2020.11.16 - version 2.9.0**

* DP-675 - Add functionality to call get_order request.

**2020.10.20 - version 2.8.0**

* DP-664 - Set a max on the decimals in order management requests with REST.

**2020.07.09 - version 2.7.0**

* DP-659 - Update endpoint for the SOAP and REST test environment.

**2020.05.27 - version 2.6.0**

* DP-657 - Update endpoint for the NL SOAP test environment.

**2020.05.19 - version 2.5.0**

* DP-641 - Add error message for SOAP invoice limit.
* DP-652 - Fixed issue with TotalNetAmount not being available for payment methods call.

**2020.03.20 - version 2.4.0**

* DP-627 - Add support for the GroupId element

**2020.03.03 - version 2.3.0**

* DP-345 - Add totalNetAmount to request for available payment methods
* DP-619 - Add support for Direct Debit bankdetails trough REST in NL
* DP-627 - Add support for the GroupId element

**2019.11.07 - version 2.2.0**

* DP-562 - Add support for NL/BE payments through REST.
* DP-598 - Add support for B2B in Germany.

**2019.06.03 - version 2.1.0**

* DP-491 - Added support for French translations
* DP-345 - Added support for Account / Flex payment methods (APS-164)

**2019.01.29 - version 2.0.0**

* DP-532 - Fixed issue with special characters. Description in SOAP is now only allowed on A-Z, a-z, 0-9, space, dash.

**2018.11.28 - version 1.9.0**

* DP-492 - PHP - Add support for sandbox
* Bugfix - Cleaning mobilephonenumber
* Bugfix - Added missing comma in validation file
* Copyright change 2018 to 2019

**2018.08.16 - version 1.8.0**

* DP-437 - PHP - Add support for SOAP request order status
* DP-30  - PHP - Add CustomerLookup functionality
* DP-423 - PHP - Do not divide totalamount by 100 on Void action
* DP-390 - PHP - Make difference in Installments calls for Germany or other countries because only Germany needs Direct Debit
* DP-305 - PHP - Add support for profile tracking and customer individual score

**2018.04.11 - version 1.7.0**

* DP-288 - PHP - Made changes to the error reporting to prevent memory leaks
* DP-289 - PHP - Clear Customer Delivery address value when empty
* DP-290 - PHP - Implement validation of Bank Account request
* DP-163 - PHP - Add specific fields and functionality for Direct Debit requests for Germany
* DP-296 - PHP - Problem with calculating the vat amount on negative values (discount lines)

**2018.02.07 - version 1.6.0**

* DP-137 - Add support for installment requests
* DP-211 - Implement product images and product urls for OneAPI connections
* DP-224 - Add personalidentification number to OneApi Request

**2018.01.22 - version 1.5.0**

* DP-134 - Add merchant id for OneAPI connections
* DP-128 - Restore ability to set order country through service and add new country codes

**2017.10.16 - version 1.4.0**

* DP-104 - Create debug log also when Exceptions happen
* DP-105 - Calculate VAT Percentages when VAT Amounts has been sent in the REST Request

**2017.09.26 - version 1.3.0**

* REST: Add vatAmount posibility to the requests
* Bugfix: Return SOAP request as readable string to the debug

**2017.09.12 - version 1.2.9**

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

**2017.07.17 - version 1.2.8**

* Bugfix: createOrderLine should have same structure as parent

**2017.07.17 - version 1.2.7**

* Rest: Remove fields that are not filled
* Rest: Added generic salutation
* Added improvements to Rejection errors both dutch and english
* Rest: Added country specific VAT rules
* Bugfix: housenumberaddition was not being sent in SOAP

**2017.06.16 - version 1.2.6**

* Rewritten check for using Soap or Rest to use variable $useRest because construct parameters are not always functioning.

**2017.06.16 - version 1.2.5**

* Extra release because of composer not get updated to correct version

**2017.06.15 - version 1.2.4**

* Bugfix: useRest made every request using REST Client

**2017.06.15 - version 1.2.3**

* Checked if firstname isset in Rest Client

**2017.06.10 - version 1.2.2**

* Changed guzzle request from json to body/json because of character encoding issues
* Added more user friendly and clear validation messages
* Added validation messages for business 2 business requests

**2017.05.10 - version 1.2.1**

* Bugfix: typo in B2B person object
* REST: Added specific fields for Google Data and Product URL
* REST: Changed amount for totalGrossAmount in euro's from cents
* REST: Added careOf field to person data
* REST: Only add mobilephonenumber if available
* REST: Added risk data ipAddress and existingCustomer
* REST: Added additional data (pluginProvider, pluginVersion, shopUrl, shopPlatform, shopPlatformVersion)

**2017.04.10 - version 1.2.0**

* Compatibility with special characters in order
* Updated rejection text for missing bankaccountnumber
* Big migration of old AfterPay Library to new library supporting old AfterPay SOAP aswel as new AfterPay.IO REST interface
