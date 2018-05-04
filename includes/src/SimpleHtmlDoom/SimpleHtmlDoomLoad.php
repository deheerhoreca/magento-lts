<?php

/**
 * Magestore
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magestore
 * @package     Magestore_Pdfinvoiceplus
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * 
 * @category    Magestore
 * @package     Magestore_Pdfinvoiceplus
 * @author      Magestore Developer
 */
/* Change by Zeus 04/12 */
if (!defined('DS')) {
    define( 'DS', DIRECTORY_SEPARATOR );
}
//define('DS', DIRECTORY_SEPARATOR);
/* end change */
include Mage::getBaseDir() . DS . 'lib' . DS . 'SimpleHtmlDoom' . DS . 'simple_html_dom.php';

class SimpleHtmlDoom_SimpleHtmlDoomLoad extends simple_html_dom
{
    //put your code here
}
