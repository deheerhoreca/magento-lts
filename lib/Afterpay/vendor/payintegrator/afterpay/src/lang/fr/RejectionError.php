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
"Pour l'instant, il n'est malheureusement pas possible de payer votre commande ultérieurement avec Riverty. Le montant total de votre commande est trop élevé.
Pour plus d'informations, veuillez contacter le service clientèle d'Riverty. Les coordonnées et les réponses aux questions fréquentes sont disponibles à l'adresse https://my.riverty.com/nl-be.
Nous vous conseillons de commander pour un montant inférieur ou de compléter votre commande avec un autre mode de paiement."
    ],
    '36' => [
        'message' => 'Customer has no valid email address',
        'description' =>
"Pour l'instant, il n'est malheureusement pas possible de payer votre commande ultérieurement avec Riverty.
Ceci est dû au fait que vous avez saisi une adresse e-mail invalide.
Nous vous conseillons de compléter votre commande avec un autre mode de paiement."
    ],
    '40' => [
        'message' => 'Customer is under 18',
        'description' =>
"Pour l'instant, il n'est malheureusement pas possible de payer votre commande ultérieurement avec Riverty.
C'est parce que vous n'avez pas encore 18 ans ou plus.
Nous vous conseillons de compléter votre commande avec un autre mode de paiement."
    ],
    '42' => [
        'message' => 'Customer has no valid address',
        'description' =>
"Pour l'instant, il n'est malheureusement pas possible de payer votre commande ultérieurement avec Riverty.
C'est parce que l'adresse que vous avez saisie est incorrecte ou invalide.
Remplissez l'adresse correcte et réessayez."
    ],
    '43' => [
        'message' => 'The order amount is too high',
        'description' =>
"Pour l'instant, il n'est malheureusement pas possible de payer votre commande ultérieurement avec Riverty. Le montant total de votre commande est trop élevé.
Pour plus d'informations, veuillez contacter le service clientèle d'Riverty. Les coordonnées et les réponses aux questions fréquentes sont disponibles à l'adresse https://my.riverty.com/nl-be.
Nous vous conseillons de commander pour un montant inférieur ou de compléter votre commande avec un autre mode de paiement."
    ],
    '47' => [
        'message' => 'The order amount is too low',
        'description' =>
"Pour l'instant, il n'est malheureusement pas possible de payer votre commande ultérieurement avec Riverty. Le montant total de votre commande est trop faible.
Pour plus d'informations, veuillez contacter le service clientèle d'Riverty. Les coordonnées et les réponses aux questions fréquentes sont disponibles à l'adresse https://my.riverty.com/nl-be.
Nous vous conseillons de commander pour un montant supérieur ou de compléter votre commande avec un autre mode de paiement."
    ],
    '71' => [
        'message' => 'Customer has no valid company data',
        'description' =>
"Pour l'instant, il n'est malheureusement pas possible de payer votre commande ultérieurement avec Riverty. Ceci est dû à des informations invalides/incorrectes dans la combinaison des données de l'entreprise et du numéro de la Chambre de Commerce.
Pour plus d'informations, veuillez contacter le service clientèle d'Riverty. Les coordonnées et les réponses aux questions fréquentes sont disponibles à l'adresse https://my.riverty.com/nl-be.
Nous vous conseillons de commander pour un montant supérieur ou de compléter votre commande avec un autre mode de paiement."
    ],
    'fallback' => [
        'message' => 'General rejection',
        'description' =>
"Pour l'instant, il n'est malheureusement pas possible de payer votre commande ultérieurement avec Riverty. Il peut y avoir plusieurs raisons à cela.
Pour plus d'informations, veuillez contacter le service clientèle d'Riverty. Les coordonnées et les réponses aux questions fréquentes sont disponibles à l'adresse https://my.riverty.com/nl-be.
Cependant, il est possible de compléter votre commande avec un autre mode de paiement."
    ],
];
