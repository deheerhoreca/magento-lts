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
        'message' => 'Het order bedrag is te hoog',
        'description' =>
'Op dit moment is het helaas niet mogelijk je bestelling achteraf te betalen met Riverty. Het totale bedrag van je
bestelling is te hoog. Voor meer informatie kun je contact opnemen met de klantenservice van Riverty. Kijk voor de
contactgegevens en antwoorden op veelgestelde vragen op https://my.riverty.com/nl-nl. We adviseren je om voor een lager bedrag 
te bestellen of je bestelling met een andere betaalmethode af te ronden.'
    ],
    '36' => [
        'message' => 'De consument heeft geen geldig email adres',
        'description' =>
'Op dit moment is het helaas niet mogelijk je bestelling achteraf te betalen met Riverty. Dit komt doordat je een
ongeldig e-mailadres hebt opgegeven. We adviseren je om een geldig e-mailadres op te geven en het opnieuw te proberen.'
    ],
    '40' => [
        'message' => 'De consument is onder 18 jaar oud',
        'description' =>
'Op dit moment is het helaas niet mogelijk je bestelling achteraf te betalen met Riverty. Dit komt omdat je nog niet
18 jaar of ouder bent. We adviseren je om je bestelling met een andere betaalmethode af te ronden.'
    ],
    '42' => [
        'message' => 'De consument heeft geen geldig adres',
        'description' =>
'Op dit moment is het helaas niet mogelijk je bestelling achteraf te betalen met Riverty. Dit komt doordat het adres
dat je hebt opgegeven onjuist of ongeldig is. Vul de juiste adresgegevens in en probeer het opnieuw.'
    ],
    '47' => [
        'message' => 'Het order bedrag is te laag',
        'description' =>
'Op dit moment is het helaas niet mogelijk je bestelling achteraf te betalen met Riverty. Het totale bedrag van je
bestelling is te laag. Voor meer informatie kun je contact opnemen met de klantenservice van Riverty. Kijk voor de
contactgegevens en antwoorden op veelgestelde vragen op https://my.riverty.com/nl-nl.We adviseren je om voor een hoger 
bedrag te bestellen of je bestelling met een andere betaalmethode af te ronden.'
    ],
    '71' => [
        'message' => 'De consument heeft geen geldige bedrijfsgegevens',
        'description' =>
'Op dit moment is het helaas niet mogelijk je bestelling achteraf te betalen met Riverty. Dit komt door
ongeldige/onjuiste informatie in de combinatie van de bedrijfsgegevens en het KVK-nummer. Voor meer informatie kun je
contact opnemen met de klantenservice van Riverty. Kijk voor de contactgegevens en antwoorden op veelgestelde vragen
op https://my.riverty.com/nl-nl. We adviseren je om de juiste gegevens in te vullen of je
bestelling met een andere betaalmethode af te ronden.'
    ],
    'fallback' => [
        'message' => 'Aanvraag komt niet in aanmerking voor Riverty',
        'description' =>
'Op dit moment is het helaas niet mogelijk om je bestelling achteraf te betalen met Riverty. Dit kan verschillende
redenen hebben. Voor meer informatie kun je contact opnemen met de klantenservice van Riverty. Kijk voor de
contactgegevens en antwoorden op veelgestelde vragen op https://my.riverty.com/nl-nl. We adviseren je om je bestelling 
met een andere betaalmethode af te ronden.'
    ],
];
