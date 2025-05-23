<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_Api2
 */

/**
 * Webservice API2 renderer of JSON type model
 *
 * @package    Mage_Api2
 */
class Mage_Api2_Model_Renderer_Json implements Mage_Api2_Model_Renderer_Interface
{
    /**
     * Adapter mime type
     */
    public const MIME_TYPE = 'application/json';

    /**
     * Convert Array to JSON
     *
     * @param array|object $data
     * @return string
     */
    public function render($data)
    {
        return Zend_Json::encode($data);
    }

    /**
     * Get MIME type generated by renderer
     *
     * @return string
     */
    public function getMimeType()
    {
        return self::MIME_TYPE;
    }
}
