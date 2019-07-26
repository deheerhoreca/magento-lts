<?php

class TM_CheckoutFields_Helper_Pdf
{
    /**
     * Get lineBlock for Mage_Sales_Model_Order_Pdf_Abstract::drawLineBlocks
     * method
     *
     * @param  mixed $order
     * @return array
     */
    public function getLineBlock($order)
    {
        if ($order instanceof Mage_Sales_Model_Order_Shipment) {
            $order = $order->getOrder();
        }

        $fields = Mage::helper('checkoutfields')->getFields();
        $lines  = array();
        $i      = 0;
        foreach ($fields as $field => $config) {
            $value = (string)$order->getData($field);

            if (!strlen($value)) {
                continue;
            }

            $lines[$i][] = array(
                'text' => $config['label'],
                'feed' => 35
            );
            $lines[$i][] = array(
                'text' => $value,
                'feed' => 200
            );

            $i++;
        }

        if ($lines) {
            return array(
                'lines'  => $lines,
                'height' => 14
            );
        }

        return false;
    }

    /**
     * Draw checkout fields into page
     *
     * @param  Zend_Pdf_Page                        $page
     * @param  mixed                                $order
     * @param  Mage_Sales_Model_Order_Pdf_Abstract  $pdfModel
     * @return void
     */
    public function drawFields(Zend_Pdf_Page $page, $order, $pdfModel)
    {
        $lineBlock = $this->getLineBlock($order);
        if (!$lineBlock) {
            return;
        }

        // $pdfModel->_setFontRegular($page, 10);
        $page->setFillColor(new Zend_Pdf_Color_RGB(0, 0, 0));
        $pdfModel->y -= 5;
        $pdfModel->drawLineBlocks($page, array($lineBlock));
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
    }
}
