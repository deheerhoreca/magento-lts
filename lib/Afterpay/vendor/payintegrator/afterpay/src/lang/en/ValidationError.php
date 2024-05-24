<?php
 /**
 * Copyright (c) 2021 arvato Finance B.V.
 *
 * AfterPay reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of AfterPay.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 * 
 * @name        AfterPay Class
 * @author      AfterPay (plugins@afterpay.nl)
 * @description PHP Library to connect with AfterPay Post Payment services
 * @copyright   Copyright (c) 2021 arvato Finance B.V.
 */

$prefix_message = "An error occurred in the payment request to Riverty: \n\n";

return [
    'field.unknown.invalid' => $prefix_message . 'An unknown field is invalid, please contact our customer service.',
    'field.shipto.person.initials.missing' => $prefix_message . 'The initials of the shipping address are missing.
Please check your shipping details or contact our customer service.',
    'field.shipto.person.initials.invalid' => $prefix_message . 'The initials of the shipping address are invalid.
Please check your shipping details or contact our customer service.',
    'field.billto.person.initials.missing' => $prefix_message . 'The initials of the billing address are missing.
Please check your billing details or contact our customer service.',
    'field.billto.person.initials.invalid' => $prefix_message . 'The initials of the billing address are invalid.
Please check your billing details or contact our customer service.',
    'field.shipto.person.lastname.missing' => $prefix_message . 'The last name of the shipping address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.person.lastname.invalid' => $prefix_message . 'The last name of the shipping address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.billto.person.lastname.missing' => $prefix_message . 'The last name of the billing address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.person.lastname.invalid' => $prefix_message . 'The last name of the billing address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.billto.city.missing' => $prefix_message . 'The city of the billing address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.city.invalid' => $prefix_message . 'The city of the billing address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.shipto.city.missing' => $prefix_message . 'The city of the shipping address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.city.invalid' => $prefix_message . 'The city of the shipping address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.billto.housenumber.missing' => $prefix_message . 'The house number of the billing address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.housenumber.invalid' => $prefix_message . 'The house number of the billing address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.shipto.housenumber.missing' => $prefix_message . 'The house number of the shipping address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.housenumber.invalid' => $prefix_message . 'The house number of the shipping address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.billto.postalcode.missing' => $prefix_message . 'The postalcode of the billing address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.postalcode.invalid' => $prefix_message . 'The postalcode of the billing address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.shipto.postalcode.missing' => $prefix_message . 'The postalcode of the shipping address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.postalcode.invalid' => $prefix_message . 'The postalcode of the shipping address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.person.gender.missing' => $prefix_message . 'The gender of the shipping address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.person.gender.invalid' => $prefix_message . 'The gender of the shipping address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.billto.person.gender.missing' => $prefix_message . 'The gender of the billing address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.person.gender.invalid' => $prefix_message . 'The gender of the billing address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.billto.housenumberaddition.missing' => $prefix_message . 'The house number addition of the billing address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.housenumberaddition.invalid' => $prefix_message . 'The house number addition of the billing address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.shipto.housenumberaddition.missing' => $prefix_message . 'The house number addition of the shipping address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.housenumberaddition.invalid' => $prefix_message . 'The house number addition of the shipping address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.billto.phonenumber1.missing' => $prefix_message . 'The fixed line and/or mobile number is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.phonenumber1.invalid' => $prefix_message . 'The fixed line and/or mobile number is invalid.
                    Please check your billing details or contact our customer service.',
    'field.billto.phonenumber2.invalid' => $prefix_message . 'The fixed line and/or mobile number is invalid.
                    Please check your billing details or contact our customer service.',
    'field.shipto.person.emailaddress.missing' => $prefix_message . 'The email address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.person.emailaddress.invalid' => $prefix_message . 'The email address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.billto.person.emailaddress.missing' => $prefix_message . 'The email address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.person.emailaddress.invalid' => $prefix_message . 'The email address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.shipto.person.dateofbirth.missing' => $prefix_message . 'The date of birth is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.person.dateofbirth.invalid' => $prefix_message . 'The date of birth is missing.
                    Please check your shipping details or contact our customer service.',
    'field.billto.person.dateofbirth.missing' => $prefix_message . 'The date of birth is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.person.dateofbirth.invalid' => $prefix_message . 'The date of birth is invalid.
                    Please check your billing details or contact our customer service.',
    'field.billto.isocountrycode.missing' => $prefix_message . 'The country code of the billing address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.isocountrycode.invalid' => $prefix_message . 'The country code of the billing address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.shipto.isocountrycode.missing' => $prefix_message . 'The country code of the shipping address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.isocountrycode.invalid' => $prefix_message . 'The country code of the shipping address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.person.prefix.missing' => $prefix_message . 'The prefix of the shipping address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.person.prefix.invalid' => $prefix_message . 'The prefix of the shipping address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.billto.person.prefix.missing' => $prefix_message . 'The prefix of the billing address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.person.prefix.invalid' => $prefix_message . 'The prefix of the billing address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.billto.isolanguagecode.missing' => $prefix_message . 'The language of the billing address is missing.
                    Please check your billing details or contact our customer service.',
    'field.billto.isolanguagecode.invalid' => $prefix_message . 'The language of the billing address is invalid.
                    Please check your billing details or contact our customer service.',
    'field.shipto.isolanguagecode.missing' => $prefix_message . 'The language of the shipping address is missing.
                    Please check your shipping details or contact our customer service.',
    'field.shipto.isolanguagecode.invalid' => $prefix_message . 'The language of the shipping address is invalid.
                    Please check your shipping details or contact our customer service.',
    'field.ordernumber.missing' => $prefix_message . 'The ordernumber is missing.
                    Please contact our customer service.',
    'field.ordernumber.invalid' => $prefix_message . 'The ordernumber is invalid.
                    Please contact our customer service.',
    'field.ordernumber.exists' => $prefix_message . 'The ordernumber already exists.
                    Please contact our customer service.',
    'field.bankaccountnumber.missing' => $prefix_message . 'The bankaccountnumber is missing.
                    Please check your bankaccountnumber or contact our customer service.',
    'field.bankaccountnumber.invalid' => $prefix_message . 'The bankaccountnumber is invalid.
                    Please check your bankaccountnumber or contact our customer service.',
    'field.currency.missing' => $prefix_message . 'The currency is missing.
                    Please contact our customer service.',
    'field.currency.invalid' => $prefix_message . 'The currency is invalid.
                    Please contact our customer service.',
    'field.orderline.missing' => $prefix_message . 'The orderline is missing.
                    Please contact our customer service.',
    'field.orderline.invalid' => $prefix_message . 'The orderline is invalid.
                    Please contact our customer service.',
    'field.totalorderamount.missing' => $prefix_message . 'The total order amount is missing.
                    Please contact our customer service.',
    'field.totalorderamount.invalid' => $prefix_message . 'The total order amount is invalid. This is probably due to a rounding difference.
                    Please contact our customer service.',
    'field.parenttransactionreference.missing' => $prefix_message . 'The parent transaction reference is missing.
                    Please contact our customer service.',
    'field.parenttransactionreference.invalid' => $prefix_message . 'The parent transaction reference is invalid.
                    Please contact our customer service.',
    'field.parenttransactionreference.exists' => $prefix_message . 'The parent transaction reference already exists.
                    Please contact our customer service.',
    'field.vat.missing' => $prefix_message . 'The vat is missing.
                    Please contact our customer service.',
    'field.vat.invalid' => $prefix_message . 'The vat is invalid.
                    Please contact our customer service.',
    'field.quantity.missing' => $prefix_message . 'The quantity is missing.
                    Please contact our customer service.',
    'field.quantity.invalid' => $prefix_message . 'The quantity is invalid.
                    Please contact our customer service.',
    'field.unitprice.missing' => $prefix_message . 'The unitprice is missing.
                    Please contact our customer service.',
    'field.unitprice.invalid' => $prefix_message . 'The unitprice is invalid.
                    Please contact our customer service.',
    'field.netunitprice.missing' => $prefix_message . 'The netunitprice is missing.
                    Please contact our customer service.',
    'field.netunitprice.invalid' => $prefix_message . 'The netunitprice is invalid.
                    Please contact our customer service.',
    'field.company.cocnumber.invalid' => $prefix_message . 'The chamber of commerce number is invalid.
                    Please check your billing details or contact our customer service.',
    'field.company.cocnumber.missing' => $prefix_message . 'The chamber of commerce number is missing.
                    Please check your billing details or contact our customer service.',
    'field.company.companyname.invalid' => $prefix_message . 'The company name is invalid.
                    Please check your billing details or contact our customer service.',
    'field.company.companyname.missing' => $prefix_message . 'The company name is missing.
                    Please check your billing details or contact our customer service.',
    'field.company.department.invalid' => $prefix_message . 'The company department is invalid.
                    Please check your billing details or contact our customer service.',
    'field.company.department.missing' => $prefix_message . 'The company department is missing.
                    Please check your billing details or contact our customer service.',
    'field.company.establishmentnumber.invalid' => $prefix_message . 'The establishment number is invalid.
                    Please check your billing details or contact our customer service.',
    'field.company.establishmentnumber.missing' => $prefix_message . 'The establishment number is missing.
                    Please check your billing details or contact our customer service.',
    'invoicenumber.amount.limit' => 'The refund cannot be processed, because you are trying to refund more than is available on the invoice. Please check your refund details or contact the customer service of Riverty.',
    'fallback' => $prefix_message . 'An unknown field is invalid.
                    Please check your shipping and billing details or contact our customer service.'
];