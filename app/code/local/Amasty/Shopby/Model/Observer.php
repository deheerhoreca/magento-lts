<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
class Amasty_Shopby_Model_Observer
{
    const QUERY_BEFORE_SEO_UPDATE = 'amshopby_query_before_seo_update';
    const QUERY_AFTER_SEO_UPDATE = 'amshopby_query_after_seo_update';

    public function handleControllerFrontInitRouters($observer)
    {
        $observer->getEvent()->getFront()
            ->addRouter('amshopby', new Amasty_Shopby_Controller_Router());
    }

    public function handleCatalogControllerCategoryInitAfter($observer)
    {
        if (Mage::getStoreConfig('amshopby/seo/urls')) {
            if (Mage::getStoreConfig('amshopby/seo/redirects_enabled')) {
                $this->checkRedirectToSeo();
            }

            /** @var Mage_Core_Controller_Front_Action $controller */
            $controller = $observer->getEvent()->getControllerAction();
            /** @var Mage_Catalog_Model_Category $cat */
            $cat = $observer->getEvent()->getCategory();

            if (!Mage::helper('amshopby/url')->saveParams($controller->getRequest())){
                if ($cat->getId()  == Mage::app()->getStore()->getRootCategoryId()){
                    $cat->setId(0);
                    return;
                }
                else {
                    Mage::helper('amshopby')->error404();
                }
            }

            if ($cat->getDisplayMode() == 'PAGE' && Mage::registry('amshopby_current_params')){
                $cat->setDisplayMode('PRODUCTS');
            }
        }

        Mage::helper('amshopby')->restrictMultipleSelection();
    }

    protected function checkRedirectToSeo()
    {
        if (Mage::app()->getRequest()->getParam('am_landing')) {
            // Not implemented and works incorrectly
            return;
        }

        /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
        $urlBuilder = Mage::getModel('amshopby/url_builder');
        $urlBuilder->reset();

        $isAJAX = Mage::app()->getRequest()->getParam('is_ajax', false);
        $isAJAX = $isAJAX && Mage::app()->getRequest()->isXmlHttpRequest();
        if ($isAJAX) {
            $urlBuilder->setAllowAjaxFlag(true);
        }

        $seoUrl = $urlBuilder->getUrl();
        $pSeo = strpos($seoUrl, '?');
        $tSeo = $pSeo ? substr($seoUrl, 0, $pSeo) : $seoUrl;

        $currentUrl = urldecode(Mage::helper('core/url')->getCurrentUrl());
        $pCurrent = strpos($currentUrl, '?');
        $tCurrent = $pCurrent ? substr($currentUrl, 0, $pCurrent) : $currentUrl;

        if ($tCurrent != $tSeo) {
            Mage::app()->getResponse()->setRedirect($seoUrl, 301);
        }
    }

    public function handleLayoutRender()
    {
        if (Mage::app()->getRequest()->getParam('is_scroll', false))
            return;

        /** @var Mage_Core_Model_Layout $layout */
        $layout = Mage::getSingleton('core/layout');
        $headBlock = $layout->getBlock('head');
        if (!$layout)
            return;

        $isAJAX = Mage::app()->getRequest()->getParam('is_ajax', false);
        $isAJAX = $isAJAX && Mage::app()->getRequest()->isXmlHttpRequest();
        if (!$isAJAX)
            return;

        $layout->removeOutputBlock('root');

        $page = $layout->getBlock('category.products');
        if (!$page){
            $page = $layout->getBlock('search.result');
        }

        if (!$page)
            return;

        $container = $layout->createBlock('core/template', 'amshopby_container');
        $container->setData('page', $this->_removeAjaxParam($page->toHtml()));
        $container->setData('title', $headBlock ? $headBlock->getTitle() : null);

        $blocks = array();
        $ambanners = array();
        foreach ($layout->getAllBlocks() as $b) {
            /** @var Mage_Core_Block_Abstract $b */
            $nameInLayout = $b->getNameInLayout();
            if ($nameInLayout == 'amlabel_script') {
                $container->setData('amlabel_script', $b->toHtml());
            }

            if ($b instanceof Amasty_Banners_Block_Container && $pos = (int) $b->getData('position')) {
                $ambanners[$pos] = $b->toHtml();
                continue;
            }

            if (!in_array($nameInLayout, array('amshopby.navleft','amshopby.navleft2','amshopby.navtop','amshopby.navright', 'amshopby.top', 'amshopby.bottom', 'amfinder89'))){
                continue;
            }
            $b->setIsAjax(true);
            $html = $b->toHtml();
            if (!$html && false !== strpos($b->getBlockId(), 'amshopby-filters-'))
            {
                // compatibility with "shopper" theme
                // @see catalog/layer/view.phtml
                $queldorei_blocks = Mage::registry('queldorei_blocks');
                if ($queldorei_blocks AND !empty($queldorei_blocks['block_layered_nav']))
                {
                    $html = $queldorei_blocks['block_layered_nav'];
                }
            }
            if ($b->getBlockId()) {
                $blocks[$b->getBlockId()] = $this->_removeAjaxParam($html);
            }
        }

        if (!$blocks)
            return;

        $container->setData('blocks', $blocks);
        if ($ambanners) {
            $container->setData('ambanners', $ambanners);
        }

        $layout->addOutputBlock('amshopby_container', 'toJson');
    }

    protected function _removeAjaxParam($html)
    {
        $html = str_replace('?___SID=U&amp;', '?', $html);
        $html = str_replace('?___SID=U', '', $html);
        $html = str_replace('&___SID=U', '', $html);

        return $html;
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function handleBlockOutput(Varien_Event_Observer $observer)
    {
        $this->bringBackParsedQueryParams($observer);
        $this->addAjaxContainer($observer);
        $this->addBrandSubcategories($observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    private function bringBackParsedQueryParams(Varien_Event_Observer $observer)
    {
        if ($observer->getBlock() instanceof Mage_Page_Block_Switch) {
            $seoQuery = Mage::registry(self::QUERY_AFTER_SEO_UPDATE, array());
            if (count($seoQuery)) {
                Mage::app()->getRequest()->setQuery($seoQuery);
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
     protected function addAjaxContainer(Varien_Event_Observer $observer)
     {
         if (!Mage::getStoreConfigFlag('amshopby/general/ajax')) {
             return;
         }

         /* @var $block Mage_Core_Block_Abstract */
         $block = $observer->getBlock();
         $classMatch = $block instanceof Mage_Catalog_Block_Category_View
             || $block instanceof Mage_CatalogSearch_Block_Result
             || $block instanceof Mage_Core_Block_Text_List;
         $nameMatch = in_array($block->getNameInLayout(), array('category.products', 'search.result'));

         if ($classMatch && $nameMatch) {
             $transport = $observer->getTransport();
             $html = $transport->getHtml();

             if (strpos($html, "amshopby-page-container") === false) {
                 $html = '<div class="amshopby-page-container" id="amshopby-page-container">' .
                     $html .
                     '<div style="display:none" class="amshopby-overlay"><div></div></div>'.
                     '</div>';

                 $transport->setHtml($html);
             }
         }
     }

    /**
     * @param Varien_Event_Observer $observer
     */
     protected function addBrandSubcategories(Varien_Event_Observer $observer)
     {
         /* @var $block Mage_Core_Block_Abstract */
         $block = $observer->getBlock();
         if ($block instanceof Mage_Cms_Block_Block
             && Mage::getStoreConfigFlag('amshopby/brands/categories_enable')
             && Mage::helper('amshopby/attributes')->getCurrentBrandPageBrand()
         ) {
             /** @var Mage_Catalog_Model_Layer $layer */
             $layer = Mage::getSingleton('catalog/layer');
             $category = $layer->getCurrentCategory();
             if ($block->getBlockId() == $category->getLandingPage()) {
                 $transport = $observer->getTransport();
                 $html = $transport->getHtml();
                 $html .= Mage::app()->getLayout()->createBlock('amshopby/subcategories_brand')->toHtml();
                 $transport->setHtml($html);
             }
         }
     }

    /**
     * Reset search engine if it is enabled for catalog navigation
     *
     * @param Varien_Event_Observer $observer
     */
    public function resetCurrentCatalogLayer(Varien_Event_Observer $observer)
    {
        if ($this->_getDataHelper()->useSolr()) {
            Mage::register('_singleton/catalog/layer', Mage::getSingleton('enterprise_search/catalog_layer'));
        }
    }

    /**
     * Reset search engine if it is enabled for search navigation
     *
     * @param Varien_Event_Observer $observer
     */
    public function resetCurrentSearchLayer(Varien_Event_Observer $observer)
    {
        if ($this->_getDataHelper()->useSolr()) {
            Mage::register('_singleton/catalogsearch/layer', Mage::getSingleton('enterprise_search/search_layer'));
        }
    }

    public function settingsChanged()
    {
        /** @var Amasty_Shopby_Model_Mysql4_Filter_Collection $filterCollection */
        $filterCollection = Mage::getResourceModel('amshopby/filter_collection');
        $count = $filterCollection->count();
        if ($count == 0) {
            Mage::getResourceModel('amshopby/filter')->refreshFilters();
        }
        $this->invalidateCache();
    }

    public function attributeChanged()
    {
        Mage::getResourceModel('amshopby/filter')->refreshFilters();
        $this->invalidateCache();
    }

    protected function invalidateCache()
    {
        $this->_getDataHelper()->invalidateCache();
    }

    protected function _getDataHelper()
    {
        /** @var Amasty_Shopby_Helper_Data $helper */
        $helper = Mage::helper('amshopby');
        return $helper;
    }

    public function onCoreBlockAbstractToHtmlBefore($observer)
    {
        $this->removeParsedQueryParams($observer);
        $this->disableCacheOnSubcategoriesBlock($observer);
        return $this;
    }

    /**
     * For the store switcher.
     * Fix redirect from http://example.com/default/male.html to http://example.com/french/male.html?gender=93
     * where "gender" GET param is excess.
     * @param Varien_Event_Observer $observer
     */
    private function removeParsedQueryParams(Varien_Event_Observer $observer)
    {
        if ($observer->getBlock() instanceof Mage_Page_Block_Switch) {
            $oldQuery = Mage::registry(self::QUERY_BEFORE_SEO_UPDATE, array());
            $currQuery = Mage::app()->getRequest()->getQuery();
            Mage::register(self::QUERY_AFTER_SEO_UPDATE, $currQuery, true);
            foreach ($currQuery as $key => $value) {
                if (!isset($oldQuery[$key])) {
                    unset(${'_GET'}[$key]);
                } else {
                    Mage::app()->getRequest()->setQuery($key, $oldQuery[$key]);
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    private function disableCacheOnSubcategoriesBlock(Varien_Event_Observer $observer)
    {
        if ($observer->getBlock() instanceof Mage_Cms_Block_Block) {
            $blockModel = Mage::getModel('cms/block')->load($observer->getBlock()->getBlockId());
            if (strpos($blockModel->getContent(), 'amshopby/subcategories') !== false) {
                $observer->getBlock()->setCacheLifetime(null);
            }
        }
    }
}
