<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */

class Amasty_Shopby_Block_Catalog_Layer_View extends Amasty_Shopby_Block_Catalog_Layer_View_Pure
{
    protected $_filterBlocks = null;
    protected $_blockPos     = 'left';

    protected $attributeOptionsData;

    /**
     * @return array
     */
    public function getFilters()
    {
        if ($this->_filterBlocks !== null){
            return $this->_filterBlocks;
        }

        if ($this->_isCurrentUserAgentExcluded()) {
            return array();
        }

        $filters = parent::getFilters();

        $filters = $this->getChildFilters($filters);

        $filters = $this->_excludeCurrentLandingFilters($filters);

        // append stock filter
        $filter = $this->getChild('stock_filter');
        if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Stock
            && !$this->_notInBlock(Mage::getStoreConfig('amshopby/stock_filter/block_pos'))
        ) {
            $filters[] = $filter;
        }

        /** @var Amasty_Shopby_Block_Catalog_Layer_Filter_Rating $filter */
        $filter = $this->getChild('rating_filter');
        if ($filter && !Mage::helper('amshopby')->useSolr()
            && !$this->_notInBlock(Mage::getStoreConfig('amshopby/rating_filter/block_pos'))
        ) {
            $filters[] = $filter;
        }

        $filter = $this->getChild('new_filter');
        if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_New
            && !$this->_notInBlock(Mage::getStoreConfig('amshopby/new_filter/block_pos'))) {
            $filters[] = $filter;
        }

        $filter = $this->getChild('on_sale_filter');
        if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_OnSale
            && !$this->_notInBlock(Mage::getStoreConfig('amshopby/on_sale_filter/block_pos'))) {
            $filters[] = $filter;
        }

        // remove some filters from the home page
        $exclude = Mage::getStoreConfig('amshopby/general/exclude');
        if ('/' == Mage::app()->getRequest()->getRequestString() && $exclude) {
            $exclude = explode(',', preg_replace('/[^a-zA-Z0-9_\-,]+/', '', $exclude));
            $filters = $this->excludeFilters($filters, $exclude);
        } else {
            $exclude = array();
        }

        $this->computeAttributeOptionsData($filters);


        $filtersPositions = Mage::helper('amshopby/attributes')->getPositionsAttributes();

        // update filters with new properties
        $allSelected = array();
        foreach ($filters as $filter) {
            $strategy = $this->_getFilterStrategy($filter);

            if (is_object($strategy)) {
                // initiate all filter-specific logic
                $strategy->prepare();
                $filter->setIsExcluded($strategy->getIsExcluded());

                // remember selected options for dependent excluding
                if ($strategy instanceof Amasty_Shopby_Helper_Layer_View_Strategy_Attribute) {
                    $selectedValues = $strategy->getSelectedValues();
                    if ($selectedValues){
                        $allSelected = array_merge($allSelected, $selectedValues);
                    }
                }
            }

            if (is_object($filter->getAttributeModel())
                && isset($filtersPositions[$filter->getAttributeModel()->getAttributeCode()])) {
                $filter->setPosition($filtersPositions[$filter->getAttributeModel()->getAttributeCode()]);
            }
            if ($filter instanceof Mage_Catalog_Block_Layer_Filter_Category
                || $filter instanceof Enterprise_Search_Block_Catalog_Layer_Filter_Category) {
                $filter->setPosition($filtersPositions['ama_category_filter']);
            }
            if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Rating) {
                $filter->setPosition($filtersPositions['ama_rating_filter']);
            }
            if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_New) {
                $filter->setPosition($filtersPositions['ama_new_filter']);
            }
            if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Stock) {
                $filter->setPosition($filtersPositions['ama_stock_filter']);
            }
            if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_OnSale) {
                $filter->setPosition($filtersPositions['ama_on_sale_filter']);
            }
        }

        //exclude dependant, since 1.4.7
        foreach ($filters as $filter) {
            $parentAttributes = trim(str_replace(' ', '', $filter->getDependOnAttribute()));

            if (!$parentAttributes) {
                continue;
            }

            if (!empty($parentAttributes)) {
                $attributePresent = false;
                $parentAttributes = explode(',', $parentAttributes);
                foreach ($parentAttributes as $parentAttribute) {
                    if (Mage::app()->getRequest()->getParam($parentAttribute)) {
                        $attributePresent = true;
                        break;
                    }
                }
                if (!$attributePresent) {
                    $exclude[] = $filter->getAttributeModel()->getAttributeCode();
                }
            }
        }

        // 1.2.7 exclude some filters from the selected categories
        $filters = $this->excludeFilters($filters, $exclude);

        usort($filters, array(Mage::helper('amshopby/attributes'), 'sortFiltersByOrder'));

        $this->_filterBlocks = $filters;
        return $filters;
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function getChildFilters(array $filters)
    {
        foreach ($filters as $f) {
            if ($alias = $f->getChildAlias()) {
                $child = $this->getChild($alias);
                if ($child) {
                    $filters[] = $child;
                }
            }
        }
        return $filters;
    }

    /**
     * @param Mage_Catalog_Block_Layer_Filter_Abstract $filter
     * @return Amasty_Shopby_Helper_Layer_View_Strategy_Abstract|null
     */
    protected function _getFilterStrategy(Mage_Catalog_Block_Layer_Filter_Abstract $filter)
    {
        $strategyCode = null;
        if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Stock) {
            $strategyCode = 'stock';
        }
        else if ($filter instanceof Mage_Catalog_Block_Layer_Filter_Attribute || $filter instanceof Enterprise_Search_Block_Catalog_Layer_Filter_Attribute) {
            $strategyCode = 'attribute';
        }
        else if ($filter instanceof Mage_Catalog_Block_Layer_Filter_Category || $filter instanceof Enterprise_Search_Block_Catalog_Layer_Filter_Category) {
            $strategyCode = 'category';
        }
        else if ($filter instanceof Mage_Catalog_Block_Layer_Filter_Price || $filter instanceof Enterprise_Search_Block_Catalog_Layer_Filter_Price) {
            $strategyCode = 'price';
        }
        else if ($filter instanceof Mage_Catalog_Block_Layer_Filter_Decimal || $filter instanceof Enterprise_Search_Block_Catalog_Layer_Filter_Decimal) {
            $strategyCode = 'decimal';
        }
        else if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Rating) {
            $strategyCode = 'rating';
        }
        else if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_OnSale) {
            $strategyCode = 'onSale';
        }
        else if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_New) {
            $strategyCode = 'new';
        }

        /** @var Amasty_Shopby_Helper_Layer_View_Strategy_Abstract|null $strategy */
        if ($strategyCode) {
            $strategy = Mage::helper('amshopby/layer_view_strategy_' . $strategyCode);
            $strategy->setLayer($this);
            $strategy->setFilter($filter);
        } else {
            $strategy = null;
        }

        return $strategy;
    }

    /**
     * @param $filters
     */
    protected function computeAttributeOptionsData($filters)
    {
        $ids = array();
        foreach ($filters as $f){
            if ($f->getItemsCount() && ($f instanceof Mage_Catalog_Block_Layer_Filter_Attribute || $f instanceof Enterprise_Search_Block_Catalog_Layer_Filter_Attribute)){
                $items = $f->getItems();
                foreach ($items as $item) {
                    $ids[] = $item->getOptionId();
                }
            }
        }

        // images of filter values
        $optionsCollection = Mage::getModel('amshopby/value')
            ->getCollectionByMixedIds($ids)->load();

        $this->attributeOptionsData = array();
        foreach ($optionsCollection as $row){
            $this->attributeOptionsData[$row->getOptionId()] = array(
                'img' => $row->getImgSmall(),
                'img_hover' => $row->getImgSmallHover(),
                'descr' => $row->getDescr()
            );
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getAttributeOptionsData()
    {
        if (is_null($this->attributeOptionsData)) {
            throw new Exception('AttributeOptionsData not initialized');
        }

        return $this->attributeOptionsData;
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function _excludeCurrentLandingFilters(array $filters)
    {
        /** @var Amasty_Xlanding_Model_Page $landingPage */
        $landingPage = Mage::registry('amlanding_page');
        if (is_null($landingPage)) {
            return $filters;
        };

        if(method_exists($landingPage, 'isHideConditionFilters') && $landingPage->isHideConditionFilters() === false) {
            return $filters;
        }

        $conditions = $landingPage->getConditions();
        $excludeCodes = array();
        foreach ($conditions['conditions'] as $condition) {
            /** @var Amasty_Xlanding_Model_Filter_Condition_Abstract $condition */

            if (!is_object($condition)) {
                continue;
            }
            if ($condition instanceof Amasty_Xlanding_Model_Filter_Condition_Product) {
                $excludeCodes[] = $condition->getAttribute();
            }
        }

        $result = array();
        foreach ($filters as $f) {
            if ($f->getAttributeModel()){
                $code = $f->getAttributeModel()->getAttributeCode();
                if (in_array($code, $excludeCodes)) {
                    continue;
                }
            }

            if ($f instanceof Mage_Catalog_Block_Layer_Filter_Category) {
                if ($landingPage->getCategory()) {
                    continue;
                }
            }

            $result[] = $f;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    protected function _getFilterableAttributes()
    {
        $attributes = $this->getData('_filterable_attributes');
        if (is_null($attributes)) {
            $setIds = $this->getLayer()->getProductCollection()->getSetIds();

            $settings   = $this->_getDataHelper()->getAttributesSettings();
            $attributes = Mage::helper('amshopby/attributes')->getFilterableAttributesBySets($setIds);

            foreach ($attributes as $k => $v) {
                $pos = 'left';
                if (isset($settings[$v->getId()])) {
                    $pos = $settings[$v->getId()]->getBlockPos();
                } elseif ($v->getAttributeCode() == 'price') {
                    $pos = Mage::getStoreConfig('amshopby/price_filter/block_pos');
                }
                if ($this->_notInBlock($pos)) {
                    unset($attributes[$k]);
                }
            }

            $this->setData('_filterable_attributes', $attributes);
        }

        return $attributes;
    }

    /**
     * @return string
     */
    public function getStateHtml()
    {
        $pos = Mage::getStoreConfig('amshopby/general/state_pos');
        if ($this->_notInBlock($pos)) {
            return '';
        }
        $this->getChild('layer_state')->setTemplate('amasty/amshopby/state.phtml');
        return $this->getChildHtml('layer_state');
    }

    /**
     * @return bool|int
     */
    public function canShowBlock()
    {
        if ($this->canShowOptions()) {
            return true;
        }

        $cnt = 0;
        $pos = Mage::getStoreConfig('amshopby/general/state_pos');
        if (!$this->_notInBlock($pos)) {
            $cnt = count($this->getLayer()->getState()->getFilters());
        }
        return $cnt;
    }

    /**
     * @return string
     */
    public function getBlockId()
    {
        return 'amshopby-filters-' . $this->_blockPos;
    }

    /**
     * @param $filters
     * @param $exclude
     * @return array
     */
    protected function excludeFilters($filters, $exclude)
    {
        $new = array();
        foreach ($filters as $f) {
            $code = substr($f->getData('type'), 1+strrpos($f->getData('type'), '_'));
            if ($f->getAttributeModel()) {
                $code = $f->getAttributeModel()->getAttributeCode();
            }

            if (in_array($code, $exclude) || $f->getIsExcluded()) {
                 continue;
            }

            $new[] = $f;
        }
        return $new;
    }

    /**
     * @param string $html
     * @return mixed|string
     */
    protected function _afterToHtml($html)
    {
        $html = parent::_afterToHtml($html);

        $queldorei = false;
        if (!$html) {
            // compatibility with "shopper" theme
            // @see catalog/layer/view.phtml
            $queldoreiBlocks = Mage::registry('queldorei_blocks');
            if ($queldoreiBlocks && !empty($queldoreiBlocks['block_layered_nav'])) {
                $html = $queldoreiBlocks['block_layered_nav'];
            }
            if (!$html) {
                return '';
            }
            $queldorei = true;
        }

        $pos = strrpos($html, '</div>');
        if ($pos !== false) {
            $needInsertApplyButton = Mage::helper('amshopby')->getIsApplyButtonEnabled()
                && !$this->_notInBlock(Mage::getStoreConfig('amshopby/general/submit_position'));
            if ($needInsertApplyButton) {
                $html = substr($html, 0, strrpos($html, '</div>'))
                    .$this->getLayout()
                            ->createBlock('amshopby/catalog_layer_filter_apply')
                            ->toHtml()
                    . substr($html, strrpos($html, '</div>'));
            }

            //add an overlay before closing tag
            $html = substr($html, 0, strrpos($html, '</div>'))
                . '<div style="display:none" class="amshopby-overlay"></div>'
                . substr($html, strrpos($html, '</div>'));
        }


        // to make js and css work for 1.3 also
        $html = str_replace('class="narrow-by', 'class="block-layered-nav narrow-by', $html);
        // add selector for ajax
        $html = str_replace('block-layered-nav', 'block-layered-nav ' . $this->getBlockId(), $html);

        if (Mage::getStoreConfig('amshopby/general/enable_collapsing')) {
            $html = str_replace('block-layered-nav', 'block-layered-nav amshopby-collapse-enabled', $html);
        }

        $scrollEnableSize = (int) Mage::getStoreConfig('amshopby/general/enable_overflow_scroll');
        if ($scrollEnableSize) {
            $html = $this->generateOverflowScroll($html, $scrollEnableSize);
        }

        // we don't want to move this into the template are different in custom themes
        $html = $this->generateTooltip($html);

        if ($queldorei AND !empty($queldoreiBlocks['block_layered_nav'])) {
            // compatibility with "shopper" theme
            // @see catalog/layer/view.phtml
            Mage::unregister('queldorei_blocks');
            $queldoreiBlocks['block_layered_nav'] = $html;
            Mage::register('queldorei_blocks', $queldoreiBlocks);
            return '';
        }

        $this->saveLayerCache();

        return $html;
    }

    /**
     * @param $html
     * @param $enableOverflowScroll
     * @return mixed|string
     */
    protected function generateOverflowScroll($html, $enableOverflowScroll)
    {
        if (strpos($html, 'block-layered-nav') !== false) {
            $html = str_replace('block-layered-nav', 'block-layered-nav amshopby-overflow-scroll-enabled', $html);
            $html .= '<style>'
                . 'div.amshopby-overflow-scroll-enabled div.block-content dl dd > ol:first-of-type {'
                . 'max-height: ' . $enableOverflowScroll . 'px;}'
                . '</style>';
        }

        return $html;
    }

    /**
     * @param $html
     * @return mixed
     */
    protected function generateTooltip($html)
    {
        $storeId = Mage::app()->getStore()->getStoreId();

        foreach ($this->getFilters() as $filter) {
            $name = $this->__($filter->getName());
            if ($filter->getCollapsed() && !$filter->getHasSelection()) {
                $html = preg_replace(
                    '|(<dt[^>]*)(>'. preg_quote($name, '|') .')|iu', '$1 class="amshopby-collapsed"$2',
                    $html
                );
            }

            $comment = $this->getStoreComment($filter, $storeId);

            if ($comment) {
                $img = Mage::getDesign()->getSkinUrl('images/amshopby-tooltip.png');
                $img = ' <img class="amshopby-tooltip-img" src="' . $img . '" 
                        width="9" height="9" alt="' . $comment . '" />';

                $pattern = '@(<dt[^>]*>\s*' . preg_quote($name, '@') . ')\s*(</dt>)@ui';
                $replacement = '$1 ' . $img . '$2';
                $html = preg_replace($pattern, $replacement, $html);
            }
        }

        return $html;
    }

    /**
     * @param $filter
     * @param $storeId
     * @return string
     */
    protected function getStoreComment($filter, $storeId)
    {
        if ($comment = $filter->getComment()) {
            if (preg_match('^([adObis]:|N;)^', $comment)) {
                try {
                    $comment = unserialize($comment);
                } catch (Exception $e) {
                    Mage::log($e->getMessage());
                }

                if (is_array($comment)) {
                    $comment = isset($comment[$storeId])
                        ? $comment[$storeId]
                        : $comment[0];
                }
            }
            $comment = htmlspecialchars($comment);
        }

        return $comment;
    }

    /**
     *
     */
    protected function saveLayerCache()
    {
        /** @var Amasty_Shopby_Helper_Layer_Cache $cache */
        $cache = Mage::helper('amshopby/layer_cache');
        $cache->saveLayerCache();
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        if($productsBlock = Mage::app()->getLayout()->getBlock('category.products')) {
            $productsBlock->getCmsBlockHtml();
        }

        $pos = Mage::getStoreConfig('amshopby/category_filter/block_pos');
        if ($this->_notInBlock($pos)) {
            $this->_categoryBlockName = 'amshopby/catalog_layer_filter_empty';
        }
        if (Mage::getStoreConfigFlag('amshopby/stock_filter/enable')) {
            $stockBlock = $this->getLayout()->createBlock('amshopby/catalog_layer_filter_stock')
                ->setLayer($this->getLayer())
                ->init();

            $this->setChild('stock_filter', $stockBlock);
        }

        if (Mage::getStoreConfigFlag('amshopby/rating_filter/enable')) {
            $ratingBlock = $this->getLayout()->createBlock('amshopby/catalog_layer_filter_rating')
                                ->setLayer($this->getLayer())
                                ->init();
            $this->setChild('rating_filter', $ratingBlock);
        }

        if (Mage::getStoreConfigFlag('amshopby/new_filter/enable')) {
            $newBlock = $this->getLayout()->createBlock('amshopby/catalog_layer_filter_new')
                ->setLayer($this->getLayer())
                ->init();
            $this->setChild('new_filter', $newBlock);
        }

        if (Mage::getStoreConfigFlag('amshopby/on_sale_filter/enable')) {
            $onSaleBlock = $this->getLayout()->createBlock('amshopby/catalog_layer_filter_onSale')
                ->setLayer($this->getLayer())
                ->init();
            $this->setChild('on_sale_filter', $onSaleBlock);
        }

        if (Mage::registry('amshopby_layout_prepared')){
            parent::_prepareLayout();
            $this->appendChildFilters();
            return $this;
        }
        else {
            Mage::register('amshopby_layout_prepared', true);
        }
        
        if (!Mage::getStoreConfigFlag('customer/startup/redirect_dashboard')) { 
            $url = Mage::helper('amshopby/url')->getFullUrl(Mage::app()->getRequest()->getParams());
            Mage::getSingleton('customer/session')
                ->setBeforeAuthUrl($url);           
        }
        
        $head = $this->getLayout()->getBlock('head');
        if ($head) {
            $head->addJs('amasty/amshopby/amshopby.js');
            if (Mage::getStoreConfig('amshopby/general/slider_use_ui')) {
                $this->addJQuery($head);
            }

            if (Mage::helper('amshopby')->isNeedAjax()) {
                $head->addJs('amasty/amshopby/amshopby-ajax.js');
            }

            if (Mage::helper('amshopby')->getIsApplyButtonEnabled()) {
                $head->addJs('amasty/amshopby/amshopby-apply.js');
            }
        }

        parent::_prepareLayout();

        $this->appendChildFilters();


        //$this->assertRequiredFilters($this->getFilters());

        return $this;
    }

    private function addJQuery($head)
    {
        $head->addJs('amasty/amshopby/jquery.min.js');
        $head->addJs('amasty/amshopby/jquery.noconflict.js');
        $head->addJs('amasty/amshopby/jquery-ui.min.js');
        $head->addJs('amasty/amshopby/jquery.ui.touch-punch.min.js');
        $head->addJs('amasty/amshopby/amshopby-jquery.js');
    }

    /**
     *
     */
    protected function appendChildFilters()
    {
        $filterableAttributes = $this->_getFilterableAttributes();
        foreach ($filterableAttributes as $attribute) {
            if ($attribute->getAttributeCode() == 'price' || $attribute->getBackendType() == 'decimal') {
                continue;
            }

            $block = $this->getChild($attribute->getAttributeCode() . '_filter');
            if (!$block) {
                continue;
            }
            $isShowChild = $block->getFilter() ? $block->getFilter()->getIsShowChildFilter() : false;
            if ($isShowChild) {
                $this->setChild($attribute->getAttributeCode() . '_filter_child',
                    $this->getLayout()->createBlock('amshopby/catalog_layer_filter_attribute_child')
                        ->setLayer($this->getLayer())
                        ->setAttributeModel($attribute)
                        ->init());
            }
        }
    }

    /**
     * @param $filters
     */
    protected function assertRequiredFilters($filters)
    {
        $requiredFilters = Mage::registry('amshopby_required_seo_filters');
        if (is_array($requiredFilters)) {
            foreach ($requiredFilters as &$requiredFilter) {
                $requiredFilter .= '_filter';
            }

            $actualAliases = array();
            foreach ($filters as $filter) {
                /** @var Mage_Catalog_Block_Layer_Filter_Abstract $filter */
                $actualAliases[] = $filter->getBlockAlias();
            }

            $diff = array_diff($requiredFilters, $actualAliases);
            if ($diff) {
                $this->_getDataHelper()->error404();
            }
        }
    }


    /**
     * @param $pos
     * @return bool
     */
    protected function _notInBlock($pos)
    {
        if (!in_array($pos, array('left', 'right', 'top','both'))){
            $pos = 'left';
        }
        return (!in_array($pos, array($this->_blockPos, Amasty_Shopby_Model_Source_Position::BOTH)));
    }

    /**
     * @return bool
     */
    protected function _isCurrentUserAgentExcluded()
    {
        /** @var Mage_Core_Helper_Http $helper */
        $helper = Mage::helper('core/http');
        $currentAgent = $helper->getHttpUserAgent();

        $excludeAgents = explode(',', Mage::getStoreConfig('amshopby/seo/exclude_user_agent'));
        foreach ($excludeAgents as $agent) {
            if (stripos($currentAgent, trim($agent)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed|string
     */
    public function getClearUrl()
    {
        /** @var Amasty_Shopby_Helper_Url $helper */
        $helper = Mage::helper('amshopby/url');
        $query = array();
        if ($helper->isOnBrandPage()) {
            $brandAttr = trim(Mage::getStoreConfig('amshopby/brands/attr'));
            $brandId = $this->getRequest()->getParam($brandAttr);
            if ($brandId) {
                $query[$brandAttr] = (int) $brandId;
            }
        }
		return $helper->getFullUrl($query, true);
	}

    /**
     * @return Amasty_Shopby_Helper_Data
     */
    protected function _getDataHelper()
    {
        /** @var Amasty_Shopby_Helper_Data $helper */
        $helper = Mage::helper('amshopby');
        return $helper;
    }

}
