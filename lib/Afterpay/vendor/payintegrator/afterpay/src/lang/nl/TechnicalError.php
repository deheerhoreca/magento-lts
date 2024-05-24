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
    'nl.afterpay.mercury.soap.exception.AccessDeniedException' => [
        'message' => 'Er was een technisch authenticatie probleem in de verbinding met de Riverty BE webservice.',
        'description' => 'Er is een technisch probleem opgetreden, neem contact op met onze klantenservice.'
    ],
    'nl.afterpay.ad3.web.service.impl.exception.AuthenticationException' => [
        'message' => 'Er was een technisch authenticatie probleem in de verbinding met de Riverty NL webservice.',
        'description' => 'Er is een technisch probleem opgetreden, neem contact op met onze klantenservice.'
    ],
    'fallback' => [
        'message' => 'Er is een technisch probleem opgetreden in de verbinding met Riverty',
        'description' =>
            'Er is een technisch probleem opgetreden in de verbinding met Riverty, neem contact op met onze klantenservice.'
    ],
    'default.message' => 'Er is een technisch probleem opgetreden in de verbinding met Riverty, neem contact op met onze klantenservice.'
];