<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/
/**
 * @author Amasty
 */   
class Amasty_Shopby_Block_Adminhtml_Range extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_range';
    $this->_blockGroup = 'amshopby';
    $this->_headerText = Mage::helper('amshopby')->__('Ranges');
    $this->_addButtonLabel = Mage::helper('amshopby')->__('Add Range');
    parent::__construct();
  }
}