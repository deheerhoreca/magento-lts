<?php

class Magiecommerce_Faqs_Block_Frontend_Search extends Mage_Core_Block_Template
{
	protected $_faqCollection;


	protected function _prepareLayout()
    {
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->setTitle($this->htmlEscape($this->__(Mage::getStoreConfig('faqs/seo/faq_meta'))));
            $head->setKeywords(Mage::getStoreConfig('faqs/seo/faq_keywords'));
            $head->setDescription(Mage::getStoreConfig('faqs/seo/faq_description'));

        }
    }
     

    public function getSearchCollection($catId)
    {
       $currentStoreId = Mage::app()->getStore(true)->getId();
 
         if($this->getRequest()->getParam('searchid')){
            $cat_id = (int)$this->getRequest()->getParam('searchid');
            $currentStoreId = Mage::app()->getStore(true)->getId();
            $parentcatid = Mage::getModel('faqs/categories')->load($cat_id)->getParentcatid();
            if($parentcatid){

              $categoriesCollections = Mage::getModel('faqs/categories')->getCollection()
                                      ->addFieldToFilter('status','1')
                                      ->addFieldToFilter('store_id', array(array('finset' => $currentStoreId),array('finset' => '0')));
              $categoriesCollections->getSelect()->where("parentcatid = ".$parentcatid." or cat_id = ".$cat_id);
              return $categoriesCollections;
              
            } else {
             $categoriesCollections = Mage::getModel('faqs/categories')->getCollection()
                                      ->addFieldToFilter('status','1')
                                      ->addFieldToFilter('store_id', array(array('finset' => $currentStoreId),array('finset' => '0')));
              $categoriesCollections->getSelect()->where("parentcatid = ".$cat_id." or cat_id = ".$cat_id);
              return $categoriesCollections;
            }

        } else {
          $currentStoreId = Mage::app()->getStore(true)->getId();
          return Mage::getModel('faqs/categories')->getCollection()
                                    ->addFieldToFilter('status','1')
                                    ->addFieldToFilter('store_id', array(array('finset' => $currentStoreId),array('finset' => '0')));
       
        }
 
    }
    public function getFaqaAndAnswer($cat_id)
    {
      $searchfaqs=$this->getRequest()->getParam('search-faqs');
       $faqCollection = Mage::getModel('faqs/items')
                              ->getCollection()
                              ->addFieldToFilter('status','1')
                              ->addFieldToFilter('cat_id',$cat_id)
                              ->addFieldToFilter('store_id', array(array('finset' => $currentStoreId),array('finset' => '0')));
        $faqCollection->getSelect()->where("quetion like '%$searchfaqs%' OR answer like '%$searchfaqs%'");
        return  $faqCollection;
    }
}
