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

$prefix_message = "Er is een fout opgetreden in het betaalverzoek naar Riverty: \n\n";

return [
    'field.unknown.invalid' => $prefix_message . 'Een onbekend veld is ongeldig, neem alstublieft contact op met onze klantenservice.',
    'field.shipto.person.initials.missing' => $prefix_message . 'De initialen van het verzendadres zijn niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.initials.invalid' => $prefix_message . 'De initialen van het verzendadres zijn ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.initials.missing' => $prefix_message . 'De initialen van het factuuradres zijn niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.initials.invalid' => $prefix_message . 'De initialen van het factuuradres zijn ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.lastname.missing' => $prefix_message . 'De achternaam van het verzendadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.lastname.invalid' => $prefix_message . 'De achternaam van het verzendadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.lastname.missing' => $prefix_message . 'De achternaam van het factuuradres is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.lastname.invalid' => $prefix_message . 'De achternaam van het factuuradres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.city.missing' => $prefix_message . 'De plaats van het factuuradres is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.city.invalid' => $prefix_message . 'De plaats van het factuuradres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.shipto.city.missing' => $prefix_message . 'De plaats van het verzendadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.city.invalid' => $prefix_message . 'De plaats van het verzendadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.billto.housenumber.missing' => $prefix_message . 'Het huisnummer van het factuuradres is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.housenumber.invalid' => $prefix_message . 'Het huisnummer van het factuuradres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.shipto.housenumber.missing' => $prefix_message . 'Het huisnummer van het verzendadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.housenumber.invalid' => $prefix_message . 'Het huisnummer van het verzendadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.billto.postalcode.missing' => $prefix_message . 'De postcode van het factuuradres is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.postalcode.invalid' => $prefix_message . 'De postcode van het factuuradres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.shipto.postalcode.missing' => $prefix_message . 'De postcode van het verzendadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.postalcode.invalid' => $prefix_message . 'De postcode van het verzendadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.gender.missing' => $prefix_message . 'Het geslacht van het verzendadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.gender.invalid' => $prefix_message . 'Het geslacht van het verzendadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.gender.missing' => $prefix_message . 'Het geslacht van het factuuradres is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.gender.invalid' => $prefix_message . 'Het geslacht van het factuuradres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.housenumberaddition.missing' => $prefix_message . 'De toevoeging op het huisnummer van het factuuradres is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.housenumberaddition.invalid' => $prefix_message . 'De toevoeging op het huisnummer van het factuuradres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.shipto.housenumberaddition.missing' => $prefix_message . 'De toevoeging op het huisnummer van het verzendadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.housenumberaddition.invalid' => $prefix_message . 'De toevoeging op het huisnummer van het verzendadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.billto.phonenumber1.missing' => $prefix_message . 'Het vaste en of mobiele telefoonnummer is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.phonenumber1.invalid' => $prefix_message . 'Het vaste en of mobiele telefoonnummer is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.phonenumber2.invalid' => $prefix_message . 'Het vaste en of mobiele telefoonnummer is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.emailaddress.missing' => $prefix_message . 'Het e-mailadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.emailaddress.invalid' => $prefix_message . 'Het e-mailadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.emailaddress.missing' => $prefix_message . 'Het e-mailadres is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.emailaddress.invalid' => $prefix_message . 'Het e-mailadres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.dateofbirth.missing' => $prefix_message . 'De geboortedatum is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.dateofbirth.invalid' => $prefix_message . 'De geboortedatum is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.dateofbirth.missing' => $prefix_message . 'De geboortedatum is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.dateofbirth.invalid' => $prefix_message . 'De geboortedatum is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.isocountrycode.missing' => $prefix_message . 'De landcode van het factuuradres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.isocountrycode.invalid' => $prefix_message . 'De landcode van het verzendadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.isocountrycode.missing' => $prefix_message . 'De landcode van het verzendadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.isocountrycode.invalid' => $prefix_message . 'De aanhef van het verzendadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.prefix.missing' => $prefix_message . 'De aanhef van het verzendadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.person.prefix.invalid' => $prefix_message . 'De aanhef van het factuuradres is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.prefix.missing' => $prefix_message . 'De aanhef van het factuuradres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.person.prefix.invalid' => $prefix_message . 'De taal van het factuuradres is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.isolanguagecode.missing' => $prefix_message . 'De taal van het factuuradres is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.billto.isolanguagecode.invalid' => $prefix_message . 'De taal van het verzendadres is niet aanwezig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.isolanguagecode.missing' => $prefix_message . 'De taal van het verzendadres is ongeldig.
                    Controleer uw verzendgegevens of neem contact op met onze klantenservice.',
    'field.shipto.isolanguagecode.invalid' => $prefix_message . 'Het ordernummer is niet aanwezig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.ordernumber.missing' => $prefix_message . 'Het ordernummer is niet aanwezig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.ordernumber.invalid' => $prefix_message . 'Het ordernummer is ongeldig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.ordernumber.exists' => $prefix_message . 'Het ordernumber bestaat al.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.bankaccountnumber.missing' => $prefix_message . 'Het bankrekeningnummer is niet aanwezig.
                    Controleer uw bankrekeningnummer of neem contact op met onze klantenservice.',
    'field.bankaccountnumber.invalid' => $prefix_message . 'Het bankrekeningnummer is ongeldig.
                    Controleer uw bankrekeningnummer of neem contact op met onze klantenservice.',
    'field.currency.missing' => $prefix_message . 'De valuta is niet aanwezig in de aanroep.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.currency.invalid' => $prefix_message . 'De valuta is ongeldig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.orderline.missing' => $prefix_message . 'De orderregel is niet aanwezig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.orderline.invalid' => $prefix_message . 'De orderregel is ongeldig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.totalorderamount.missing' => $prefix_message . 'Het totaalbedrag is niet aanwezig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.totalorderamount.invalid' => $prefix_message . 'Het totaalbedrag is ongeldig. Dit is waarschijnlijk een afrondingsverschil.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.parenttransactionreference.missing' => $prefix_message . 'De referentie aan de hoofdtransactie is niet aanwezig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.parenttransactionreference.invalid' => $prefix_message . 'De referentie aan de hoofdtransactie is ongeldig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.parenttransactionreference.exists' => $prefix_message . 'De referentie aan de hoofdtransactie bestaat al.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.vat.missing' => $prefix_message . 'De BTW is niet aanwezig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.vat.invalid' => $prefix_message . 'De BTW is ongeldig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.quantity.missing' => $prefix_message . 'Het aantal is niet aanwezig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.quantity.invalid' => $prefix_message . 'Het aantal is ongeldig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.unitprice.missing' => $prefix_message . 'De stuksprijs is niet aanwezig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.unitprice.invalid' => $prefix_message . 'De stuksprijs is ongeldig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.netunitprice.missing' => $prefix_message . 'De netto stuksprijs is niet aanwezig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.netunitprice.invalid' => $prefix_message . 'De netto stuksprijs is ongeldig.
                    Neem alstublieft contact op met onze klantenservice.',
    'field.company.cocnumber.invalid' => $prefix_message . 'Het nummer van de kamer van koophandel is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.company.cocnumber.missing' => $prefix_message . 'Het nummer van de kamer van koophandel is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.company.companyname.invalid' => $prefix_message . 'De bedrijfsnaam is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.company.companyname.missing' => $prefix_message . 'De bedrijfsnaam is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.company.department.invalid' => $prefix_message . 'De naam van de bedrijfsafdeling is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.company.department.missing' => $prefix_message . 'De naam van de bedrijfsafdeling is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.company.establishmentnumber.invalid' => $prefix_message . 'Het dossiernummer is ongeldig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'field.company.establishmentnumber.missing' => $prefix_message . 'Het dossiernummer is niet aanwezig.
                    Controleer uw factuurgegevens of neem contact op met onze klantenservice.',
    'invoicenumber.amount.limit' => 'De refund kan niet worden uitgevoerd omdat u probeert meer te refunden dan dat er op dit moment beschikbaar is. Controleer uw factuurgegevens of neem contact op met de klantenservice van Riverty.',
    'fallback' => $prefix_message . 'Een onbekend veld is ongeldig.
                    Neem alstublieft contact op met onze klantenservice.'
];