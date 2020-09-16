<?php

class Magiecommerce_Faqs_Block_Frontend_List extends Mage_Core_Block_Template
{
	protected $_faqCollection;


	protected function _prepareLayout()
    {

             /**
             * Set Meta keywords,Description and Title
            */
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->setTitle($this->htmlEscape($this->__(Mage::getStoreConfig('faqs/seo/faq_meta'))));
            $head->setKeywords(Mage::getStoreConfig('faqs/seo/faq_keywords'));
            $head->setDescription(Mage::getStoreConfig('faqs/seo/faq_description'));


        }
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
    $breadcrumbs->addCrumb('home', array('label'=>'Home', 'title'=>'Go to Home Page', 'link'=>Mage::getBaseUrl()));
    $breadcrumbs->addCrumb('faqs', array('label'=>$requestLable, 'title'=>$requestLable, 'link'=>Mage::getBaseUrl().$requestUrl));
            }
               


    }
    public function getFaqCatCollection($catid)
    {

        $currentStoreId = Mage::app()->getStore(true)->getId();
        $categories =  Mage::getModel('faqs/categories')->getCollection()
                ->addFieldToFilter('status','1')
                ->addFieldToFilter('store_id', array(
              array('finset' => $currentStoreId),
              array('finset' => '0'))
              );
        $categories->addOrder('cat_sortorder','ASC');
        return $categories;
    }
    public function getFaqCollection($catId)
    {

        $currentStoreId = Mage::app()->getStore(true)->getId();
       $faqCollection = Mage::getModel('faqs/items')
             ->getCollection()
             ->addFieldToFilter('cat_id',$catId)
             ->addFieldToFilter('status','1')
              ->addFieldToFilter('store_id', array(
              array('finset' => $currentStoreId),
              array('finset' => '0'))
              )
             ;
         $searchfaqs=$this->getRequest()->getParam('search-faqs');
        if($searchfaqs!= NULL)
        {
          $faqCollection->getSelect()->where("quetion like '%$searchfaqs%' OR answer like '%$searchfaqs%'");

        }
        $faqCollection->addOrder('itemsortorder','ASC');
        return $faqCollection->getData();

    }

    public function getsearchCollection($catId)
    {
       $currentStoreId = Mage::app()->getStore(true)->getId();

         if($this->getRequest()->getParam('searchid')){

            $cat_id = $this->getRequest()->getParam('searchid');
            $categoriesCollections =  Mage::getModel('faqs/categories')->getCollection()
                            ->addFieldToFilter('status','1')
                            ->addFieldToFilter('store_id', array(array('finset' => $currentStoreId),array('finset' => '0')));
            $categoriesCollections->getSelect()->where("cat_id = $cat_id or parentcatid=$cat_id");
            foreach ($categoriesCollections as $categoriesCollection) {
                $cat_ids[] = $categoriesCollection->getCatId();
            }
            $searchfaqs=$this->getRequest()->getParam('search-faqs');
            $faqCollection = Mage::getModel('faqs/items')
                              ->getCollection()
                              ->addFieldToFilter('status','1')
                              ->addFieldToFilter('cat_id',$cat_ids)
                              ->addFieldToFilter('store_id', array(array('finset' => $currentStoreId),array('finset' => '0')));
          $faqCollection->getSelect()->where("quetion like '%$searchfaqs%' OR answer like '%$searchfaqs%'");

          return $faqCollection;


        } else {
          $currentStoreId = Mage::app()->getStore(true)->getId();
          $searchfaqs=$this->getRequest()->getParam('search-faqs');
          $faqCollection = Mage::getModel('faqs/items')
                              ->getCollection()
                              ->addFieldToFilter('status','1')
                              ->addFieldToFilter('store_id', array(array('finset' => $currentStoreId),array('finset' => '0')));
          $faqCollection->getSelect()->where("quetion like '%$searchfaqs%' OR answer like '%$searchfaqs%'");
          return $faqCollection;
        }


    }
}
