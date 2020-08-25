<?php

class Magiecommerce_Faqs_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        /**
         * for module enable or disable
         */
        $pageLayoutCode = Mage::getStoreConfig('faqs/faqs/faqs_page');
        $enable = Mage::getStoreConfig('faqs/faqs/enabled');
        if ($enable == '1'):
            $this->loadLayout();
            $this->getLayout()->helper('page/layout')->applyTemplate($pageLayoutCode);
            $this->renderLayout();
        else:
            $this->_forward('faqnoroute');
        endif;
    }

    /**
     * Displays the current FAQ's detail view
     */
    public function showAction() {
        $this->loadLayout()->renderLayout();
    }

    /**
     * Displays the current FAQ's detail view
     */
    public function faqnorouteAction() {

        $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_NO_ROUTE_PAGE);
        Mage::helper('cms/page')->renderPage($this, $pageId);
    }

    public function categoryAction() {
          $enable = Mage::getStoreConfig('faqs/faqs/enabled');
        if($enable == '1')
        {

        $catid = $this->getRequest()->getParam('id');
        $loadlayout = Mage::getModel('faqs/categories')->load($catid);
        $layout = $loadlayout->getPageLayout();
        $this->loadLayout();
        $this->getLayout()->helper('page/layout')->applyTemplate($layout);
        $this->renderLayout();
    }
    else
    {
       $this->_forward('faqnoroute');
    }


    }

    public function searchAction()
    {
       $pageLayoutCode = Mage::getStoreConfig('faqs/faqs/faqs_page');
        $enable = Mage::getStoreConfig('faqs/faqs/enabled');
        if ($enable == '1'):
            $this->loadLayout();
            $this->getLayout()->helper('page/layout')->applyTemplate($pageLayoutCode);
            $this->renderLayout();
        else:
            $this->_forward('faqnoroute');
        endif;
        $enable = Mage::getStoreConfig('faqs/faqs/enabled');
    }

}
