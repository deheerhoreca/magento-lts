<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Magento 1 Base Package
*/

/**
 * Class Amasty_Base_Block_Adminhtml_Support
 *
 * @method Amasty_Base_Block_Adminhtml_Support setClass($class)
 * @method Amasty_Base_Block_Adminhtml_Support setContent($content)
 * @method mixed getContent()
 */
class Amasty_Base_Block_Adminhtml_Support extends Mage_Adminhtml_Block_Template
{
    public function getClass()
    {
        if ($class = $this->getData('class')) {
            return $class;
        } else {
            return 'ambase-notice';
        }
    }
}
