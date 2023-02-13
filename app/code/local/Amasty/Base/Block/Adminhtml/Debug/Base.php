<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Magento 1 Base Package
*/

class Amasty_Base_Block_Adminhtml_Debug_Base extends Mage_Adminhtml_Block_Widget_Form
{
    public function getClassPath($rewrites, $codePool, $rewriteIndex)
    {
        return Amasty_Base_Model_Conflict::getClassPath($codePool[$rewriteIndex], $rewrites[$rewriteIndex]);
    }
}
