<?php
/**
 * OpenMage
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available at https://opensource.org/license/osl-3-0-php
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://www.magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://www.openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Search Catalog Model
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 *
 * @method bool hasLimit()
 * @method int getLimit()
 * @method bool hasQuery()
 * @method string getQuery()
 * @method bool setResults(array $value)
 * @method bool hasStart()
 * @method int getStart()
 */
class Mage_Adminhtml_Model_Search_Catalog extends Varien_Object
{
    /**
     * Load search results
     *
     * @return $this
     */
    public function load()
    {
        $arr = [];

        if (!$this->hasStart() || !$this->hasLimit() || !$this->hasQuery()) {
            $this->setResults($arr);
            return $this;
        }

        // DHH CORE HACK -- Limiting fields, using other collection
        $query = $this->getQuery();
        $collection = Mage::getResourceModel("catalog/product_collection")
            ->addAttributeToSelect("entity_id")
            ->addAttributeToSelect("name")
            ->addAttributeToFilter([
                ["attribute" => "sku",        "like" => "%{$query}%"],
                ["attribute" => "sku_seller", "eq" => "{$query}"],
                ["attribute" => "ean",        "eq" => "{$query}"],
                ["attribute" => "ean13",      "eq" => "{$query}"],
            ])
            ->setCurPage($this->getStart())
            ->setPageSize($this->getLimit())
            ->load()
        ;
        
        foreach ($collection as $product) {
            $arr[]          = [
                'id'            => 'product/1/' . $product->getId(),
                'type'          => Mage::helper('adminhtml')->__('Product'),
                'name'          => $product->getName(),
                'description'   => "",
                'url'           => Mage::helper('adminhtml')->getUrl('*/catalog_product/edit', ['id' => $product->getId()]),
            ];
        }

        $this->setResults($arr);

        return $this;
    }

}
