<?php

/**
 * Class Shopworks_Billink_Helper_AddressParser
 */
class Shopworks_Billink_Helper_AddressParser
{
    /**
     * @param $addressString
     * @return Shopworks_Billink_Helper_AddressParserOutput
     */
    public function parse($addressString)
    {
        $result = new Shopworks_Billink_Helper_AddressParserOutput();

        //Remove line breaks
        $addressString = trim(str_replace(array("\n", "\r"), ' ', $addressString));

        //Reverse the address, because we cannot make assumptions on the streetname, but we can do that
        //with the housenumber and housenumber extension.
        $addressString = strrev($addressString);

        $housenumberExtensionRegex = '[^\s]*[a-zA-Z]+|[a-zA-Z]*';
        $houseNumberRegex = '[0-9\-]+';
        $streetRegex = '.+';

        $regexPattern = '/^('.$housenumberExtensionRegex.')(\s*)('.$houseNumberRegex.')('.$streetRegex.')$/';
        $matches = array();
        preg_match_all($regexPattern, $addressString, $matches);

        $result->streetName = isset($matches[4][0]) ? strrev(trim($matches[4][0])) : '';
        $result->houseNumber = isset($matches[3][0]) ? strrev(trim($matches[3][0])) : '';
        $result->houseNumberExtension = isset($matches[1][0]) ? strrev(trim($matches[1][0])) : '';

        return $result;
    }

}

/**
 * Class Shopworks_Billink_Helper_AddressParserOutput
 */
class Shopworks_Billink_Helper_AddressParserOutput
{
    public $streetName = '';
    public $houseNumber = '';
    public $houseNumberExtension = '';
}