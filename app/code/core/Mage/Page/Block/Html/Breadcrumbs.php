<?php
/**
 * OpenMage
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    Mage_Page
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://www.magento.com)
 * @copyright  Copyright (c) 2018-2022 The OpenMage Contributors (https://www.openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Html page block
 *
 * @category   Mage
 * @package    Mage_Page
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Page_Block_Html_Breadcrumbs extends Mage_Core_Block_Template
{
    /**
     * Array of breadcrumbs
     *
     * array(
     *  [$index] => array(
     *                  ['label']
     *                  ['title']
     *                  ['link']
     *                  ['first']
     *                  ['last']
     *              )
     * )
     *
     * @var array
     */
    protected $_crumbs = null;

    /**
     * Cache key info
     *
     * @var null|array
     */
    protected $_cacheKeyInfo = null;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('page/html/breadcrumbs.phtml');
    }

    /**
     * @param string $crumbName
     * @param array $crumbInfo
     * @param bool $after
     * @return $this
     */
    public function addCrumb($crumbName, $crumbInfo, $after = false)
    {
        $this->_prepareArray($crumbInfo, ['label', 'title', 'link', 'first', 'last', 'readonly']);
        if ((!isset($this->_crumbs[$crumbName])) || (!$this->_crumbs[$crumbName]['readonly'])) {
            if ($after && isset($this->_crumbs[$after])) {
                $offset = array_search($after, array_keys($this->_crumbs), true) + 1;
                $this->_crumbs = array_slice($this->_crumbs, 0, $offset, true) + [$crumbName => $crumbInfo] + array_slice($this->_crumbs, $offset, null, true);
            } else {
                $this->_crumbs[$crumbName] = $crumbInfo;
            }
        }
        return $this;
    }

    /**
     * @param string $crumbName
     * @param array $crumbInfo
     * @param bool $before
     */
    public function addCrumbBefore($crumbName, $crumbInfo, $before = false)
    {
        if ($before && isset($this->_crumbs[$before])) {
            $keys = array_keys($this->_crumbs);
            $offset = array_search($before, $keys, true);
            # add before first
            if (!$offset) {
                $this->_prepareArray($crumbInfo, ['label', 'title', 'link', 'first', 'last', 'readonly']);
                $this->_crumbs = [$crumbName => $crumbInfo] + $this->_crumbs;
            } else {
                $this->addCrumb($crumbName, $crumbInfo, $keys[$offset - 1]);
            }
        } else {
            $this->addCrumb($crumbName, $crumbInfo);
        }
    }

    /**
     * @param string $crumbName
     */
    public function removeCrumb($crumbName)
    {
        if (isset($this->_crumbs[$crumbName])) {
            unset($this->_crumbs[$crumbName]);
        }
    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        if ($this->_cacheKeyInfo === null) {
            $this->_cacheKeyInfo = parent::getCacheKeyInfo() + [
                'crumbs' => base64_encode(serialize($this->_crumbs)),
                'name'   => $this->getNameInLayout(),
                ];
        }

        return $this->_cacheKeyInfo;
    }
    
    // DHH CORE HACK
    // @see https://stackoverflow.com/questions/12417499/making-consistent-breadcrumbs-on-individual-product-pages-in-magento
    
    protected function _toHtml() {
      $cat_id       = "";
      $categoryIds  = [];
      
      if(Mage::registry('current_product')) {
        $product_id = Mage::registry('current_product')->getId();
        $_product = Mage::getModel("catalog/product")->setId($product_id);
        if(isset($_product->getCategoryIds()[0])) {
          $product_category_id = $_product->getCategoryIds()[0];

          if($product_id) {
            if(empty($this->_crumbs["category{$product_category_id}"]) === true) {
              // Breadcrumb is not complete yet, find out full category path and store IDs
              $collection = $_product->getCategoryCollection()->addAttributeToSelect('path');
              foreach($collection as $category) {
                $categoryIds = explode("/", $category->getPath());
              }
              // echo "<pre>";
              // print_r($categoryIds);
              // echo "</pre>";
            } else {
              // We're okay, the category ID exists in the current crumbs, so
              // do not change anything
            }
          }
        }
      }
      
      if(empty($categoryIds) === false) {
        foreach($categoryIds as $key => $category_id) {
          if($category_id == 0) continue; // Root
          if($category_id == 1) continue; // 
          if($category_id == 2) continue; // 
          $category = Mage::getModel('catalog/category')->load($category_id);
          $cat_name = $category->getName();
          $cat_url =  $this->getBaseUrl().$category->getUrlPath();
          
          $this->_crumbs['category'.$category_id] = [
            'label'    => $cat_name,
            'title'    => '',
            'link'     => $cat_url,
            'first'    => '',
            'last'     => '',
            'readonly' => '',
          ];
        }
        $home = $this->_crumbs['home'];
        unset($this->_crumbs['home']);
        $this->_crumbs = ["home" => $home] + $this->_crumbs; // unshift while keeping key intact
      }
      
      if(is_array($this->_crumbs)) {
        reset($this->_crumbs);
        $this->_crumbs[key($this->_crumbs)]['first'] = true;
        end($this->_crumbs);
        $this->_crumbs[key($this->_crumbs)]['last'] = true;
      }
      
      $this->assign('crumbs', $this->_crumbs);
      
      return parent::_toHtml();
    }
    
    /**
     * @return string
     */
    // protected function _toHtml()
    // {
        // if (is_array($this->_crumbs)) {
            // reset($this->_crumbs);
            // $this->_crumbs[key($this->_crumbs)]['first'] = true;
            // end($this->_crumbs);
            // $this->_crumbs[key($this->_crumbs)]['last'] = true;
        // }
        // $this->assign('crumbs', $this->_crumbs);
        // return parent::_toHtml();
    // }
}
