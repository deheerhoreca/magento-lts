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
 * @copyright  Copyright (c) 2020-2023 The OpenMage Contributors (https://www.openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales Order Invoice PDF model
 *
 * @category   Mage
 * @package    Mage_Sales
 */
class Mage_Sales_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Abstract
{
    /**
     * Draw header for item table
     *
     * @param Zend_Pdf_Page $page
     */
    protected function _drawHeader(Zend_Pdf_Page $page)
    {
        /* Add table head */
        $this->_setFontRegular($page, 10);
        $page->setFillColor(new Zend_Pdf_Color_Html('#5180c2'));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 15);
        $this->y -= 10;
        $page->setFillColor(new Zend_Pdf_Color_RGB(255, 255, 255));

        //columns headers
        $lines[0][] = [
            'text' => Mage::helper('sales')->__('Product'),
            'feed' => 25,
            'font' => 'bold',
        ];

        $lines[0][] = [
            'text'  => 'SKU',
            'feed'  => 260,
            'align' => 'left',
            'font'  => 'bold',
        ];

        $lines[0][] = [
            'text'  => Mage::helper('sales')->__('Price'),
            'feed'  => 430,
            'align' => 'right',
            'font'  => 'bold',
        ];

        $lines[0][] = [
            'text'  => Mage::helper('sales')->__('Qty'),
            'feed'  => 465,
            'align' => 'center',
            'font'  => 'bold',
        ];

        $lines[0][] = [
            'text'  => Mage::helper('sales')->__('Subtotal'),
            'feed'  => 560,
            'align' => 'right',
            'font'  => 'bold',
        ];

        $lineBlock = [
            'lines'  => $lines,
            'height' => 5,
        ];

        $this->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }

    /**
     * Return PDF document
     *
     * @param  Mage_Sales_Model_Order_Invoice[] $invoices
     * @return Zend_Pdf
     */
    public function getPdf($invoices = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }
            $page  = $this->newPage();
            $order = $invoice->getOrder();
            /* Add image */
            $this->insertLogo($page, $invoice->getStore());
            /* Add address */
            $this->insertAddress($page, $invoice->getStore());
            
            /* DHH BEGIN */
            $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoice->getIncrementId());
      			$createdDate = $invoice->getCreatedAt();
      			$invoiceDate = date('d M Y', strtotime($createdDate));

            $this->y = $this->y ? $this->y : 815;
            $top = $this->y;
      			$page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
      			// $font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir().'/font/CORBEL.TTF');
      			// $page->setFont($font, 10);
      			$page->drawText(Mage::helper('sales')->__('Factuurdatum: ') . $invoiceDate, 35, $top, 'UTF-8');
      			//$page->drawText(Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId(), 25, 740, 'UTF-8');
      			//$page->drawText(Mage::helper('sales')->__('Order # ') . $order->getRealOrderId(), 25, 725, 'UTF-8');
            /* DHH END */
            
            /* Add head */
            $this->insertOrder(
                $page,
                $order,
                Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId())
            );
            /* Add document text and number */
            $this->insertDocumentNumber(
                $page,
                Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId()
            );
            
            /* DHH BEGIN */
            if(intval($invoice->getStoreId()) === 4) {
              $page->drawText("De factuur is reeds betaald via bol.com", 200, $top-20, 'UTF-8');
            } else {
              $page->drawText("Indien van toepassing verzoeken u vriendelijk het verschuldigde bedrag", 200, $top-10, 'UTF-8');
              $page->drawText("binnen 14 dagen over te maken onder vermelding van het factuurnummer.", 200, $top-20, 'UTF-8');
              $page->drawText("Onze algemene voorwaarden zijn van toepassing en kunt u vinden op onze website", 200, $top-35, 'UTF-8');
              if($order->getShippingAddress()->getCountryId() === "BE") {
                $page->drawText("Zakelijke leveringen in België: Domestic charge, BTW verlegd", 200, $top-48, 'UTF-8');
              }
            }
            /* DHH END */
            
            /* Add table */
            $this->_drawHeader($page);
            /* Add body */
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
            /* Add totals */
            $this->insertTotals($page, $invoice);
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }
        /* DHH */ $this->insertFooter(end($pdf->pages));
        $this->_afterGetPdf();
        return $pdf;
    }

    /**
     * Create new page and assign to PDF object
     *
     * @param  array $settings
     * @return Zend_Pdf_Page
     */
    public function newPage(array $settings = [])
    {
        /* Add new table head */
        $page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;
        if (!empty($settings['table_header'])) {
            $this->_drawHeader($page);
        }
        return $page;
    }
    
    /* DHH BEGIN */
    public function insertFooter($page)
    {
		    $this->_setFontRegular($page, 10);
        $page->setFillColor(new Zend_Pdf_Color_Html('#4F81BD'));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
		    $width = 842;
        $height = 50;
        $y  =   $height /2.5;
        $page->drawRectangle(1, 20, 35+ $width /1.5, $y + $height / 2);
        $page->setFillColor(new Zend_Pdf_Color_Html('#FFFFFF'));
    		$page->drawText('Chefstore.nl: Alles voor de Chef', 250, 29, 'UTF-8');

        $page->setFillColor(new Zend_Pdf_Color_Html('#1F497D'));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
		    $width = 842;
        $height = 25;
        $y = $height /2.5;
        $page->drawRectangle(1, 1, 35 + $width /1.5, $y + $height / 2);
        $page->setFillColor(new Zend_Pdf_Color_Html('#FFFFFF'));
    		$page->drawText('De Heer Horeca B.V. John M. Keynesplein 12-46 1066 EP Amsterdam Nederland service@chefstore.nl +31 (0) 85-0441003', 40, 8, 'UTF-8');
    }

    public function getInvoiceDate()
    {
		    $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoice->getIncrementId());
		    $createdDate = $invoice->getCreatedAt();
        $invoiceDate = date('d M Y', strtotime($createdDate));
        return $invoiceDate;
    }
    /* DHH END */
}
