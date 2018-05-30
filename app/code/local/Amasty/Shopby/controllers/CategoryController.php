<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


require_once Mage::getModuleDir('controllers','Mage_Catalog').DS.'CategoryController.php';

class Amasty_Shopby_CategoryController extends Mage_Catalog_CategoryController
{
    private $category;

    protected function _initCategory()
    {
        if (!Mage::registry('current_category')) {
            $this->category = parent::_initCategory();
        }

        return $this->category;
    }

    /**
     * method name was fixed in magento 1.9.3.(?7)
     */
    protected function _initCatagory()
    {
        if (!Mage::registry('current_category')) {
            $this->category = parent::_initCatagory();
        }

        return $this->category;
    }

    public function viewAction()
    {
        if (!$category = $this->_initCatagory()) {
            return parent::viewAction();
        }

        $pageResource = Mage::getResourceModel('amshopby/page');
        $page = $pageResource->getCurrentMatchedPage($category->getId());
        if ($page) {
            Mage::register('amshopby_page', $page);
            $this->getLayout()->getUpdate()->addUpdate($page->getCustomLayoutUpdateXml());
        }

        parent::viewAction();
    }
}
