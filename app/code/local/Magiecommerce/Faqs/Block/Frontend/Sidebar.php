<?php
class Magiecommerce_Faqs_Block_Frontend_Sidebar extends Mage_Core_Block_Template {
    public function __construct() {

    }
public function getAllcategory()
{

        $currentStoreId = Mage::app()->getStore(true)->getId();
        $categories =  Mage::getModel('faqs/categories')->getCollection()
                ->addFieldToFilter('status','1')
                ->addFieldToFilter('parentcatid','0')
                ->addFieldToFilter('store_id', array(
              array('finset' => $currentStoreId),
              array('finset' => '0'))
              );
        $categories->addOrder('cat_sortorder','ASC');
        return $categories;


}
public function getParantCategory($current_catid)
{
  $parent_id = Mage::getModel('faqs/categories')->load($current_catid)->getParentcatid();
  if($parent_id){
    return $parent_id;
  }
  return $current_catid;
}
public function getAllsubcategory($cat_id)
{

  $currentStoreId = Mage::app()->getStore(true)->getId();
  $categories =  Mage::getModel('faqs/categories')->getCollection()
          ->addFieldToFilter('status','1')
           ->addFieldToFilter('parentcatid',$cat_id)
          ->addFieldToFilter('store_id', array(
        array('finset' => $currentStoreId),
        array('finset' => '0'))
        );
  $categories->addOrder('cat_sortorder','ASC');
  return $categories;


}
}