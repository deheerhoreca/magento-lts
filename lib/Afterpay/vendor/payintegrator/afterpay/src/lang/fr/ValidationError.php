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

$prefix_message = "Une erreur s'est produite dans la demande de paiement à Riverty: \n\n";

return [
    'field.unknown.invalid' => $prefix_message . "Un champ de formulaire inconnu est invalide, veuillez contacter notre service clientèle.",
    'field.shipto.person.initials.missing' => $prefix_message . "Les initiales de l'adresse de livraison ne sont pas présentes. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.person.initials.invalid' => $prefix_message . "Les initiales de l'adresse de livraison ne sont pas valides. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.billto.person.initials.missing' => $prefix_message . "Les initiales de l'adresse de facturation ne sont pas présentes. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.person.initials.invalid' => $prefix_message . "Les initiales de l'adresse de facturation sont invalides. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.shipto.person.lastname.missing' => $prefix_message . "Le nom de famille de l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.person.lastname.invalid' => $prefix_message . "Le nom de famille dans l'adresse de livraison n'est pas valide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.billto.person.lastname.missing' => $prefix_message . "Le nom de famille dans l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.person.lastname.invalid' => $prefix_message . "Le nom de famille dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.city.missing' => $prefix_message . "La ville dans l'adresse de facturation n'est pas présente. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.city.invalid' => $prefix_message . "La ville dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.shipto.city.missing' => $prefix_message . "La ville dans l'adresse de livraison n'est pas présente. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.city.invalid' => $prefix_message . "La ville dans l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.billto.housenumber.missing' => $prefix_message . "Le numéro de rue dans l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.housenumber.invalid' => $prefix_message . "Le numéro de rue indiqué dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.shipto.housenumber.missing' => $prefix_message . "Le numéro de la maison dans l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.housenumber.invalid' => $prefix_message . "Le numéro de rue indiqué dans l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.billto.postalcode.missing' => $prefix_message . "Le code postal de l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.postalcode.invalid' => $prefix_message . "Le code postal de l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.shipto.postalcode.missing' => $prefix_message . "Le code postal de l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.postalcode.invalid' => $prefix_message . "Le code postal de l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.person.gender.missing' => $prefix_message . "Le sexe dans l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.person.gender.invalid' => $prefix_message . "Le sexe de l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.billto.person.gender.missing' => $prefix_message . "Le sexe dans l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.person.gender.invalid' => $prefix_message . "Le sexe dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.housenumberaddition.missing' => $prefix_message . "L'addition au numéro de rue dans l'adresse de facturation n'est pas disponible. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.housenumberaddition.invalid' => $prefix_message . "L'addition au numéro de rue dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.shipto.housenumberaddition.missing' => $prefix_message . "L'addition du numéro de rue dans l'adresse de livraison n'est pas disponible. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.housenumberaddition.invalid' => $prefix_message . "L'addition au numéro de rue dans l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.billto.phonenumber1.missing' => $prefix_message . "Le numéro de téléphone fixe ou mobile n'est pas disponible. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.phonenumber1.invalid' => $prefix_message . "Le numéro de téléphone fixe et / ou mobile est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.phonenumber2.invalid' => $prefix_message . "Le numéro de téléphone fixe et / ou mobile est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.shipto.person.emailaddress.missing' => $prefix_message . "L'adresse e-mail n'est pas disponible. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.person.emailaddress.invalid' => $prefix_message . "L'adresse e-mail n'est pas valide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.billto.person.emailaddress.missing' => $prefix_message . "L'adresse e-mail n'est pas disponible. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.person.emailaddress.invalid' => $prefix_message . "L'adresse e-mail n'est pas valide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.shipto.person.dateofbirth.missing' => $prefix_message . "La date de naissance n'est pas disponible. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.person.dateofbirth.invalid' => $prefix_message . "La date de naissance est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.billto.person.dateofbirth.missing' => $prefix_message . "La date de naissance n'est pas disponible. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.person.dateofbirth.invalid' => $prefix_message . "La date de naissance est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.isocountrycode.missing' => $prefix_message . "Le code pays dans l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.isocountrycode.invalid' => $prefix_message . "Le code pays dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.shipto.isocountrycode.missing' => $prefix_message . "Le code pays de l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.isocountrycode.invalid' => $prefix_message . "Le code pays de l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.person.prefix.missing' => $prefix_message . "La salutation dans l'adresse de livraison n'est pas présente. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.person.prefix.invalid' => $prefix_message . "La salutation dans l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.billto.person.prefix.missing' => $prefix_message . "La salutation dans l'adresse de facturation n'est pas présente. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.person.prefix.invalid' => $prefix_message . "La salutation dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.isolanguagecode.missing' => $prefix_message . "La langue dans l'adresse de facturation n'est pas présente. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.billto.isolanguagecode.invalid' => $prefix_message . "La langue dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.",
    'field.shipto.isolanguagecode.missing' => $prefix_message . "La langue dans l'adresse de livraison n'est pas présente. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.shipto.isolanguagecode.invalid' => $prefix_message . "La langue dans l'adresse de livraison n'est pas valide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.",
    'field.ordernumber.missing' => $prefix_message . "Le numéro de commande n'est pas disponible. Veuillez contacter notre service clientèle.",
    'field.ordernumber.invalid' => $prefix_message . "Le numéro de commande est invalide. Veuillez contacter notre service clientèle.",
    'field.ordernumber.exists' => $prefix_message . "Le numéro de commande existe déjà et ne peut plus être transmis à Riverty. Veuillez contacter notre service clientèle.",
    'field.bankaccountnumber.missing' => $prefix_message . "Le numéro de compte bancaire n'est pas présent. Veuillez vérifier votre numéro de compte bancaire ou contacter notre service clientèle.",
    'field.bankaccountnumber.invalid' => $prefix_message . "Le numéro de compte bancaire n'est pas valide. Veuillez vérifier votre numéro de compte bancaire ou contacter notre service clientèle.",
    'field.currency.missing' => $prefix_message . "La monnaie n'est pas présente. Veuillez contacter notre service clientèle.",
    'field.currency.invalid' => $prefix_message . "La monnaie est invalide. Veuillez contacter notre service clientèle.",
    'field.orderline.missing' => $prefix_message . "La ligne de commande n'est pas présente. Veuillez contacter notre service clientèle.",
    'field.orderline.invalid' => $prefix_message . "La ligne de commande est invalide. Veuillez contacter notre service clientèle.",
    'field.totalorderamount.missing' => $prefix_message . "Le montant total n'est pas disponible. Veuillez contacter notre service clientèle.",
    'field.totalorderamount.invalid' => $prefix_message . "Le montant total est invalide. Ceci est probablement dû à une différence d'arrondi. Veuillez contacter notre service clientèle.",
    'field.parenttransactionreference.missing' => $prefix_message . "La référence à la transaction principale n'est pas présente. Veuillez contacter notre service clientèle.",
    'field.parenttransactionreference.invalid' => $prefix_message . "La référence à la transaction principale n'est pas valide. Veuillez contacter notre service clientèle.",
    'field.parenttransactionreference.exists' => $prefix_message . "La référence à la transaction principale existe déjà. Veuillez contacter notre service clientèle.",
    'field.vat.missing' => $prefix_message . "La TVA n'est pas présente. Veuillez contacter notre service clientèle.",
    'field.vat.invalid' => $prefix_message . "La TVA est invalide. Veuillez contacter notre service clientèle.",
    'field.quantity.missing' => $prefix_message . "La quantité n'est pas présent. Veuillez contacter notre service clientèle.",
    'field.quantity.invalid' => $prefix_message . "La quantité est invalide. Veuillez contacter notre service clientèle.",
    'field.unitprice.missing' => $prefix_message . "Le prix unitaire n'est pas présent. Veuillez contacter notre service clientèle.",
    'field.unitprice.invalid' => $prefix_message . "Le prix unitaire est invalide. Veuillez contacter notre service clientèle.",
    'field.netunitprice.missing' => $prefix_message . "Le prix unitaire net n'est pas présent. Veuillez contacter notre service clientèle.",
    'field.netunitprice.invalid' => $prefix_message . "Le prix unitaire net est invalide. Veuillez contacter notre service clientèle.",
    'invoicenumber.amount.limit' => 'Le remboursement ne peut pas être effectué car vous essayez de rembourser plus que ce qui est actuellement disponible. Vérifiez vos informations de facturation ou contactez le service clientèle Riverty.',
    'fallback' => $prefix_message . 'Un champ de formulaire inconnu est invalide, veuillez contacter notre service clientèle.'
];