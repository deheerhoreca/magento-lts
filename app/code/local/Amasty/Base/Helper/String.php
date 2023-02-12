<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Magento 1 Base Package
*/

/**
 * Class for security unserialize string (not support object)
 */
class Amasty_Base_Helper_String
{
    /**
     * @param $str
     * @param bool $useOurUnserialize
     * @return bool|mixed|null
     */
    public static function unserialize($str, $useOurUnserialize = false)
    {
        try {
            if (!$useOurUnserialize
                && @class_exists('Unserialize_Reader_ArrValue')
            ) {
                $reader = new Unserialize_Reader_ArrValue('data');
            } else {
                $reader = new Amasty_Unserialize_Reader_ArrValue('data');
            }

            $prevChar = null;
            for ($i = 0; $i < strlen($str); $i++) {
                $char = $str[$i];
                $result = $reader->read($char, $prevChar);
                if (!is_null($result)) {
                    return $result;
                }
                $prevChar = $char;
            }
        } catch (Exception $ex) {
            return false;
        }
    }
}
