<?php
class Magiecommerce_Faqs_Model_Observer extends Mage_Core_Model_Session_Abstract
{
    public function systemConfigChangedfaqs(Varien_Event_Observer $observer)
    {
        $code = Mage::getSingleton('adminhtml/config_data')->getStore();
            $store_id = Mage::getModel('core/store')->load($code)->getId();
            $requestUrl =  Mage::getStoreConfig("faqs/faqs/faq_url",$store_id);
            $requestUrl = ($requestUrl) ? $requestUrl : 'faqs';
    		if($code){ $url_id_path = 'faqs-'.$code; } else { $url_id_path = 'faqs'; }
            $results = Mage::getModel('core/url_rewrite')->loadByIdPath($url_id_path);
            $url = 'faqs';
            $store = Mage::app()->getStore();
            if($results->geturlRewriteId()) {
                Mage::getModel('core/url_rewrite')->loadByIdPath($url_id_path)
                    ->setIsSystem(0)
                    ->setStoreId($store_id)
                    ->setRequestPath($requestUrl)
                    ->setTargetPath($url)
                    ->save();
            } else {
                Mage::getModel('core/url_rewrite')
                    ->setIsSystem(0)
                    ->setStoreId($store_id)
                    ->setIdPath($url_id_path)
                    ->setRequestPath($requestUrl)
                    ->setTargetPath($url)
                    ->save();
            }
    	$results = Mage::getModel('core/url_rewrite')->loadByIdPath('faqs');
    	$requestUrl = Mage::getStoreConfig('faqs/faqs/faq_url');

    	$url = 'faqs';

    	if($results->geturlRewriteId()) {
    		Mage::getModel('core/url_rewrite')->loadByIdPath('faqs')
    		    ->setIsSystem(0)
    			->setRequestPath($requestUrl)
    			->setTargetPath($url)
    			->save();
    	} else {
    		Mage::getModel('core/url_rewrite')
    			->setIsSystem(0)
    			->setIdPath('faqs')
    			->setRequestPath($requestUrl)
    			->setTargetPath($url)
    			->save();
    	}
    }
}
?>