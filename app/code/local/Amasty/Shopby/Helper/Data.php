<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */ 

class Amasty_Shopby_Helper_Data extends Amasty_Shopby_Helper_Cached
{
    protected $_useSolr;
    
    const XML_PATH_SEO_PRICE_NOFOLLOW       = 'amshopby/seo/price_nofollow';
    const XML_PATH_SEO_PRICE_NOINDEX        = 'amshopby/seo/price_noindex';
    const XML_PATH_SEO_PRICE_RELNOFOLLOW    = 'amshopby/seo/price_rel_nofollow';
    const TEMPLATE_CATEGORY = '{category}';

    protected static $categoriesMultiselectMode;

    /**
     * @return bool
     */
    public function getSeoPriceNofollow()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SEO_PRICE_NOFOLLOW);
    }

    /**
     * @return bool
     */
    public function getSeoPriceNoindex()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SEO_PRICE_NOINDEX);
    }

    /**
     * @return bool
     */
    public function getSeoPriceRelNofollow()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SEO_PRICE_RELNOFOLLOW);
    }

    /**
     * @return array|bool|mixed
     */
    protected function _getFilters()
    {
        $cacheId = 'filters';

        $result = $this->load($cacheId);
        if ($result) {
            return $result;
        }

        //get all possible filters as collection
        $filterCollection = Mage::getResourceModel('amshopby/filter_collection')
                ->addFieldToFilter('show_on_view', 1)
                ->addTitles();
        // convert to array
        $filters = array();
        foreach ($filterCollection as $filter){
            $filters[$filter->getId()] = $filter;
        }

        $this->save($filters, $cacheId);
        return $filters;
    }

    /**
     * @return Amasty_Shopby_Model_Filter[]
     */
    public function getAttributesSettings()
    {
        $cacheId = 'attribute_settings';

        $result = $this->load($cacheId);
        if ($result) {
            return $result;
        }

        $attrCollection = Mage::getResourceModel('amshopby/filter_collection')->load();

        $attributes = array();
        foreach ($attrCollection as $row){
            $attributes[$row->getAttributeId()] = $row;
        }

        $this->save($attributes, $cacheId);
        return $attributes;
    }

    /**
     * @return string - filter position
     * @param Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute $filter
     *          - used in app/design/frontend/base/default/template/amasty/amshopby/view_top.phtml
     */
    public function getAttributePosition($filter)
    {
        if (is_object($filter->getAttributeModel())) {
            $attributeId = $filter->getAttributeModel()->getAttributeId();
            $settings = $this->getAttributesSettings();
            if (array_key_exists($attributeId, $settings)) {
                return "amshopby-filter-position-" .$settings[$attributeId]->getBlockPos();
            }
        }

        if ($filter instanceof Mage_Catalog_Block_Layer_Filter_Price) {
            return "amshopby-filter-position-" . Mage::getStoreConfig('amshopby/price_filter/block_pos');
        }

        if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Stock) {
            return "amshopby-filter-position-" . Mage::getStoreConfig('amshopby/stock_filter/block_pos');
        }

        if ($filter instanceof Mage_Catalog_Block_Layer_Filter_Category) {
            return "amshopby-filter-position-" . Mage::getStoreConfig('amshopby/category_filter/block_pos');
        }

        if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Rating) {
            return "amshopby-filter-position-" . Mage::getStoreConfig('amshopby/rating_filter/block_pos');
        }

        if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_New) {
            return "amshopby-filter-position-" . Mage::getStoreConfig('amshopby/new_filter/block_pos');
        }

        if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_OnSale) {
            return "amshopby-filter-position-" . Mage::getStoreConfig('amshopby/on_sale_filter/block_pos');
        }

        return "";
    }


    /**
     * @return array|bool|mixed
     */
    public function getIconsData()
    {
        $cacheId = 'icons_data';

        $result = $this->load($cacheId);
        if ($result) {
            return $result;
        }

        $filters = $this->_getFilters();

        $optionCollection = Mage::getModel('amshopby/value')->getCollection()
            ->addPositions()
            ->addFieldToFilter('img_medium', array('gt' => ''))  
            ->addValue();  

        $result = array();
        /** @var Amasty_Shopby_Helper_Url $hlp */
        $hlp = Mage::helper('amshopby/url');
        foreach ($optionCollection as $opt){
            /** @var Amasty_Shopby_Model_Value $opt */

            $filterId = $opt->getFilterId();
            // it is possible when "use on view" = "false"
            if (empty($filters[$filterId]))
                continue;

            /** @var Amasty_Shopby_Model_Filter $filter */
            $filter = $filters[$filterId];
                            
            // seo urls fix when different values        
            $opt->setTitle($opt->getValue() ? $opt->getValue() : $opt->getCurrentTitle());
                
            $img  = $opt->getImgMedium();
            $url = $hlp->getOptionUrl($filter->getAttributeCode(), $opt->getOptionId());

            $result[$opt->getOptionId()] = array(
                'url'   => str_replace('___SID=U&','', $url),
                'title' => $opt->getCurrentTitle(),
                'descr' => $opt->getCurrentDescr(),
                'img'   => Mage::getBaseUrl('media') . 'amshopby/' . $img,  
                'pos'   => $filter->getPosition(),  
                'pos2'  => $opt->getSortOrder(),  
            );    
        }

        $this->save($result, $cacheId);
        return $result;
    }    
    
    /**
     * Returns HTML with attribute images
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $mode (view, list, grid)
     * @param array $names arrtibute codes to show images for
     * @param bool $exclude flag to indicate taht we need to show all attributes beside specified in $names
     * @return string
     */
    public function showLinks($product, $mode='view', $names=array(), $exclude=false)
    {
        $filters = $this->_getFilters();
        
        $items = array();
        foreach ($filters as $filter){
            $code = $filter->getAttributeCode(); 
            if (!$code){
                continue;
            }
            
            if ($names && in_array($code, $names) && $exclude)
                continue;
                
            if ($names && !in_array($code, $names) && !$exclude)
                continue;
            
            $optIds  = trim($product->getData($code), ','); 
            if (!$optIds && $product->isConfigurable()){
                $usedProds = $product->getTypeInstance(true)->getUsedProducts(null, $product);
                foreach ($usedProds as $child){
                    if ($child->getData($code)){
                        $optIds .= $child->getData($code) . ',';
                    }
                }
            }
            
            if ($optIds){
                $optIds = explode(',', $optIds);
                $optIds = array_unique($optIds);
                $iconsData = $this->getIconsData();
                foreach ($optIds as $id){
                    if (isset($iconsData[$id])){
                        $items[] = $iconsData[$id];
                    }
                }
            }
        }

        //sort by position in the layered navigation
        usort($items, array('Amasty_Shopby_Helper_Data', '_srt'));
        
        //create block
        $block = Mage::getModel('core/layout')->createBlock('core/template')
            ->setArea('frontend')
            ->setTemplate('amasty/amshopby/links.phtml');
        $block->assign('_type', 'html')
            ->assign('_section', 'body')        
            ->setLinks($items)
            ->setMode($mode); // to be able to created different html
             
        return $block->toHtml();          
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    public static function _srt($a, $b)
     {
        $res = ($a['pos'] < $b['pos']) ? -1 : 1;
        if ($a['pos'] == $b['pos']){ 
            if ($a['pos2'] == $b['pos2'])
                $res = 0;
            else 
                $res = ($a['pos2'] < $b['pos2']) ? -1 : 1;
        }
        
        return $res;
     }

    /**
     * @param $major
     * @param $minor
     * @return bool
     */
    public function isVersionLessThan($major, $minor)
    {
        $curr = explode('.', Mage::getVersion()); // 1.3. compatibility
        $need = func_get_args();
        foreach ($need as $k => $v){
            if ($curr[$k] != $v)
                return ($curr[$k] < $v);
        }
        return false;
    }
    
    /**
     * Gets params (6,17,89) from the request as array and sanitize them
     *
     * @param string $key attribute code
     * @param string $backendType attribute type
     * @return array
     */
    public function getRequestValues($key, $backendType = '')
    {
       $v = Mage::app()->getRequest()->getParam($key);
       
       if (is_array($v) || $backendType == 'decimal'){//smth goes wrong
           return array();
       }

       $tmp = str_replace(Amasty_Shopby_Helper_Attributes::MAPPED_PREFIX, '', $v);
       if (preg_match('/^[0-9,]+$/', $tmp)) {
            $v = array_unique(explode(',', $v));
       } else {
            $v = array();
       }
       
       return $v;       
    }

    /**
     * @return bool
     */
    public function isOnLandingPage()
    {
        return $this->isModuleEnabled('Amasty_Xlanding') && Mage::app()->getRequest()->getParam('am_landing');
    }

    /**
     *
     */
    public function error404()
    {
        Mage::app()->getResponse()
            ->setHeader('HTTP/1.1','404 Not Found')
            ->setHeader('Status','404 File not found');

        $layout = Mage::app()->getLayout();
        foreach ($layout->getAllBlocks() as $block) {
            $layout->unsetBlock($block->getNameInLayout());
        }

        $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_NO_ROUTE_PAGE);
        try {
            Mage::helper('cms/page')->renderPage(Mage::app()->getFrontController()->getAction(), $pageId);
        } catch (Exception $e) {
            ;
        }
        Mage::app()->getResponse()->sendResponse();
        Mage::helper('ambase/utils')->_exit();
    }

    /**
     * Display 404 error if multiple values was selected for 'Single Choice Only' attributes
     */
    public function restrictMultipleSelection()
    {
        /** @var Amasty_Shopby_Helper_Attributes $attributeHelper */
        $attributeHelper = Mage::helper('amshopby/attributes');
        $requestedCodes = $attributeHelper->getRequestedFilterCodes();
        $multiselectCodes = $this->getMultiselectAttributeCodes();

        $mappedOptions = array_keys(Mage::helper('amshopby/url')->getMappedOptionsWithParents());

        foreach ($requestedCodes as $code => $value)
        {
            //assumed all child filters are multiselect. remove it codes from value
            $value = str_replace($mappedOptions, '', $value);
            $value = trim($value, ',');


            if (false !== strpos($value, ',')) // Multiple values
            {
                if (!in_array($code, $multiselectCodes)) {
                    $this->error404();
                }
            }
        }
    }

    /**
     * @return array|bool|mixed
     */
    protected function getMultiselectAttributeCodes()
    {
        $cacheId = 'multiselect_attribute_codes';

        $result = $this->load($cacheId);
        if ($result) {
            return $result;
        }

        /** @var Amasty_Shopby_Helper_Attributes $attributesHelper */
        $attributesHelper = Mage::helper('amshopby/attributes');
        $attributes = $attributesHelper->getFilterableAttributes();
        $settings = $this->getAttributesSettings();

        $result = array();
        foreach ($attributes as $attribute) {
            if (isset($settings[$attribute->getId()])) {
                if (!$settings[$attribute->getId()]->getSingleChoice()) {
                    $result[] = $attribute->getAttributeCode();
                }
            }
        }

        $this->save($result, $cacheId);
        return $result;
    }

    /**
     * @return bool
     */
    public function useSolr()
    {
        if (isset($this->_useSolr)) {
            return $this->_useSolr;
        }

        if ($this->isModuleEnabled('Enterprise_Search')) {
            /** @var Enterprise_Search_Helper_Data $helper */
            $helper = Mage::helper('enterprise_search');

            $routeName = Mage::app()->getRequest()->getRequestedRouteName();
            if ($routeName == 'catalog' || $routeName == 'amshopby') {
                $result = $helper->getIsEngineAvailableForNavigation(true);
            } else if ($routeName == 'catalogsearch') {
                $result = $helper->getIsEngineAvailableForNavigation(false);
            } else if ($routeName == 'adminhtml' || is_null($routeName)) {
                // process indexation
                $result = $helper->isActiveEngine();
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }

        $this->_useSolr = $result;
        return $result;
    }

    /**
     * Compatibility with Magento 1.4
     * @param string $moduleName
     * @return bool
     */
    public function isModuleEnabled($moduleName = null)
    {
        return 'true' == (string)Mage::getConfig()->getNode('modules/' . $moduleName .'/active');
    }

    /**
     * @deprecated
     * Not need to call in current version
     */
    public function init()
    {
    }

    /**
     * @return bool|Mage_Catalog_Model_Category|mixed|null
     */
    public function getCurrentCategory()
    {
        $cacheId = 'current_category';

        $result = $this->load($cacheId);
        if ($result) {
            return $result;
        }

        /** @var Mage_Catalog_Model_Category $category */
        $category = null;

        $forwardedCategoryId = Mage::registry('amshopby_forwarded_category_id');
        if ($forwardedCategoryId) {
            /** @var Mage_Catalog_Model_Category $category */
            $category = Mage::getModel('catalog/category')->load($forwardedCategoryId);
        }

        if (!$category && !$this->getCategoriesMultiselectMode()) {
            // Try from XLandings or search argument
            $moduleName = Mage::app()->getRequest()->getModuleName();
            $isSomeSearch = in_array($moduleName, array('sqli_singlesearchresult', 'catalogsearch' ,'categorysearch'));

            if (($this->isOnLandingPage() || $isSomeSearch) && Mage::app()->getRequest()->getParam('cat')) {
                $cat = Mage::app()->getRequest()->getParam('cat');
                if(is_array($cat)) {
                    $cat = array_shift($cat);
                }
                $category = Mage::getModel('catalog/category')->load($cat);
                if (!$category->getId()) {
                    $category = null;
                }
            }
        }

        // Try standard category view
        if (!$category)
        {
            $category = Mage::registry('current_category');
            if (!is_object($category) || $category->getId() == 0) {
                $category = null;
            }
        }

        // Default category by default
        if (!$category) {
            $category = Mage::getModel('catalog/category')->load(Mage::app()->getStore()->getRootCategoryId());
        }

        $this->saveLite($category, $cacheId);
        return $category;
    }

    /**
     * @return bool
     */
    public function getCategoriesMultiselectMode()
    {
        if (is_null(self::$categoriesMultiselectMode)) {
            self::$categoriesMultiselectMode = Mage::getStoreConfigFlag('amshopby/category_filter/multiselect')
                && Mage::getStoreConfig('amshopby/category_filter/display_mode') == Amasty_Shopby_Model_Catalog_Layer_Filter_Category::DT_DEFAULT;
        }
        return self::$categoriesMultiselectMode;
    }

    /**
     * @return Mage_Core_Model_Resource_Store_Collection
     */
    public function getStores()
    {
        $stores = $this->load('stores');
        if (!$stores) {
            $stores = Mage::getModel('core/store')
                ->getResourceCollection()
                ->setLoadDefault(true)
                ->load();
            $this->saveLite($stores,'stores');
        }
        return $stores;
    }

    /**
     * @return bool|mixed
     */
    public function getIsCountGloballyEnabled()
    {
        $isEnabled = Mage::getStoreConfig('catalog/layered_navigation/display_product_count');
        if (is_null($isEnabled)) {
            // Magento 1.4 has no option
            $isEnabled = true;
        }

        return $isEnabled;
    }

    /**
     * @return bool
     */
    public function isAjaxScrollEnabled()
    {
        return Mage::getStoreConfigFlag('amshopby/general/scroll_to_products');
    }

    /**
     * @return bool
     */
    public function isNeedAjax()
    {
        $result = false;
        if (Mage::getStoreConfigFlag('amshopby/general/ajax')){
            $request = Mage::app()->getRequest();

            $isProductPage = $request->getControllerName() == "product" &&
                $request->getActionName() == "view";

            if (!$isProductPage)
                $result = true;
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function getIsApplyButtonEnabled() {
        return Mage::getStoreConfig('amshopby/general/submit_filters') == Amasty_Shopby_Model_Source_Submit::BY_BUTTON;
    }

    /**
     * @param $categoryId
     * @return array|mixed
     */
    public function getCategoryChildrenIds($categoryId)
    {
        $cacheKey = 'category_children';
        $data = $this->load($cacheKey);
        if ($data === false) {
            /** @var Mage_Catalog_Model_Resource_Category_Collection $collection */
            $collection = Mage::getResourceModel('catalog/category_collection');
            $select = $collection->getSelect();
            $select->reset(Zend_Db_Select::COLUMNS);
            $select->columns('path');
            $rows = $select->query(Zend_Db::FETCH_ASSOC)->fetchAll();
            $data = array();
            foreach ($rows as $row) {
                if (preg_match('@(\d+)/(\d+)$@', $row['path'], $match)) {
                    $parent = $match[1];
                    $child = $match[2];
                    if (array_key_exists($parent, $data)) {
                        $data[$parent][] = $child;
                    } else {
                        $data[$parent] = array($child);
                    }
                }
            }
            $this->save($data, $cacheKey);
        }

        return isset($data[$categoryId]) ? $data[$categoryId] : array();
    }

    /**
     * @param $attributeId
     * @return array
     */
    public function getColorSwatchesImageSize($attributeId)
    {
        $attribute = Mage::getModel('amconf/attribute')->load($attributeId, 'attribute_id');

        return array(
            'width' => $attribute->getCatSmallWidth() != "0" ? $attribute->getCatSmallWidth() : 25,
            'height' => $smHeight = $attribute->getCatSmallHeight() != "0" ? $attribute->getCatSmallHeight() : 25
        );
    }

    /**
     * @param $optionId
     * @param $imgSize
     * @return string
     */
    public function getColorSwatchesImageUrl($optionId, $imgSize)
    {
        /** @var Amasty_Conf_Helper_Data $amConfHelper */
        $amConfHelper = Mage::helper('amconf');

        return $amConfHelper->getImageUrl($optionId, $imgSize['width'], $imgSize['height']);
    }

    /**
     * @param $optionId
     * @return mixed
     */
    public function getColorSwatchesBackgroundColor($optionId)
    {
        $model = Mage::getModel('amconf/swatch')->load($optionId);

        return $model->getColor();
    }

    /**
     * Set category name in template
     * @param $value
     * @return mixed
     */
    public function buildTemplate($value)
    {
        $category = Mage::registry('current_category');
        if ($category) {
            $value = str_replace(static::TEMPLATE_CATEGORY, $category->getName(), $value);
        }

        return $value;
    }

    /**
     * @param string $hashTag
     * @return string
     */
    public function getGuideHint($hashTag = '')
    {
        $text = $this->__(
            'Confused? No worries, please check <a href="%s" target="_blank">the guide</a> covering all the settings.',
            $this->getGuideLink($hashTag)
        );

        return Mage::app()->getLayout()
            ->createBlock('core/template')
            ->setTemplate('amasty/amshopby/hint.phtml')
            ->setText($text)
            ->toHtml();
    }

    /**
     * @param string $hashTag
     * @return string
     */
    public function getGuideLink($hashTag = '')
    {
        $link = 'https://amasty.com/docs/doku.php?id=magento_1:improved_layered_navigation';
        if ($hashTag) {
            $link .= '#' . $hashTag;
        }

        return $link;
    }
}
