<?php

class TM_RichSnippets_Model_Observer {
	public function addSnippetsToReviewForm(Varien_Event_Observer $observer)
	{
        if ('TM_EasyTabs_Block_Tab_Product_Review' == get_class($observer->getBlock())) {
            $normalOutput    = $observer->getTransport()->getHtml();
            $richBlock = $observer->getBlock()->getLayout()->createBlock('richsnippets/product')->toHtml();
            $observer->getTransport()->setHtml($normalOutput  . $richBlock);
        } elseif ('Mage_Catalog_Block_Product_View_Attributes' == get_class($observer->getBlock())) {
            $normalOutput    = $observer->getTransport()->getHtml();
            $richBlock = $observer->getBlock()->getLayout()->createBlock('richsnippets/product')->toHtml();
            $observer->getTransport()->setHtml($normalOutput  . $richBlock);
        }
	}
}