<?php
/**
 * FAQ accordion for Magento

 */

/**
 * FAQ accordion for Magento
 *

 * Website: www.hiremagento.com 
 * Email: hiremagento@gmail.com
 */
class HM_Faq_IndexController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Displays the FAQ list.
	 */
	public function indexAction()
	{
		$this->loadLayout()->renderLayout();
	}
	
	/**
	 * Displays the current FAQ's detail view
	 */
	public function showAction()
	{
		$this->loadLayout()->renderLayout();
	}
}
