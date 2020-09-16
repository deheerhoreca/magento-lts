<?php

class Magiecommerce_Faqs_Block_Frontend_View extends Mage_Core_Block_Template {

    protected $categoryid;

    public function __construct() {
        $this->getRequest()->getParam('id');
    }

    protected function _prepareLayout() {
        if ($breadcrumbs = $this->getLayout()->getBlock('breadcrumbs')) {
            $requestLable = Mage::getStoreConfig('faqs/faqs/faq_lable');
            if($requestLable)
            {$requestLable = Mage::getStoreConfig('faqs/faqs/faq_lable');}
            else
            {$requestLable = "faqs";}
            if($requestUrl)
            {$requestUrl = Mage::getStoreConfig('faqs/faqs/faq_url');}
            else
            {$requestUrl = "faqs";}
            $_faqCollection = Mage::getModel('faqs/categories')->load($this->getRequest()->getParam('id'));
            $breadcrumbs->addCrumb('home', array('label' => 'Home', 'title' => 'Go to Home Page', 'link' => Mage::getBaseUrl()));
            $breadcrumbs->addCrumb('faqs', array('label' => $requestLable, 'title' => $requestLable, 'link' => Mage::getBaseUrl() . $requestUrl));
            $breadcrumbs->addCrumb('category', array('label' => $_faqCollection->getCatName(), 'title' => $_faqCollection->getCatName()));
        }
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->setTitle($this->htmlEscape($_faqCollection->getMetatitle()));
            $head->setKeywords($_faqCollection->getMetakeyword());
            $head->setDescription($_faqCollection->getMetadescription());
        }
    }

    public function getFaqCollection($catId) {
        $currentStoreId = Mage::app()->getStore(true)->getId();
        $faqCollection = Mage::getModel('faqs/items')
                ->getCollection()
                ->addFieldToFilter('cat_id', $catId)
                ->addFieldToFilter('status', '1')
                ->addFieldToFilter('store_id', array(
            array('finset' => $currentStoreId),
            array('finset' => '0'))
                )
        ;
        $searchfaqs = $this->getRequest()->getParam('search-faqs');
        if ($searchfaqs != NULL) {
            $faqCollection->getSelect()->where("quetion like '%$searchfaqs%' OR answer like '%$searchfaqs%'");
        }
        $faqCollection->addOrder('itemsortorder', 'ASC');
        return $faqCollection->getData();
    }

}
