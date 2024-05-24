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


return [
    '29' => [
        'message' => 'The order amount is too high',
        'description' =>
'We are sorry to have to inform you that your request for Riverty on your order is not accepted by Riverty.
This is because the order amount exceeds the allowed amount to pay with Riverty.
We advise you to choose a different payment method to complete your order.'
    ],
    '36' => [
        'message' => 'Customer has no valid email address',
        'description' =>
'We are sorry to have to inform you that your request for Riverty on your order is not accepted by Riverty.
This is because your email address is not correct or not complete.
We advise you to choose a different payment method to complete your order.'
    ],
    '40' => [
        'message' => 'Customer is under 18',
        'description' =>
'We are sorry to have to inform you that your request for Riverty on your order is not accepted by Riverty.
This is because your age is under 18. If you want to use the Riverty service, your age has to be 18 years or older.
We advise you to choose a different payment method to complete your order.'
    ],
    '42' => [
        'message' => 'Customer has no valid address',
        'description' =>
'We are sorry to have to inform you that your request for Riverty on your order is not accepted by Riverty.
This is because your address is not correct or not complete.
We advise you to choose a different payment method to complete your order.'
    ],
    '47' => [
        'message' => 'The order amount is too low',
        'description' =>
'We are sorry to have to inform you that your request for Riverty on your order is not accepted by Riverty.
This is because the order amount is under the allowed amount to pay with Riverty.
We advise you to choose a different payment method to complete your order.'
    ],
    '71' => [
        'message' => 'Customer has no valid company data',
        'description' =>
'We are sorry to have to inform you that your request for Riverty on your order is not accepted by Riverty.
This is because your company data is not correct or not complete.
We advise you to choose a different payment method to complete your order.'
    ],
    'fallback' => [
        'message' => 'General rejection',
        'description' =>
'We are sorry to have to inform you that your request for Riverty on your order is not accepted by Riverty.
This is because of various reasons.
We advise you to choose a different payment method to complete your order.'
    ],
];
