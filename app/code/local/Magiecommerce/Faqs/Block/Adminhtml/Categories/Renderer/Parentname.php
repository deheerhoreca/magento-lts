<?php
class Magiecommerce_Faqs_Block_Adminhtml_Categories_Renderer_Parentname extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
{
$value =  $row->getData($this->getColumn()->getIndex());
if($value==0)
{
    $value1 = "Root Category";
return $value1;
}
else
    {
    //echo "<pre>"; print_r($row);die;
   $catid = $row->getParentcatid();
    $collection = Mage::getModel('faqs/Categories')->load($catid);
    //echo "<pre>"; print_r($collection->getData());die;
    $catname = $collection->getCatName();
    return $catname;
    }

}
}