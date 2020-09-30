<?php
/**
 *
 * CART2QUOTE CONFIDENTIAL
 * __________________
 *
 *  [2009] - [2020] Cart2Quote B.V.
 *  All Rights Reserved.
 *
 * NOTICE OF LICENSE
 *
 * All information contained herein is, and remains
 * the property of Cart2Quote B.V. and its suppliers,
 * if any.  The intellectual and technical concepts contained
 * herein are proprietary to Cart2Quote B.V.
 * and its suppliers and may be covered by European and Foreign Patents,
 * patents in process, and are protected by trade secret or copyright law.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Cart2Quote B.V.
 *
 * @category    Ophirah
 * @package     Qquoteadv
 * @copyright   Copyright (c) 2020 Cart2Quote B.V. (https://www.cart2quote.com)
 * @license     https://www.cart2quote.com/ordering-licenses(https://www.cart2quote.com)
 */

/**
 * Class Ophirah_Qquoteadv_Model_Mysql4_Qqadvcustomer_Collection
 */
class Ophirah_Qquoteadv_Model_Mysql4_Qqadvcustomer_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Construct
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('qquoteadv/qqadvcustomer');
    }

    /**
     * Function that adds an ORDER BY clause to the select
     *
     * @param $attribute
     * @param $dir
     * @return $this
     */
    public function order($attribute, $dir = self::SORT_ORDER_DESC)
    {
        $this->_select->order($attribute . ' ' . $dir);
        return $this;
    }

    /**
     * Function that adds a joinLeft to the select
     * DEPRECATED?
     * @return $this
     */
    public function joinTable()
    {
        Mage::log(
            'DEPRECATED: ' . __METHOD__ . ' in ' . __FILE__ . '(' . __LINE__ . ')',
            null,
            'c2q_deprecated.log',
            true
        );

        $this->_select->joinLeft('qquote_product', 'main_table.quote_id = qquote_product.quote_id');
        return $this;
    }
}
