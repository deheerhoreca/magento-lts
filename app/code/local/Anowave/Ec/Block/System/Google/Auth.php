<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2020 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

class Anowave_Ec_Block_System_Google_Auth extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	/**
	 * Use API 
	 * 
	 * @var boolean
	 */
	private $use = true;
	
	/**
	 * Google Tag Manager API
	 * 
	 * @var Anowave_Ec_Model_Api
	 */
	private $api = null;

	/**
	 * Constructor 
	 * 
	 * {@inheritDoc}
	 * @see Mage_Core_Block_Template::_construct()
	 */
    protected function _construct()
    {
    	if ($this->use)
    	{
    		set_time_limit(360);
    		
    		set_include_path(get_include_path() . PATH_SEPARATOR . '/lib/Google');
    		
    		/**
    		 * Set custom template
    		 */
    		$this->setTemplate('ec/system/google/auth.phtml');
    	}
    }
    
    public function getApi()
    {
    	if (!$this->api)
    	{
    		$this->api = Mage::getModel('ec/api');
    	}
    	
    	return $this->api;
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setNamePrefix($element->getName())->setHtmlId($element->getHtmlId());
        
        return $this->_toHtml();
    }
}