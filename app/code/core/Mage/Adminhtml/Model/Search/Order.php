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
 * Search Order Model
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
class Mage_Adminhtml_Model_Search_Order extends Varien_Object
{
    /**
     * Load search results
     *
     * @return $this
     */
    public function load()
    {
        $results = [];

        if (!$this->hasStart() || !$this->hasLimit() || !$this->hasQuery()) {
            $this->setResults($results);
            return $this;
        }

        $query = $this->getQuery()."%";
        $query_wc = "%".$this->getQuery()."%";
        //TODO: add full name logic
        $collection = Mage::getResourceModel("sales/order_collection")
            // DHH CORE HACK -- Limiting fields
            // ->addAttributeToSelect("*")
            ->addAttributeToSelect("increment_id")
            ->addAttributeToSelect("entity_id")
            ->addAttributeToSearchFilter([
                ["attribute" => "increment_id",       "like" => $query],
                ["attribute" => "tm_field1",          "like" => $query_wc],
                ["attribute" => "tm_field6",          "like" => $query],
                ["attribute" => "tm_field8",          "like" => $query_wc],
                ["attribute" => "customer_email",     "like" => $query],
                // ["attribute" => "billing_firstname",  "like" => $query_wc],
                // ["attribute" => "billing_lastname",   "like" => $query_wc],
                // ["attribute" => "billing_telephone",  "like" => $query_wc],
                // ["attribute" => "billing_postcode",   "like" => $query_wc],
                // ["attribute" => "shipping_firstname", "like" => $query_wc],
                // ["attribute" => "shipping_lastname",  "like" => $query_wc],
                // ["attribute" => "shipping_telephone", "like" => $query_wc],
                // ["attribute" => "shipping_postcode",  "like" => $query_wc],
            ])
            ->setCurPage($this->getStart())
            ->setPageSize($this->getLimit())
            ->load();
            // var_dump($collection->getSelect()->__toString());

        foreach ($collection as $order) {
            $billing_address = $order->getBillingAddress();
            $billing_fullname = trim($billing_address->getFirstname().' '.$order->getLastname());
            $order_id = $order->getId();
            $order_increment_id = $order->getIncrementId();
            $results[] = [
                'id'                => 'order/1/'.$order_id,
                'type'              => Mage::helper('adminhtml')->__('Order'),
                'name'              => Mage::helper('adminhtml')->__('Order #%s', $order_increment_id),
                'description'       => $billing_fullname,
                'form_panel_title'  => Mage::helper('adminhtml')->__('Order #%s (%s)', $order_increment_id, $billing_fullname),
                'url'               => Mage::helper('adminhtml')->getUrl('*/sales_order/view', ['order_id' => $order_id]),
            ];
        }

        $this->setResults($results);

        return $this;
    }
}
