<?php
class Magiecommerce_Faqs_Helper_Data extends Mage_Core_Helper_Data
{
	public function getFaqsUrl() {
            if(Mage::getStoreConfig('faqs/faqs/faq_url')):
		return Mage::getStoreConfig('faqs/faqs/faq_url');
            else:
                return $this->_getUrl('faqs');
            endif;
    }
    public function getFaqsLabel() {
            if(Mage::getStoreConfig('faqs/faqs/faq_lable')):
		return Mage::getStoreConfig('faqs/faqs/faq_lable');
            else:
                return "faqs";
            endif;
    }

}