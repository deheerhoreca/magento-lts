<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_Adminhtml
 */

/**
 * Adminhtml Catalog helper
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Helper_Catalog extends Mage_Core_Helper_Abstract
{
    public const XML_PATH_SITEMAP_VALID_PATHS = 'general/file/sitemap_generate_valid_paths';

    protected $_moduleName = 'Mage_Adminhtml';

    /**
     * Attribute Tab block name for product edit
     *
     * @var string|null
     */
    protected $_attributeTabBlock = null;

    /**
     * Attribute Tab block name for category edit
     *
     * @var string
     */
    protected $_categoryAttributeTabBlock;

    /**
     * Retrieve Attribute Tab Block Name for Product Edit
     *
     * @return string|null
     */
    public function getAttributeTabBlock()
    {
        return $this->_attributeTabBlock;
    }

    /**
     * Set Custom Attribute Tab Block Name for Product Edit
     *
     * @param string $attributeTabBlock
     * @return $this
     */
    public function setAttributeTabBlock($attributeTabBlock)
    {
        $this->_attributeTabBlock = $attributeTabBlock;
        return $this;
    }

    /**
     * Retrieve Attribute Tab Block Name for Category Edit
     *
     * @return string
     */
    public function getCategoryAttributeTabBlock()
    {
        return $this->_categoryAttributeTabBlock;
    }

    /**
     * Set Custom Attribute Tab Block Name for Category Edit
     *
     * @param string $attributeTabBlock
     * @return $this
     */
    public function setCategoryAttributeTabBlock($attributeTabBlock)
    {
        $this->_categoryAttributeTabBlock = $attributeTabBlock;
        return $this;
    }

    /**
     * Get list valid paths for generate a sitemap XML file
     *
     * @return array
     */
    public function getSitemapValidPaths()
    {
        $path = Mage::getStoreConfig(self::XML_PATH_SITEMAP_VALID_PATHS);
        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');
        return array_merge($path, $helper->getPublicFilesValidPath());
    }
}
