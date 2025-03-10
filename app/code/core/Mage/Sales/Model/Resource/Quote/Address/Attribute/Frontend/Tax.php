<?php

/**
 * OpenMage
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available at https://opensource.org/license/osl-3-0-php
 *
 * @category   Mage
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://www.magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://www.openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Quote address attribute frontend tax resource model
 *
 * @category   Mage
 * @package    Mage_Sales
 */
class Mage_Sales_Model_Resource_Quote_Address_Attribute_Frontend_Tax extends Mage_Sales_Model_Resource_Quote_Address_Attribute_Frontend
{
    /**
     * Fetch totals
     *
     * @return $this
     */
    public function fetchTotals(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getTaxAmount();
        if ($amount != 0) {
            $address->addTotal([
                'code'  => 'tax',
                'title' => Mage::helper('sales')->__('Tax'),
                'value' => $amount,
            ]);
        }
        return $this;
    }
}
