<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_Cms
 */

/**
 * CMS block model
 *
 * @package    Mage_Cms
 *
 * @method Mage_Cms_Model_Resource_Block _getResource()
 * @method Mage_Cms_Model_Resource_Block getResource()
 * @method Mage_Cms_Model_Resource_Block_Collection getCollection()
 *
 * @method string getTitle()
 * @method $this setTitle(string $value)
 * @method string getIdentifier()
 * @method $this setIdentifier(string $value)
 * @method string getContent()
 * @method $this setContent(string $value)
 * @method string getCreationTime()
 * @method $this setCreationTime(string $value)
 * @method string getUpdateTime()
 * @method $this setUpdateTime(string $value)
 * @method int getIsActive()
 * @method $this setIsActive(int $value)
 * @method $this setStoreId(int $storeId)
 * @method int getStoreId()
 * @method int getBlockId()
 */
class Mage_Cms_Model_Block extends Mage_Core_Model_Abstract
{
    public const CACHE_TAG     = 'cms_block';
    protected $_cacheTag = 'cms_block';

    protected function _construct()
    {
        $this->_init('cms/block');
    }

    /**
     * Prevent blocks recursion
     *
     * @throws Mage_Core_Exception
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        $needle = 'block_id="' . $this->getBlockId() . '"';
        if (!strstr($this->getContent(), $needle)) {
            return parent::_beforeSave();
        }
        Mage::throwException(
            Mage::helper('cms')->__('The static block content cannot contain  directive with its self.'),
        );
    }
}
