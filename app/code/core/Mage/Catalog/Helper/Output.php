<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_Catalog
 */

/**
 * @package    Mage_Catalog
 */
class Mage_Catalog_Helper_Output extends Mage_Core_Helper_Abstract
{
    protected $_moduleName = 'Mage_Catalog';

    /**
     * Array of existing handlers
     *
     * @var array
     */
    protected $_handlers;

    /**
     * Template processor instance
     *
     * @var Varien_Filter_Template
     */
    protected $_templateProcessor = null;

    public function __construct()
    {
        Mage::dispatchEvent('catalog_helper_output_construct', ['helper' => $this]);
    }

    /**
     * @return Varien_Filter_Template
     */
    protected function _getTemplateProcessor()
    {
        if ($this->_templateProcessor === null) {
            $this->_templateProcessor = Mage::helper('catalog')->getPageTemplateProcessor();
        }

        return $this->_templateProcessor;
    }

    /**
     * Adding method handler
     *
     * @param   string $method
     * @param   object $handler
     * @return  Mage_Catalog_Helper_Output
     */
    public function addHandler($method, $handler)
    {
        if (!is_object($handler)) {
            return $this;
        }
        $method = strtolower($method);

        if (!isset($this->_handlers[$method])) {
            $this->_handlers[$method] = [];
        }

        $this->_handlers[$method][] = $handler;
        return $this;
    }

    /**
     * Get all handlers for some method
     *
     * @param   string $method
     * @return  array
     */
    public function getHandlers($method)
    {
        $method = strtolower($method);
        return $this->_handlers[$method] ?? [];
    }

    /**
     * Process all method handlers
     *
     * @param string $method
     * @param mixed $result
     * @param array $params
     * @return mixed
     */
    public function process($method, $result, $params)
    {
        foreach ($this->getHandlers($method) as $handler) {
            if (method_exists($handler, $method)) {
                $result = $handler->$method($this, $result, $params);
            }
        }
        return $result;
    }

    /**
     * Prepare product attribute html output
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   string $attributeHtml
     * @param   string $attributeName
     * @return  string
     */
    public function productAttribute($product, $attributeHtml, $attributeName)
    {
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
        $attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeName);
        if ($attribute && $attribute->getId() && ($attribute->getFrontendInput() != 'media_image')
            && (!$attribute->getIsHtmlAllowedOnFront() && !$attribute->getIsWysiwygEnabled())
        ) {
            if ($attribute->getFrontendInput() != 'price') {
                $attributeHtml = $this->escapeHtml($attributeHtml);
            }
            if ($attribute->getFrontendInput() == 'textarea') {
                $attributeHtml = nl2br($attributeHtml);
            }
        }
        if ($attribute->getIsHtmlAllowedOnFront() && $attribute->getIsWysiwygEnabled()) {
            if (Mage::helper('catalog')->isUrlDirectivesParsingAllowed()) {
                $attributeHtml = $this->_getTemplateProcessor()->filter($attributeHtml);
            }
        }

        return $this->process('productAttribute', $attributeHtml, [
            'product'   => $product,
            'attribute' => $attributeName,
        ]);
    }

    /**
     * Prepare category attribute html output
     *
     * @param   Mage_Catalog_Model_Category $category
     * @param   string $attributeHtml
     * @param   string $attributeName
     * @return  string
     */
    public function categoryAttribute($category, $attributeHtml, $attributeName)
    {
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
        $attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY, $attributeName);

        if ($attribute && ($attribute->getFrontendInput() != 'image')
            && (!$attribute->getIsHtmlAllowedOnFront() && !$attribute->getIsWysiwygEnabled())
        ) {
            $attributeHtml = $this->escapeHtml($attributeHtml);
        }
        if ($attribute->getIsHtmlAllowedOnFront() && $attribute->getIsWysiwygEnabled()) {
            if (Mage::helper('catalog')->isUrlDirectivesParsingAllowed()) {
                $attributeHtml = $this->_getTemplateProcessor()->filter($attributeHtml);
            }
        }
        return $this->process('categoryAttribute', $attributeHtml, [
            'category'  => $category,
            'attribute' => $attributeName,
        ]);
    }
}
