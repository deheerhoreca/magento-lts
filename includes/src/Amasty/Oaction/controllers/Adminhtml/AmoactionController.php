<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Adminhtml_AmoactionController extends Mage_Adminhtml_Controller_Action
{
    public function doAction()
    {
        $ids = $this->getRequest()->getParam('order_ids');
        $val = trim($this->getRequest()->getParam('amoaction_value'));
        $commandType = trim($this->getRequest()->getParam('command'));
		
		// pre-save url here as some action may change current store
        // in multi-store env it can result in admin url change		
	    $url = $this->getUrl('adminhtml/sales_order');

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/mass_order_actions/' . $commandType)) {
            try {
                $command = Amasty_Oaction_Model_Command_Abstract::factory($commandType);

                $success = $command->execute($ids, $val);
                if ($success) {
                     $this->_getSession()->addSuccess($success);
                }

                // show non critical errors to the user
                foreach ($command->getErrors() as $err) {
                     $this->_getSession()->addError($err);
                }
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Error: %s', $e->getMessage()));
            }
        } else {
            $this->_getSession()->addError($this->__('Access denied.'));
        }
        
        if ($command->hasResponse()) {
            $this->_prepareDownloadResponse(
                $command->getResponseName(), 
                $command->getResponseBody(),
                $command->getResponsetype()
            );            
        } else {
			$this->_getSession()->setIsUrlNotice($this->getFlag('', self::FLAG_IS_URLS_CHECKED));
			$this->getResponse()->setRedirect($url);
        }
		
        return $this;        
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/mass_order_actions');
    }
}