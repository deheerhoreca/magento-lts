<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking / Onestepcheckout
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
 * @package 	Anowave_Ecosc
 * @copyright 	Copyright (c) 2017 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

class Anowave_Ecosc_Model_Observer extends Anowave_Ec_Model_Observer
{
	/**
	 * Onestepcheckout cart template
	 *
	 * @see Anowave_Ec_Model_Observer::getCart()
	 */
	protected function getCart(Mage_Checkout_Block_Cart $block)
	{
		return Mage::app()->getLayout()->createBlock('ec/track')->setTemplate('ecosc/cart.phtml')->setData(array('items' => $block->getItems(),'quote' => $block->getQuote()))->toHtml();
	}
	
	/**
	 * Augment append method and capture onestepcheckout block
	 *
	 * @see Anowave_Ec_Model_Observer::append()
	 */
	protected function append(Mage_Core_Block_Abstract $block)
	{
		$content = parent::append($block);
		
		if (!$content)
		{
			switch ($block->getType())
			{
				case 'opc/wrapper':
				case 'checkout/onepage':
				case 'onestepcheckout/checkout':
				case 'firecheckout/checkout':
					
					$data = array();
					
					/**
					 * Checkout specific data. Depends on different checkout solutions
					 */
					switch($block->getNameInLayout())
					{
						case 'es.checkout.container':
							break;
					}
					
					return $this->getCheckoutOnestep($data);
				default:
					/**
					 * OSC (Magestore)
					 */
					if ('onestepcheckout' === $block->getNameInLayout())
					{
						return $this->getCheckoutOnestep(array
						(
							'getter' => Mage::app()->getLayout()->createBlock('ecosc/getter')->setTemplate('ecosc/getter_magestore.phtml')->toHtml()
						));
					}
					break;
			}
			
			return null;
		}
		
		return $content;
	}
	
	/**
	 * Get checkout step
	 *
	 * @param [] $data
	 */
	protected function getCheckoutOnestep($data = array())
	{
		/**
		 * Default getter
		 */
		if (!isset($data['getter']))
		{
			$data['getter'] = Mage::app()->getLayout()->createBlock('ecosc/getter')->setTemplate('ecosc/getter.phtml')->toHtml();
		}

		return Mage::app()->getLayout()->createBlock('ec/track')->setData($data)->setTemplate('ecosc/checkout.phtml')->toHtml();
	}
	
	/**
	 * Modify checkout products collection
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function getCheckoutProducts(Varien_Event_Observer $observer)
	{
		/**
		 * @todo Modify checkout products collection if needs be
		 */
	}
}