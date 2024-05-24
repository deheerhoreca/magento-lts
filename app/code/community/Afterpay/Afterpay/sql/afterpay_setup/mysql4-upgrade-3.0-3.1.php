<?php
/**
 * Copyright (c) 2011-2022  arvato Finance B.V.
 *
 * AfterPay reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of AfterPay.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    AfterPay
 * @package     Afterpay_Afterpay
 * @copyright   Copyright (c) 2011-2022 arvato Finance B.V.
 */

/** @var $installer Mage_Catalog_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

/**
 * Update SOAP payment methods titles and taglines
 */
$installer->run(
    "
    UPDATE `{$installer->getTable('core_config_data')}` SET `value` = 'Achteraf betalen'
    WHERE `path` = 'afterpay/afterpay_afterpay_nl_digital_invoice/portfolio_label';
            
    UPDATE `{$installer->getTable('core_config_data')}` SET `value` = 'Automatische incasso'
    WHERE `path` = 'afterpay/afterpay_afterpay_nl_direct_debit/portfolio_label';

    UPDATE `{$installer->getTable('core_config_data')}` SET `value` = 'Achteraf betalen'
    WHERE `path` = 'afterpay/afterpay_afterpay_nl_business/portfolio_label';

    UPDATE `{$installer->getTable('core_config_data')}` SET `value` = 'Shop nu, betaal later'
    WHERE `path` = 'afterpay/afterpay_afterpay_nl_digital_invoice/portfolio_footnote';

    UPDATE `{$installer->getTable('core_config_data')}` SET `value` = 'Betaal via een automatische afschrijving van je rekening'
    WHERE `path` = 'afterpay/afterpay_afterpay_nl_direct_debit/portfolio_footnote';

    UPDATE `{$installer->getTable('core_config_data')}` SET `value` = 'Shop nu, betaal later'
    WHERE `path` = 'afterpay/afterpay_afterpay_nl_business/portfolio_footnote';

    UPDATE `{$installer->getTable('core_config_data')}` SET `value` = 'Achteraf betalen'
    WHERE `path` = 'afterpay/afterpay_afterpay_be_digital_invoice/portfolio_label';

    UPDATE `{$installer->getTable('core_config_data')}` SET `value` = 'Shop nu, betaal later'
    WHERE `path` = 'afterpay/afterpay_afterpay_be_digital_invoice/portfolio_footnote';
    "
);

$installer->endSetup();
