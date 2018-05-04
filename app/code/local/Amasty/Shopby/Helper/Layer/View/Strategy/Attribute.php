<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


/**
 * Class Amasty_Shopby_Helper_Layer_View_Strategy_Attribute
 */
class Amasty_Shopby_Helper_Layer_View_Strategy_Attribute extends Amasty_Shopby_Helper_Layer_View_Strategy_Modeled
{
    /**
     * @return void
     */
    public function prepare()
    {
        parent::prepare();

        $this->prepareItems();
    }

    /**
     * @return string
     */
    protected function setTemplate()
    {
        $template = 'amasty/amshopby/attribute.phtml';

        if (is_object($this->model)) {
            $displayType = $this->model->getDisplayType();
            if ($displayType == Amasty_Shopby_Model_Source_Attribute::DT_MAGENTO_SWATCHES) {
                if ($this->isSwatchesAvailable()) {
                    $template = 'amasty/amshopby/swatches.phtml';
                } else {
                    $this->model->setDisplayType(Amasty_Shopby_Model_Source_Attribute::DT_LABELS_ONLY);
                }
            } elseif ($displayType == Amasty_Shopby_Model_Source_Attribute::DT_COLOR_SWATCHES_PRO) {
                $template = 'amasty/amshopby/swatches_pro.phtml';
            }
        }
        return $template;
    }

    /**
     * @return bool
     */
    protected function isSwatchesAvailable()
    {
        if (Mage::helper('amshopby')->isModuleEnabled('Mage_ConfigurableSwatches')) {
            /** @var Mage_ConfigurableSwatches_Helper_Data $configurableSwatchesHelper */
            $configurableSwatchesHelper = Mage::helper('configurableswatches');
            if ($configurableSwatchesHelper->isEnabled()) {
                if ($configurableSwatchesHelper->attrIsSwatchType($this->attribute->getId())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function setHasSelection()
    {
        $selected = $this->getSelectedValues();
        return !empty($selected);
    }

    /**
     * @return $this
     */
    protected function prepareItems()
    {
        $items = $this->filter->getItems();

        $options = $this->layer->getAttributeOptionsData();

        foreach ($items as $item){
            /** @var Amasty_Shopby_Model_Catalog_Layer_Filter_Item $item */

            $optId = $item->getOptionId();
            $item->setIsSelected(in_array($optId, $this->getSelectedValues()));

            if (!empty($options[$optId]['img'])){
                $item->setImage($options[$optId]['img']);
            }
            if (!empty($options[$optId]['img_hover'])){
                $item->setImageHover($options[$optId]['img_hover']);
                if ($item->getIsSelected()) {
                    $item->setImage($options[$optId]['img_hover']);
                }
            }
            if (!empty($options[$optId]['descr'])){
                $item->setDescr($options[$optId]['descr']);
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getSelectedValues()
    {
        return $this->_getDataHelper()->getRequestValues($this->attribute->getAttributeCode());
    }

    /**
     * @return array
     */
    protected function getTransferableFields()
    {
        return array('max_options', 'sort_by', 'sort_featured_first', 'display_type', 'single_choice',
            'seo_rel', 'depend_on_attribute', 'comment', 'show_search', 'number_options_for_show_search', 'seo_noindex');
    }

    /**
     * @return bool
     */
    public function getIsExcluded()
    {
        if (parent::getIsExcluded()) {
            return true;
        }

        // hide when selected
        $hideBySingleChoice = (
            defined('AMSHOPBY_FEATURE_HIDE_SINGLE_CHOICE_FILTERS')
            && AMSHOPBY_FEATURE_HIDE_SINGLE_CHOICE_FILTERS && $this->model->getSingleChoice()
        );
        $hideByConfigurableSwatches =
            $this->model->getDisplayType() == Amasty_Shopby_Model_Source_Attribute::DT_MAGENTO_SWATCHES
            && $this->isSwatchesAvailable()
            && $this->model->getSingleChoice();

        if ($hideBySingleChoice || $hideByConfigurableSwatches) {
            if (Mage::app()->getRequest()->getParam($this->attribute->getAttributeCode())) {
                return true;
            }
        }

        return false;
    }
}
