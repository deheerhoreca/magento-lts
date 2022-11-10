<?php

/**
 * Fooman SameOrderInvoiceNumber
 *
 * @package   Fooman_SameOrderInvoiceNumber
 * @author    Kristof Ringleff <kristof@fooman.co.nz>
 * @copyright Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Fooman_SameOrderInvoiceNumber_Model_Observer
{

    /**
     * change the invoice increment to the order increment id
     * only affects invoices without id (=new invoices)
     *
     * @param Varien_Event_Observer $observer
     */
    public function sales_order_invoice_save_before($observer)
    {
        $invoice = $observer->getInvoice();
        if (!$invoice->getId()) {
            $order = $invoice->getOrder();
            $storeId = $order->getStore()->getStoreId();
            $prefix = Mage::getStoreConfig(
                'sameorderinvoicenumber/settings/invoiceprefix',
                $storeId
            );
            $newInvoiceNr = 0;
            $currentPostfix = 0;
            while (!$newInvoiceNr) {
                if ($currentPostfix) {
                    $newInvoiceNr = $prefix . $order->getIncrementId() . '-' . $currentPostfix;
                } else {
                    $newInvoiceNr = $prefix . $order->getIncrementId();
                }
                $collection = Mage::getModel('sales/order_invoice')->getCollection()->addFieldToFilter(
                    'increment_id',
                    $newInvoiceNr
                );
                if ($collection->getAllIds()) {
                    //number already exists
                    $newInvoiceNr = 0;
                    $currentPostfix++;
                } else {
                    $invoice->setIncrementId($newInvoiceNr);
                }
            }
        }
    }

    /**
     * change the shipment increment to the order increment id
     * only affects shipments without id (=new shipments)
     *
     * @param Varien_Event_Observer $observer
     */
    public function sales_order_shipment_save_before($observer)
    {
        $shipment = $observer->getShipment();
        if (!$shipment->getId()) {
            $order = $shipment->getOrder();
            $storeId = $order->getStore()->getStoreId();
            $prefix = Mage::getStoreConfig(
                'sameorderinvoicenumber/settings/shipmentprefix',
                $storeId
            );
            $newShipmentNr = 0;
            $currentPostfix = 0;
            while (!$newShipmentNr) {
                if ($currentPostfix) {
                    $newShipmentNr = $prefix . $order->getIncrementId() . '-' . $currentPostfix;
                } else {
                    $newShipmentNr = $prefix . $order->getIncrementId();
                }
                $collection = Mage::getModel('sales/order_shipment')->getCollection()->addFieldToFilter(
                    'increment_id',
                    $newShipmentNr
                );
                if ($collection->getAllIds()) {
                    //number already exists
                    $newShipmentNr = 0;
                    $currentPostfix++;
                } else {
                    $shipment->setIncrementId($newShipmentNr);
                }
            }
        }
    }

    /**
     * change the creditmemo increment to the order increment id
     * only affects creditmemos without id (=new creditmemos)
     *
     * @param Varien_Event_Observer $observer
     */
    public function sales_order_creditmemo_save_before($observer)
    {
        $creditmemo = $observer->getCreditmemo();
        if (!$creditmemo->getId()) {
            $order = $creditmemo->getOrder();
            $storeId = $order->getStore()->getStoreId();
            $prefix = Mage::getStoreConfig(
                'sameorderinvoicenumber/settings/creditmemoprefix',
                $storeId
            );
            $newCreditmemoNr = 0;
            $currentPostfix = 0;
            while (!$newCreditmemoNr) {
                if ($currentPostfix) {
                    $newCreditmemoNr = $prefix . $order->getIncrementId() . '-' . $currentPostfix;
                } else {
                    $newCreditmemoNr = $prefix . $order->getIncrementId();
                }
                $collection = Mage::getModel('sales/order_creditmemo')->getCollection()->addFieldToFilter(
                    'increment_id',
                    $newCreditmemoNr
                );
                if ($collection->getAllIds()) {
                    //number already exists
                    $newCreditmemoNr = 0;
                    $currentPostfix++;
                } else {
                    $creditmemo->setIncrementId($newCreditmemoNr);
                }
            }
        }
    }

}
