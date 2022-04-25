<?php
/**
 * Copyright (c) 2011-2020  arvato Finance B.V.
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
 * @category    AfterPay
 * @package     Afterpay_Afterpay
 * @copyright   Copyright (c) 2011-2020 arvato Finance B.V.
 */

class Afterpay_Afterpay_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function sendDebugEmail($email)
    {
        $recipients = explode(',', Mage::getStoreConfig('afterpay/afterpay_general/debug_mail', Mage::app()->getStore()->getStoreId()));
        foreach ($recipients as $recipient) {
            $mail = Mage::getModel('core/email');
            $mail->setToName('AfterPay Debug Recipient');
            $mail->setToEmail($recipient);
            $mail->setBody($email);
            $mail->setSubject('Afterpay Debug E-mail');
            $mail->setFromEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
            $mail->setFromName(Mage::getStoreConfig('trans_email/ident_general/name'));
            $mail->setType('text');
            $mail->send();
        }
    }

    public function isAdmin()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return true;
        }
        if (Mage::getDesign()->getArea() == 'adminhtml') {
            return true;
        }
        return false;
    }

    public function getAfterPayPaymentMethods()
    {
        $array = array(
            'afterpay_nl_digital_invoice',
            'afterpay_nl_direct_debit',
            'afterpay_nl_business',
            'afterpay_nl_digital_invoice_rest',
            'afterpay_nl_direct_debit_rest',
            'afterpay_nl_business_rest',
            'afterpay_be_digital_invoice',
            'afterpay_be_digital_invoice_rest',
            'afterpay_de_digital_invoice',
            'afterpay_at_digital_invoice',
            'afterpay_ch_digital_invoice',
            'afterpay_se_digital_invoice',
            'afterpay_dk_digital_invoice',
            'afterpay_fi_digital_invoice',
            'afterpay_no_digital_invoice',
            'afterpay_de_installment_payment',
            'portfolio_a',
            'portfolio_b',
            'portfolio_c',
            'portfolio_d',
            'portfolio_e',
            'portfolio_f'
            // 'portfolio_k',
            // 'portfolio_l',
        );
        
        return $array;
    }

    public function isEnterprise()
    {
        return (bool) Mage::getConfig()->getModuleConfig("Enterprise_Enterprise")->version;
    }

    public function log($message, $force = false)
    {
        Mage::log($message, Zend_Log::DEBUG, 'Afterpay_AfterPay.log', $force);
    }

    public function logException($exception)
    {
        if ($exception instanceof Exception) {
            Mage::log($exception->getMessage(), Zend_Log::ERR, 'Afterpay_AfterPay_Exception.log', true);
            Mage::log($exception->getTraceAsString(), Zend_Log::ERR, 'Afterpay_AfterPay_Exception.log', true);
        } else {
            Mage::log($exception, Zend_Log::ERR, 'Afterpay_AfterPay_Exception.log', true);
        }
    }

    public function getTaxClass($percentage)
    {
        if ($percentage < 5) {
            return '4';
        } elseif ($percentage < 20) {
            return '2';
        } else {
            return '1';
        }
    }

    public function getTaxClassByAmounts($price, $tax)
    {
        $priceExTax = $price - $tax;
        $onePercent = $priceExTax / 100;
        $taxPercentage = round($tax / $onePercent);

        return Mage::helper('afterpay')->getTaxClass($taxPercentage);
    }

    /**
     * Check validation error and give back readable error message
     *
     * @param string        $field_failure
     *
     * @return string
     */
    public function checkValidationError($failure, $language = 'nl')
    {
        // Belgium has a different buildup of the failure message
        if (in_array($failure->failure, array('field.invalid', 'field.missing'))) {
            $oldFailure = explode('.', $failure->failure);

            // In Belgium person is ReferencePerson, so replace
            $failure->fieldname = str_replace('referencePerson', 'person', $failure->fieldname);

            // In Belgium phonenumber1 is onder person, so replace
            $failure->fieldname = str_replace('person.phonenumber1', 'phonenumber1', $failure->fieldname);
            $failure->fieldname = str_replace('person.phonenumber2', 'phonenumber2', $failure->fieldname);

            // In Belgium shipto city is afleveradres.plaats
            $failure->fieldname = str_replace('afleveradres.plaats', 'shipto.city', $failure->fieldname);

            // In Belgium some fields are not lowercase
            $failure->fieldname = strtolower($failure->fieldname);

            $field_failure = $oldFailure[0] . '.' . $failure->fieldname . '.' . $oldFailure[1];
        } else {
            $field_failure = $failure->failure;
        }

        // Set language for field failure
        $field_failure = $language . '.' . $field_failure;

        switch ($field_failure) {
            case 'en.field.unknown.invalid':
                return 'An unknown field is invalid, please contact our customer service.';
                break;
            case 'en.field.shipto.person.initials.missing':
                return 'The initials of the shipping address are missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.person.initials.invalid':
                return 'The initials of the shipping address are invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.billto.person.initials.missing':
                return 'The initials of the billing address are missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.person.initials.invalid':
                return 'The initials of the billing address are invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.shipto.person.lastname.missing':
                return 'The last name of the shipping address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.person.lastname.invalid':
                return 'The last name of the shipping address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.billto.person.lastname.missing':
                return 'The last name of the billing address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.person.lastname.invalid':
                return 'The last name of the billing address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.city.missing':
                return 'The city of the billing address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.city.invalid':
                return 'The city of the billing address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.shipto.city.missing':
                return 'The city of the shipping address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.city.invalid':
                return 'The city of the shipping address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.billto.housenumber.missing':
                return 'The house number of the billing address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.housenumber.invalid':
                return 'The house number of the billing address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.shipto.housenumber.missing':
                return 'The house number of the shipping address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.housenumber.invalid':
                return 'The house number of the shipping address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.billto.postalcode.missing':
                return 'The postalcode of the billing address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.postalcode.invalid':
                return 'The postalcode of the billing address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.shipto.postalcode.missing':
                return 'The postalcode of the shipping address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.postalcode.invalid':
                return 'The postalcode of the shipping address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.person.gender.missing':
                return 'The gender of the shipping address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.person.gender.invalid':
                return 'The gender of the shipping address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.billto.person.gender.missing':
                return 'The gender of the billing address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.person.gender.invalid':
                return 'The gender of the billing address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.housenumberaddition.missing':
                return 'The house number addition of the billing address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.housenumberaddition.invalid':
                return 'The house number addition of the billing address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.shipto.housenumberaddition.missing':
                return 'The house number addition of the shipping address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.housenumberaddition.invalid':
                return 'The house number addition of the shipping address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.billto.phonenumber1.missing':
                return 'The fixed line and/or mobile number is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.phonenumber1.invalid':
                return 'The fixed line and/or mobile number is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.phonenumber2.invalid':
                return 'The fixed line and/or mobile number is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.shipto.person.emailaddress.missing':
                return 'The email address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.person.emailaddress.invalid':
                return 'The email address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.billto.person.emailaddress.missing':
                return 'The email address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.person.emailaddress.invalid':
                return 'The email address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.shipto.person.dateofbirth.missing':
                return 'The date of birth is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.person.dateofbirth.invalid':
                return 'The date of birth is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.billto.person.dateofbirth.missing':
                return 'The date of birth is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.person.dateofbirth.invalid':
                return 'The date of birth is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.isocountrycode.missing':
                return 'The country code of the billing address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.isocountrycode.invalid':
                return 'The country code of the billing address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.shipto.isocountrycode.missing':
                return 'The country code of the shipping address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.isocountrycode.invalid':
                return 'The country code of the shipping address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.person.prefix.missing':
                return 'The prefix of the shipping address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.person.prefix.invalid':
                return 'The prefix of the shipping address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.billto.person.prefix.missing':
                return 'The prefix of the billing address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.person.prefix.invalid':
                return 'The prefix of the billing address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.isolanguagecode.missing':
                return 'The language of the billing address is missing. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.billto.isolanguagecode.invalid':
                return 'The language of the billing address is invalid. Please check your billing details or contact our customer service.';
                break;
            case 'en.field.shipto.isolanguagecode.missing':
                return 'The language of the shipping address is missing. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.shipto.isolanguagecode.invalid':
                return 'The language of the shipping address is invalid. Please check your shipping details or contact our customer service.';
                break;
            case 'en.field.ordernumber.missing':
                return 'The ordernumber is missing. Please contact our customer service.';
                break;
            case 'en.field.ordernumber.invalid':
                return 'The ordernumber is invalid. Please contact our customer service.';
                break;
            case 'en.field.ordernumber.exists':
                return 'The ordernumber already exists. Please contact our customer service.';
                break;
            case 'en.field.bankaccountnumber.missing':
                return 'The bankaccountnumber is missing. Please check your bankaccountnumber or contact our customer service.';
                break;
            case 'en.field.bankaccountnumber.invalid':
                return 'The bankaccountnumber is missing. Please check your bankaccountnumber or contact our customer service.';
                break;
            case 'en.field.currency.missing':
                return 'The currency is missing. Please contact our customer service.';
                break;
            case 'en.field.currency.invalid':
                return 'The currency is invalid. Please contact our customer service.';
                break;
            case 'en.field.orderline.missing':
                return 'The orderline is missing. Please contact our customer service.';
                break;
            case 'en.field.orderline.invalid':
                return 'The orderline is invalid. Please contact our customer service.';
                break;
            case 'en.field.totalorderamount.missing':
                return 'The total order amount is missing. Please contact our customer service.';
                break;
            case 'en.field.totalorderamount.invalid':
                return 'The total order amount is invalid. This is probably due to a rounding difference. Please contact our customer service.';
                break;
            case 'en.field.parenttransactionreference.missing':
                return 'The parent transaction reference is missing. Please contact our customer service.';
                break;
            case 'en.field.parenttransactionreference.invalid':
                return 'The parent transaction reference is invalid. Please contact our customer service.';
                break;
            case 'en.field.parenttransactionreference.exists':
                return 'The parent transaction reference already exists. Please contact our customer service.';
                break;
            case 'en.field.vat.missing':
                return 'The vat is missing. Please contact our customer service.';
                break;
            case 'en.field.vat.invalid':
                return 'The vat is invalid. Please contact our customer service.';
                break;
            case 'en.field.quantity.missing':
                return 'The quantity is missing. Please contact our customer service.';
                break;
            case 'en.field.quantity.invalid':
                return 'The quantity is invalid. Please contact our customer service.';
                break;
            case 'en.field.unitprice.missing':
                return 'The unitprice is missing. Please contact our customer service.';
                break;
            case 'en.field.unitprice.invalid':
                return 'The unitprice is invalid. Please contact our customer service.';
                break;
            case 'en.field.netunitprice.missing':
                return 'The netunitprice is missing. Please contact our customer service.';
                break;
            case 'en.field.netunitprice.invalid':
                return 'The netunitprice is invalid. Please contact our customer service.';
                break;
            case 'en.field.company.cocnumber.invalid':
                return 'The number of the Chamber of Commerce is incorrect. Please check your number or contact our customer service.';
                break;

            // Field failures in French
            case 'fr.field.unknown.invalid':
                return "Un champ de formulaire inconnu est invalide, veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.shipto.person.initials.missing':
                return "Les initiales de l'adresse de livraison ne sont pas présentes. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.initials.invalid':
                return "Les initiales de l'adresse de livraison ne sont pas valides. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.initials.missing':
                return "Les initiales de l'adresse de facturation ne sont pas présentes. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.initials.invalid':
                return "Les initiales de l'adresse de facturation sont invalides. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.lastname.missing':
                return "Le nom de famille de l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.lastname.invalid':
                return "Le nom de famille dans l'adresse de livraison n'est pas valide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.lastname.missing':
                return "Le nom de famille dans l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.lastname.invalid':
                return "Le nom de famille dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.city.missing':
                return "La ville dans l'adresse de facturation n'est pas présente. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.city.invalid':
                return "La ville dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.city.missing':
                return "La ville dans l'adresse de livraison n'est pas présente. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.city.invalid':
                return "La ville dans l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.housenumber.missing':
                return "Le numéro de rue dans l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.housenumber.invalid':
                return "Le numéro de rue indiqué dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.housenumber.missing':
                return "Le numéro de la maison dans l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.housenumber.invalid':
                return "Le numéro de rue indiqué dans l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.postalcode.missing':
                return "Le code postal de l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.postalcode.invalid':
                return "Le code postal de l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.postalcode.missing':
                return "Le code postal de l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.postalcode.invalid':
                return "Le code postal de l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.gender.missing':
                return "Le sexe dans l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.gender.invalid':
                return "Le sexe de l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.gender.missing':
                return "Le sexe dans l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.gender.invalid':
                return "Le sexe dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.housenumberaddition.missing':
                return "L'addition au numéro de rue dans l'adresse de facturation n'est pas disponible. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.housenumberaddition.invalid':
                return "L'addition au numéro de rue dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.housenumberaddition.missing':
                return "L'addition du numéro de rue dans l'adresse de livraison n'est pas disponible. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.housenumberaddition.invalid':
                return "L'addition au numéro de rue dans l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.phonenumber1.missing':
                return "Le numéro de téléphone fixe ou mobile n'est pas disponible. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.phonenumber1.invalid':
                return "Le numéro de téléphone fixe et / ou mobile est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.phonenumber2.invalid':
                return "Le numéro de téléphone fixe et / ou mobile est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.emailaddress.missing':
                return "L'adresse e-mail n'est pas disponible. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.emailaddress.invalid':
                return "L'adresse e-mail n'est pas valide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.emailaddress.missing':
                return "L'adresse e-mail n'est pas disponible. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.emailaddress.invalid':
                return "L'adresse e-mail n'est pas valide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.dateofbirth.missing':
                return "La date de naissance n'est pas disponible. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.dateofbirth.invalid':
                return "La date de naissance est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.dateofbirth.missing':
                return "La date de naissance n'est pas disponible. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.dateofbirth.invalid':
                return "La date de naissance est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.isocountrycode.missing':
                return "Le code pays dans l'adresse de facturation n'est pas présent. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.isocountrycode.invalid':
                return "Le code pays dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.isocountrycode.missing':
                return "Le code pays de l'adresse de livraison n'est pas présent. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.isocountrycode.invalid':
                return "Le code pays de l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.prefix.missing':
                return "La salutation dans l'adresse de livraison n'est pas présente. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.person.prefix.invalid':
                return "La salutation dans l'adresse de livraison est invalide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.prefix.missing':
                return "La salutation dans l'adresse de facturation n'est pas présente. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.person.prefix.invalid':
                return "La salutation dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.isolanguagecode.missing':
                return "La langue dans l'adresse de facturation n'est pas présente. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.billto.isolanguagecode.invalid':
                return "La langue dans l'adresse de facturation est invalide. Vérifiez vos informations de facturation ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.isolanguagecode.missing':
                return "La langue dans l'adresse de livraison n'est pas présente. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.shipto.isolanguagecode.invalid':
                return "La langue dans l'adresse de livraison n'est pas valide. Vérifiez vos informations d'expédition ou contactez notre service clientèle.";
                break;
            case 'fr.field.ordernumber.missing':
                return "Le numéro de commande n'est pas disponible. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.ordernumber.invalid':
                return "Le numéro de commande est invalide. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.ordernumber.exists':
                return "Le numéro de commande existe déjà et ne peut plus être transmis à AfterPay. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.bankaccountnumber.missing':
                return "Le numéro de compte bancaire n'est pas présent. Veuillez vérifier votre numéro de compte bancaire ou contacter notre service clientèle.";
                break;
            case 'fr.field.bankaccountnumber.invalid':
                return "Le numéro de compte bancaire n'est pas présent. Veuillez vérifier votre numéro de compte bancaire ou contacter notre service clientèle.";
                break;
            case 'fr.field.currency.missing':
                return "La monnaie n'est pas présente. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.currency.invalid':
                return "La monnaie est invalide. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.orderline.missing':
                return "La ligne de commande n'est pas présente. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.orderline.invalid':
                return "La ligne de commande est invalide. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.totalorderamount.missing':
                return "Le montant total n'est pas disponible. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.totalorderamount.invalid':
                return "Le montant total est invalide. Ceci est probablement dû à une différence d'arrondi. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.parenttransactionreference.missing':
                return "La référence à la transaction principale n'est pas présente. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.parenttransactionreference.invalid':
                return "La référence à la transaction principale n'est pas valide. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.parenttransactionreference.exists':
                return "La référence à la transaction principale existe déjà. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.vat.missing':
                return "La TVA n'est pas présente. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.vat.invalid':
                return "La TVA est invalide. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.quantity.missing':
                return "La quantité n'est pas présent. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.quantity.invalid':
                return "La quantité est invalide. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.unitprice.missing':
                return "Le prix unitaire n'est pas présent. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.unitprice.invalid':
                return "Le prix unitaire est invalide. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.netunitprice.missing':
                return "Le prix unitaire net n'est pas présent. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.netunitprice.invalid':
                return "Le prix unitaire net est invalide. Veuillez contacter notre service clientèle.";
                break;
            case 'fr.field.company.cocnumber.invalid':
                return "Le numéro de la chambre de commerce est incorrect. Vérifiez votre numéro de la chambre de commerce ou contactez notre service clientèle.";
                break;

            // Field failures in Dutch
            case 'nl.field.unknown.invalid':
                return 'Een onbekend veld is ongeldig, neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.initials.missing':
                return 'De initialen van het verzendadres zijn niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.initials.invalid':
                return 'De initialen van het verzendadres zijn ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.initials.missing':
                return 'De initialen van het factuuradres zijn niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.initials.invalid':
                return 'De initialen van het factuuradres zijn ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.lastname.missing':
                return 'De achternaam van het verzendadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.lastname.invalid':
                return 'De achternaam van het verzendadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.lastname.missing':
                return 'De achternaam van het factuuradres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.lastname.invalid':
                return 'De achternaam van het factuuradres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.city.missing':
                return 'De plaats van het factuuradres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.city.invalid':
                return 'De plaats van het factuuradres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.city.missing':
                return 'De plaats van het verzendadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.city.invalid':
                return 'De plaats van het verzendadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.housenumber.missing':
                return 'Het huisnummer van het factuuradres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.housenumber.invalid':
                return 'Het huisnummer van het factuuradres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.housenumber.missing':
                return 'Het huisnummer van het verzendadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.housenumber.invalid':
                return 'Het huisnummer van het verzendadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.postalcode.missing':
                return 'De postcode van het factuuradres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.postalcode.invalid':
                return 'De postcode van het factuuradres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.postalcode.missing':
                return 'De postcode van het verzendadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.postalcode.invalid':
                return 'De postcode van het verzendadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.gender.missing':
                return 'Het geslacht van het verzendadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.gender.invalid':
                return 'Het geslacht van het verzendadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.gender.missing':
                return 'Het geslacht van het factuuradres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.gender.invalid':
                return 'Het geslacht van het factuuradres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.housenumberaddition.missing':
                return 'De toevoeging op het huisnummer van het factuuradres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.housenumberaddition.invalid':
                return 'De toevoeging op het huisnummer van het factuuradres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.housenumberaddition.missing':
                return 'De toevoeging op het huisnummer van het verzendadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.housenumberaddition.invalid':
                return 'De toevoeging op het huisnummer van het verzendadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.phonenumber1.missing':
                return 'Het vaste en of mobiele telefoonnummer is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.phonenumber1.invalid':
                return 'Het vaste en of mobiele telefoonnummer is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.phonenumber2.invalid':
                return 'Het vaste en of mobiele telefoonnummer is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.emailaddress.missing':
                return 'Het e-mailadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.emailaddress.invalid':
                return 'Het e-mailadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.emailaddress.missing':
                return 'Het e-mailadres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.emailaddress.invalid':
                return 'Het e-mailadres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.dateofbirth.missing':
                return 'De geboortedatum is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.dateofbirth.invalid':
                return 'De geboortedatum is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.dateofbirth.missing':
                return 'De geboortedatum is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.dateofbirth.invalid':
                return 'De geboortedatum is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.isocountrycode.missing':
                return 'De landcode van het factuuradres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.isocountrycode.invalid':
                return 'De landcode van het factuuradres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.isocountrycode.missing':
                return 'De landcode van het verzendadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.isocountrycode.invalid':
                return 'De landcode van het verzendadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.prefix.missing':
                return 'De aanhef van het verzendadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.person.prefix.invalid':
                return 'De aanhef van het verzendadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.prefix.missing':
                return 'De aanhef van het factuuradres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.person.prefix.invalid':
                return 'De aanhef van het factuuradres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.isolanguagecode.missing':
                return 'De taal van het factuuradres is niet aanwezig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.billto.isolanguagecode.invalid':
                return 'De taal van het factuuradres is ongeldig. Controleer uw factuurgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.isolanguagecode.missing':
                return 'De taal van het verzendadres is niet aanwezig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.shipto.isolanguagecode.invalid':
                return 'De taal van het verzendadres is ongeldig. Controleer uw verzendgegevens of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.ordernumber.missing':
                return 'Het ordernummer is niet aanwezig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.ordernumber.invalid':
                return 'Het ordernummer is ongeldig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.ordernumber.exists':
                return 'Dit ordernummer bestaat al en kan niet nogmaals aan AfterPay worden doorgegeven. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.bankaccountnumber.missing':
                return 'Het bankrekeningnummer is niet aanwezig. Please check your bankaccountnumber of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.bankaccountnumber.invalid':
                return 'Het bankrekeningnummer is niet aanwezig. Please check your bankaccountnumber of neem contact op met onze klantenservice.';
                break;
            case 'nl.field.currency.missing':
                return 'De valuta is niet aanwezig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.currency.invalid':
                return 'De valuta is ongeldig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.orderline.missing':
                return 'De orderregel is niet aanwezig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.orderline.invalid':
                return 'De orderregel is ongeldig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.totalorderamount.missing':
                return 'Het totaalbedrag is niet aanwezig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.totalorderamount.invalid':
                return 'Het totaalbedrag is ongeldig. This is probably due to a rounding difference. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.parenttransactionreference.missing':
                return 'De referentie aan de hoofdtransactie is niet aanwezig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.parenttransactionreference.invalid':
                return 'De referentie aan de hoofdtransactie is ongeldig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.parenttransactionreference.exists':
                return 'De referentie aan de hoofdtransactie bestaat al. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.vat.missing':
                return 'De BTW is niet aanwezig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.vat.invalid':
                return 'De BTW is ongeldig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.quantity.missing':
                return 'Het aantal is niet aanwezig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.quantity.invalid':
                return 'Het aantal is ongeldig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.unitprice.missing':
                return 'De stuksprijs is niet aanwezig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.unitprice.invalid':
                return 'De stuksprijs is ongeldig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.netunitprice.missing':
                return 'De netto stuksprijs is niet aanwezig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.netunitprice.invalid':
                return 'De netto stuksprijs is ongeldig. Neem alstublieft contact op met onze klantenservice.';
                break;
            case 'nl.field.company.cocnumber.invalid':
                return 'Het KVK nummer is onjuist. Controleer uw KVK nummer of neem contact op met onze klantenservice.';
                break;
            default:
                return 'Een onbekend veld is ongeldig. Neem alstublieft contact op met onze klantenservice.';
                break;
        }
    }

    public function getAllowedIps() {
        $allowedIps = array();
        // Get string with allowed IPS from mip.afterpay.nl
        $url = 'https://mip.afterpay.nl/ips.php';
        $url_headers = @get_headers($url);

        // Check if url is live otherwise return empty array.
        if(!$url_headers || strpos($url_headers[0], '404')) {
            return $allowedIps;
        }

        $ips_crypted = file_get_contents($url);
        $allowedIpsCloud = json_decode($this->simple_sha_crypt($ips_crypted, 'd'));

        if(!is_array($allowedIpsCloud)) {
            return $allowedIps;
        }

        return $allowedIpsCloud;
    }

    /**
     * Encrypt and decrypt
     *
     * @param string $string string to be encrypted/decrypted
     * @param string $action what to do with this? e for encrypt, d for decrypt
     */
    private function simple_sha_crypt($string, $action = 'e') {
        $secret_key = 'C26f&*zq0R1@';
        $secret_iv = 'fT2c#b3^GoIT';

        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

        if( $action == 'e' ) {
            $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
        }
        else if( $action == 'd' ){
            $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        }

        return $output;
    }
}