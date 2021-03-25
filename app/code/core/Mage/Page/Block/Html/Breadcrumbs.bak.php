<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Page
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Html page block
 *
 * @category   Mage
 * @package    Mage_Page
 * @author      Magento Core Team <core@magentocommerce.com>
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
        $this->_prepareArray($crumbInfo, array('label', 'title', 'link', 'first', 'last', 'readonly'));
        if ((!isset($this->_crumbs[$crumbName])) || (!$this->_crumbs[$crumbName]['readonly'])) {
            if ($after && isset($this->_crumbs[$after])) {
                $offset = array_search($after, array_keys($this->_crumbs)) + 1;
                $this->_crumbs = array_slice($this->_crumbs, 0, $offset, true) + array($crumbName => $crumbInfo) + array_slice($this->_crumbs, $offset, null, true);
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
            $offset = array_search($before, $keys);
            # add before first
            if (!$offset) {
                $this->_prepareArray($crumbInfo, array('label', 'title', 'link', 'first', 'last', 'readonly'));
                $this->_crumbs = array($crumbName => $crumbInfo) + $this->_crumbs;
            } else {
                $this->addCrumb($crumbName, $crumbInfo, $keys[$offset-1]);
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
        if (null === $this->_cacheKeyInfo) {
            $this->_cacheKeyInfo = parent::getCacheKeyInfo() + array(
                'crumbs' => base64_encode(serialize($this->_crumbs)),
                'name'   => $this->getNameInLayout(),
            );
        }

        return $this->_cacheKeyInfo;
    }
    
    
    /* DHH CORE HACK */
    
    //https://stackoverflow.com/questions/12417499/making-consistent-breadcrumbs-on-individual-product-pages-in-magento
    
    /*
    protected function _toHtml() {
      $cat_id = "";
      if (Mage::registry('current_product')) {
        $product_id = Mage::registry('current_product')->getId();
        $obj = Mage::getModel('catalog/product');
        $_product = $obj->load($product_id); // Enter your Product Id in $product_id
        if ($product_id) {
           $categoryIds = $_product->getCategoryIds();
           $cat_id = $categoryIds[0];
        }
        $category = Mage::getModel('catalog/category')->load($cat_id);
        $cat_name = $category->getName();
        $cat_url =  $this->getBaseUrl().$category->getUrlPath();
      }
      if (is_array($this->_crumbs)) {
        reset($this->_crumbs);
        $this->_crumbs[key($this->_crumbs)]['first'] = true;
        end($this->_crumbs);
        $this->_crumbs[key($this->_crumbs)]['last'] = true;
      }
      if($cat_id) {
        $this->_crumbs['category'.$cat_id] = array('label'=>$cat_name, 'title'=>'', 'link'=>$cat_url,'first'=>'','last'=>'','readonly'=>'');
        ksort($this->_crumbs);
        $home = $this->_crumbs['home'];
        unset($this->_crumbs['home']);
        array_unshift($this->_crumbs,$home);
      }
      $this->assign('crumbs', $this->_crumbs);
      return parent::_toHtml();
    }
    */
    
    protected function _toHtml() {
      $cat_id = "";
      
      // For this to work, we reset the crumbs first except for home
      $this->_crumbs = [
        "home"      => $this->_crumbs["home"],
      ];
      
      $categoryIds = $fallback_categoryIds = [];
      
      if(Mage::registry('current_product')) {
        $product_id   = Mage::registry('current_product')->getId();
        // $_product     = Mage::getModel("catalog/product")->setId($product_id);
        $category_ids = Mage::registry('current_product')->getCategoryIds();
        
        if(empty($product_id) === false && isset($category_ids[0])) {
          
          $product_category_id = $category_ids[0];

          if(empty($this->_crumbs["category{$product_category_id}"]) === true) {
            // Breadcrumb is not complete yet, find out full category path and store IDs
            $collection = Mage::registry('current_product')->getCategoryCollection()
                          ->addAttributeToSelect('path')
                          ->addAttributeToSelect('name');
            
            // Prefers a category path without "promos", but picks it if there is no other option
            foreach($collection as $category) {
              $category_elements = explode("/", $category->getPath());
              $categoryIds = $category_elements;
              // if(in_array(834, $category_elements)) {
                // $fallback_categoryIds = $category_elements;
              // } else {
                // $categoryIds = $category_elements;
              // }
              
              // if($_SERVER["REMOTE_ADDR"] === "185.127.111.251" && isset($_GET['nofpc'])) {
                // echo "<pre>";
                // printr($category_elements);
                // echo "</pre>";
              // }
            }
            
            // if(empty($categoryIds) && !empty($fallback_categoryIds)) {
              // $categoryIds = $fallback_categoryIds;
            // }
            
          } else {
            // We're okay, the category ID exists in the current crumbs, so
            // do not change anything
          }
        }
      }
      
      // if($_SERVER["REMOTE_ADDR"] === "185.127.111.251" && isset($_GET['nofpc'])) {
        // echo "final categories:<pre>";
        // printr($categoryIds);
        // echo "</pre>";
      // }
      
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
      
      // if($_SERVER["REMOTE_ADDR"] === "185.127.111.251" && isset($_GET['nofpc'])) {
        // echo "<pre>";
        // printr(Mage::getModel('catalog/layer')->getCurrentCategory()->getId());
        // print_r($this->_crumbs);
        // echo "</pre>";
      // }
      
      return parent::_toHtml();
    }

    /**
     * @return string
     */
    /*
    protected function _toHtml()
    {
        if (is_array($this->_crumbs)) {
            reset($this->_crumbs);
            $this->_crumbs[key($this->_crumbs)]['first'] = true;
            end($this->_crumbs);
            $this->_crumbs[key($this->_crumbs)]['last'] = true;
        }
        $this->assign('crumbs', $this->_crumbs);
        return parent::_toHtml();
    }
    */
    
    
}
