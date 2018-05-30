<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Autorelated
 * @version    2.5.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */

$installer = $this;
$installer->startSetup();

try {
    if ($this->getTable('admin/permission_block')) {
        $this->getConnection()->insertMultiple(
            $this->getTable('admin/permission_block'),
            array(
                array('block_name' => 'awautorelated/blocks', 'is_allowed' => 1),
                )
        );
    }
} catch (Exception $e) {
    Mage::logException($e);
}

$blocksTable = $this->getTable('awautorelated/blocks');

/** @var Varien_Db_Adapter_Interface $dbConnection */
$dbConnection = $this->getConnection();

if (!$dbConnection->tableColumnExists($blocksTable, 'stop_further')) {
    /* Add stop_further column */
    $dbConnection->addColumn(
        $blocksTable,
        'stop_further',
        'TINYINT NOT NULL DEFAULT 0 AFTER `priority`'
    );
}

$installer->endSetup();