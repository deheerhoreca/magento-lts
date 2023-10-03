<?php
 /**
 * Copyright (c) 2020 arvato Finance B.V.
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
 * @copyright   Copyright (c) 2020 arvato Finance B.V.
 */

namespace Afterpay;

use Afterpay;

/**
 * Function for cleaning phone numbers to correct data depending on country
 *
 * @param string $phoneNumber
 * @param string $country
 *
 * @return string $phoneNumber
 */

function cleanphone($phoneNumber, $country = 'NL')
{
    // Replace + with 00
    $phoneNumber = str_replace('+', '00', $phoneNumber);
    // Remove (0) because output is international format
    $phoneNumber = str_replace('(0)', '', $phoneNumber);
    // Only numbers
    $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
    // Country specific checks
    if ($country == 'NL') {
        if (
            strlen($phoneNumber) == '10'
            && substr($phoneNumber, 0, 3) != '0031'
            && substr($phoneNumber, 0, 1) == '0'
        ) {
            $phoneNumber = '0031' . substr($phoneNumber, -9);
        } elseif (strlen($phoneNumber) == '13' && substr($phoneNumber, 0, 3) == '0031') {
            $phoneNumber = '0031' . substr($phoneNumber, -9);
        }
    } elseif ($country == 'BE') {
        // Land lines
        if (
            strlen($phoneNumber) == '9'
            && substr($phoneNumber, 0, 3) != '0032'
            && substr($phoneNumber, 0, 1) == '0'
        ) {
            $phoneNumber = '0032' . substr($phoneNumber, -8);
        } elseif (strlen($phoneNumber) == '12' && substr($phoneNumber, 0, 3) == '0032') {
            $phoneNumber = '0032' . substr($phoneNumber, -8);
        }
        // Mobile lines
        if (
            strlen($phoneNumber) == '10'
            && substr($phoneNumber, 0, 3) != '0032'
            && substr($phoneNumber, 0, 1) == '0'
        ) {
            $phoneNumber = '0032' . substr($phoneNumber, -9);
        } elseif (strlen($phoneNumber) == '13' && substr($phoneNumber, 0, 3) == '0032') {
            $phoneNumber = '0032' . substr($phoneNumber, -9);
        }
    }

    return $phoneNumber;
}

/**
 * Check validation error and give back readable error message
 *
 * @param string $failure
 * @param string $fieldName
 * @param string $language
 *
 * @return array|string
 */
function check_validation_error($failure, $fieldName = '', $language = 'nl')
{
    // Belgium has a different buildup of the failure message
    if (in_array($failure, array('field.invalid', 'field.missing'))) {
        $oldFailure = explode('.', $failure);
        // In Belgium person is ReferencePerson, so replace
        $fieldName = str_replace('referencePerson', 'person', $fieldName);
        // In Belgium phonenumber1 is onder person, so replace
        $fieldName = str_replace('person.phonenumber1', 'phonenumber1', $fieldName);
        $fieldName = str_replace('person.phonenumber2', 'phonenumber2', $fieldName);

        $field_failure = $oldFailure[0] . '.' . $fieldName . '.' . $oldFailure[1];
    } else {
        $field_failure = $failure;
    }

    $translationFile = 'ValidationError';
    return Afterpay\lang($field_failure, $translationFile, $language);
}

/**
 * Check rejection error and give back readable error message
 *
 * @param string $rejection_code
 * @param string $language
 *
 * @return array|string
 */
function check_rejection_error($rejection_code, $language = 'nl')
{
    $translationFile = 'RejectionError';
    return Afterpay\lang($rejection_code, $translationFile, $language);
}

/**
 * Check technical error and give back readable error message
 *
 * @param string $field_failure
 *
 * @return array
 */
function check_technical_error($field_failure, $language = 'nl')
{
    $translationFile = 'TechnicalError';
    return Afterpay\lang($field_failure, $translationFile, $language);
}

/**
 * @param string $fieldKey
 * @param string $translationFile
 * @param string $language
 *
 * @return array|string
 */
function lang($fieldKey, $translationFile, $language = 'nl')
{
    $translationFilePath = __DIR__ . '/lang/' . $language . '/' . $translationFile . '.php';
    if (file_exists($translationFilePath)) {
        $langArray = include($translationFilePath);
        if (array_key_exists($fieldKey, $langArray)) {
            return $langArray[$fieldKey];
        } else {
            try {
                return $langArray['fallback'];
            } catch (\Exception $e) {
                // todo: log it some where
            }
        }
    }
}

/**
 * @param array $arrayOne
 * @param array $arrayTwo
 *
 * @return array
 */
function arrayRecursiveDiff($arrayOne, $arrayTwo)
{
    $diffedArray = array();
    foreach ($arrayOne as $key => $value) {
        if (array_key_exists($key, $arrayTwo)) {
            if (is_array($value)) {
                $recursiveDiff = Afterpay\arrayRecursiveDiff($value, $arrayTwo[$key]);
                if (count($recursiveDiff)) {
                    $diffedArray[$key] = $recursiveDiff;
                }
            } else {
                if ($value != $arrayTwo[$key]) {
                    $diffedArray[$key] = $value;
                }
            }
        } else {
            $diffedArray[$key] = $value;
        }
    }
    return $diffedArray;
}

/**
 * @param $price
 *
 * @return number|string
 */
function convertPrice($price) {
    // Check if price is negative
    $priceIsNegative = false;
    if( $price < 0 ) {
        $priceIsNegative = true;
    }
    $price = abs($price);
    if( $priceIsNegative ) {
        $price = $price * -1;
    }
    $price = number_format(
        $price,
        RestClient::DECIMALS,
        RestClient::DEC_POINT,
        RestClient::THOUSANDS_SEP
    );
    return $price;
}

/**
 * Calculate vat percentage based on totalamount and vatamount
 *
 * @param int $priceInclVat
 * @param int $vatAmount
 *
 * @return int $vatPercentage
 */
function calculateVatPercentage($priceInclVat, $vatAmount) {
    // Check if values are zero, then return zero. Otherwise there will be issues dividing by zero
    if ( $priceInclVat == 0 && $vatAmount == 0 ) return 0;
    $vatPercentage = 0;
    $priceExclVat = $priceInclVat - $vatAmount;
    $onePercentage = $priceExclVat / 100;
    $vatPercentage = $vatAmount / $onePercentage;
    return round($vatPercentage);
}

/**
 * Calculate vat amount based on totalamount and vat percentage
 *
 * @param int $priceInclVat
 * @param int $vatPercentage
 *
 * @return float $vatAmount
 */
function calculateVatAmount($priceInclVat, $vatPercentage) {
    $vatAmount = 0;
    $priceExclVat = ( $priceInclVat / ( $vatPercentage + 100 ) ) * 100;
    $vatAmount = $priceInclVat - $priceExclVat;
    return round( $vatAmount, 2 );
}

/**
 * Function for cleaning texts and filtering out special characters.
 *
 * @param string $text
 *
 * @return string $text
 */

function cleanText($text)
{
    // Replace special character with normal characters.
    $replace = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae',
        'Ä' => 'A', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae',
        'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D',
        'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E',
        'Ę' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G',
        'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I',
        'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I',
        'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K',
        'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N',
        'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
        'Ö' => 'Oe', 'Ö' => 'Oe', 'Ø' => 'O', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O',
        'Œ' => 'OE', 'Ŕ' => 'R', 'Ř' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S',
        'Ş' => 'S', 'Ŝ' => 'S', 'Ș' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T',
        'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ū' => 'U',
        'Ü' => 'Ue', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U',
        'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z',
        'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
        'ä' => 'ae', 'ä' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a',
        'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c',
        'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e',
        'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e',
        'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h',
        'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i',
        'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j',
        'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l',
        'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n',
        'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe',
        'ö' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe',
        'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u',
        'û' => 'u', 'ü' => 'ue', 'ū' => 'u', 'ü' => 'ue', 'ů' => 'u', 'ű' => 'u',
        'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y',
        'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss',
        'ſ' => 'ss', 'ый' => 'iy', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G',
        'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I',
        'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F',
        'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '',
        'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a',
        'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l',
        'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
        'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e',
        'ю' => 'yu', 'я' => 'ya'
    ];
    $text = str_replace(array_keys($replace), $replace, $text);

    // Filter out any other characters and leave only A-Z, a-z, spaces and dashes.
    $text = preg_replace( "/[^A-Za-z0-9\s\-]/", '', $text );

    return $text;
}