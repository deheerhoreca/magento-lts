<?php

class Cloud_InvoiceGenerator_Helper_Parser extends Mage_Core_Helper_Abstract {

    function parseOrder($magdborder, $payment) {
        $order = array();
        $order['entity_id'] = $magdborder->getEntityId();
        $order['storeid'] = $magdborder->getStoreId();
        $order['order_id'] = $magdborder->getOrderId();
        $order['order_number'] = $magdborder->getIncrementId();
        $order['orderdate'] = $magdborder->getCreatedAt();
        $order['status'] = $magdborder->getState();
        $order['billing_address_id'] = $magdborder->getBillingAddressId();
        $order['store_currency_code'] = $magdborder->getStoreCurrencyCode();
        $order['global_currency_code'] = $magdborder->getGlobalCurrencyCode();
        $order['order_currency_code'] = $magdborder->getOrderCurrencyCode();
        $order['base_currency_code'] = $magdborder->getBaseCurrencyCode();

        $order['base_grand_total'] = $magdborder->getBaseGrandTotal();
        $order['grand_total'] = $magdborder->getGrandTotal();
        $order['base_subtotal'] = $magdborder->getBaseSubtotal();
        $order['subtotal'] = $magdborder->getSubtotal();
        $order['subtotal_incl_tax'] = $magdborder->getSubtotalInclTax();

        $order['tax_amount'] = $magdborder->getTaxAmount();
        $order['base_tax_amount'] = $magdborder->getBaseTaxAmount();
        $order['base_shipping_tax_amount'] = $magdborder->getBaseShippingTaxAmount();
        $order['shipping_tax_amount'] = $magdborder->getShippingTaxAmount();

        $order['base_discount_amount'] = $magdborder->getBaseDiscountAmount();
        $order['discount_amount'] = $magdborder->getDiscountAmount();

        $order['base_shipping_amount'] = $magdborder->getBaseShippingAmount();
        $order['shipping_amount'] = $magdborder->getShippingAmount();
        $order['base_shipping_incl_tax'] = $magdborder->getBaseShippingInclTax();
        $order['shipping_incl_tax'] = $magdborder->getShippingInclTax();

        //payment
        $order['amount_paid'] = $payment->getBaseAmountPaid();
        $order['payment_method_id'] = $payment->getMethod();
        $order['payment_method_title'] = $payment->getMethod();

        return $order;
    }

    function parsePurchaseorder($magdborder) {
        //This method must get adapted. Fields you may find in /var/www/html/magento/magento/app/code/community/MDN/Purchase/Model/Order.php
        $order = array();
        $order['total_excl'] = $magdborder->getTotalHt();
        $order['total_incl'] = $magdborder->getTotalTtc();
        $order['product_total_base'] = $magdborder->getProductTotalBase();
        $order['product_total'] = $magdborder->getProductTotal();
        $order['tax_amount'] = $magdborder->getTaxAmount();
        $order['shipping_amount_excl'] = $magdborder->getShippingAmountHt();
        $order['shipping_amount_incl'] = $magdborder->getShippingAmountTtc();
        $order['zoll_amount_excl'] = $magdborder->getZollAmountHt();
        $order['zoll_amount_incl'] = $magdborder->getZollAmountTtc();
        $order['order_currency_code'] = $magdborder->getCurrency();
        $order['base_currency_code'] = $magdborder->getEuroCurrency();
        $order['supplier'] = $magdborder->getSupplier();
        $order['products'] = $magdborder->getProducts();
        
        return $order;
    }

    function parseInvoice($magdbinvoice, $payment) {
        $invoice = array();
        $magdborder = $magdbinvoice->getOrder();
        $invoice['entity_id'] = $magdbinvoice->getEntityId();
        $invoice['number'] = $magdbinvoice->getIncrementId();
        $invoice['invoicedate'] = $magdbinvoice->getCreatedAt();
        $invoice['order_id'] = $magdbinvoice->getOrderId();
        $invoice['order_number'] = $magdborder->getIncrementId();
        $invoice['storeid'] = $magdborder->getStoreId();
        $invoice['status'] = $magdbinvoice->getState();
        $invoice['billing_address_id'] = $magdbinvoice->getBillingAddressId();
        $invoice['store_currency_code'] = $magdbinvoice->getStoreCurrencyCode();
        $invoice['global_currency_code'] = $magdbinvoice->getGlobalCurrencyCode();
        $invoice['order_currency_code'] = $magdbinvoice->getOrderCurrencyCode();
        $invoice['base_currency_code'] = $magdbinvoice->getBaseCurrencyCode();

        $invoice['base_grand_total'] = $magdbinvoice->getBaseGrandTotal();
        $invoice['grand_total'] = $magdbinvoice->getGrandTotal();
        $invoice['base_subtotal'] = $magdbinvoice->getBaseSubtotal();
        $invoice['subtotal'] = $magdbinvoice->getSubtotal();
        $invoice['subtotal_incl_tax'] = $magdbinvoice->getSubtotalInclTax();

        $invoice['tax_amount'] = $magdbinvoice->getTaxAmount();
        $invoice['base_tax_amount'] = $magdbinvoice->getBaseTaxAmount();
        $invoice['base_shipping_tax_amount'] = $magdbinvoice->getBaseShippingTaxAmount();
        $invoice['shipping_tax_amount'] = $magdbinvoice->getShippingTaxAmount();

        $invoice['base_discount_amount'] = $magdbinvoice->getBaseDiscountAmount();
        $invoice['discount_amount'] = $magdbinvoice->getDiscountAmount();

        $invoice['base_shipping_amount'] = $magdbinvoice->getBaseShippingAmount();
        $invoice['shipping_amount'] = $magdbinvoice->getShippingAmount();
        $invoice['base_shipping_incl_tax'] = $magdbinvoice->getBaseShippingInclTax();
        $invoice['shipping_incl_tax'] = $magdbinvoice->getShippingInclTax();

        //payment
        $invoice['amount_paid'] = $payment->getBaseAmountPaid();
        $invoice['payment_method_id'] = $payment->getMethod();
        $invoice['payment_method_title'] = $payment->getMethod();

        return $invoice;
    }

    function parseCreditmemo($magdbcreditmemo) {
        $creditmemo = array();
        $magdborder = $magdbcreditmemo->getOrder();
        $creditmemo['entity_id'] = $magdbcreditmemo->getEntityId();
        $creditmemo['number'] = $magdbcreditmemo->getIncrementId();
        $creditmemo['invoicedate'] = $magdbcreditmemo->getCreatedAt();
        $creditmemo['order_id'] = $magdbcreditmemo->getOrderId();
        $creditmemo['order_number'] = $magdborder->getIncrementId();
        $creditmemo['storeid'] = $magdborder->getStoreId();
        $creditmemo['status'] = $magdbcreditmemo->getState();
        $creditmemo['billing_address_id'] = $magdbcreditmemo->getBillingAddressId();
        $creditmemo['store_currency_code'] = $magdbcreditmemo->getStoreCurrencyCode();
        $creditmemo['global_currency_code'] = $magdbcreditmemo->getGlobalCurrencyCode();
        $creditmemo['order_currency_code'] = $magdbcreditmemo->getOrderCurrencyCode();
        $creditmemo['base_currency_code'] = $magdbcreditmemo->getBaseCurrencyCode();

        $creditmemo['base_grand_total'] = $magdbcreditmemo->getBaseGrandTotal();
        $creditmemo['grand_total'] = $magdbcreditmemo->getGrandTotal();
        $creditmemo['base_subtotal'] = $magdbcreditmemo->getBaseSubtotal();
        $creditmemo['subtotal'] = $magdbcreditmemo->getSubtotal();
        $creditmemo['subtotal_incl_tax'] = $magdbcreditmemo->getSubtotalInclTax();

        $creditmemo['tax_amount'] = $magdbcreditmemo->getTaxAmount();
        $creditmemo['base_tax_amount'] = $magdbcreditmemo->getBaseTaxAmount();
        $creditmemo['base_shipping_tax_amount'] = $magdbcreditmemo->getBaseShippingTaxAmount();
        $creditmemo['shipping_tax_amount'] = $magdbcreditmemo->getShippingTaxAmount();

        $creditmemo['base_discount_amount'] = $magdbcreditmemo->getBaseDiscountAmount();
        $creditmemo['discount_amount'] = $magdbcreditmemo->getDiscountAmount();

        $creditmemo['base_shipping_amount'] = $magdbcreditmemo->getBaseShippingAmount();
        $creditmemo['shipping_amount'] = $magdbcreditmemo->getShippingAmount();
        $creditmemo['base_shipping_incl_tax'] = $magdbcreditmemo->getBaseShippingInclTax();
        $creditmemo['shipping_incl_tax'] = $magdbcreditmemo->getShippingInclTax();

        return $creditmemo;
    }

    function parseAddress($magdbaddress) {
        $address = array();
        $address['entity_id'] = $magdbaddress->getEntityId();
        $address['customer_id'] = $magdbaddress->getCustomerId();
        $address['region'] = $magdbaddress->getRegion();
        $address['postcode'] = $magdbaddress->getPostcode();
        $address['firstname'] = $magdbaddress->getFirstname();
        $address['lastname'] = $magdbaddress->getLastname();
        $address['street'] = $magdbaddress->getStreet();
        $address['city'] = $magdbaddress->getCity();
        $address['email'] = $magdbaddress->getEmail();
        $address['telephone'] = $magdbaddress->getTelephone();
        $address['country_id'] = $magdbaddress->getCountryId();
        $address['company'] = $magdbaddress->getCompany();
        $address['vat_id'] = $magdbaddress->getVatId();
       
        return $address;
    }

    function parseOrderitems($magdblines) {
        $orderitems = array();
        foreach ($magdblines as $magdbline) {
            $orderline = array();
            $orderline['entity_id'] = $magdbline->getItemId();
            $orderline['base_price'] = $magdbline->getBasePrice();
            $orderline['base_tax_amount'] = $magdbline->getBaseTaxAmount();
            $orderline['tax_amount'] = $magdbline->getTaxAmount();
            $orderline['base_row_total'] = $magdbline->getBaseRowTotal();
            $orderline['discount_amount'] = $magdbline->getDiscountAmount();
            $orderline['base_discount_amount'] = $magdbline->getBaseDiscountAmount();
            $orderline['row_total'] = $magdbline->getRowTotal();
            $orderline['base_row_total_incl_tax'] = $magdbline->getBaseRowTotalInclTax();
            $orderline['row_total_incl_tax'] = $magdbline->getRowTotalInclTax();
            $orderline['price'] = $magdbline->getPrice();
            $orderline['price_incl_tax'] = $magdbline->getPriceInclTax();
            $orderline['base_price_incl_tax'] = $magdbline->getBasePriceInclTax();
            $orderline['product_id'] = $magdbline->getProductId();
            $orderline['description'] = $magdbline->getDescription();
            $orderline['sku'] = $magdbline->getSku();
            $orderline['name'] = $magdbline->getName();
            $orderline['quantity'] = $magdbline->getQtyOrdered();
            $orderline['ledger_code'] = '';
            $orderlines[] = $orderline;
        } 
        return $orderlines;
    }

    function parseEntrylines($magdblines) {
        //This method must get adapted. Fields you may find in /var/www/html/magento/magento/app/code/community/MDN/Purchase/Model/OrderProduct.php
        $orderitems = array();
        foreach ($magdblines as $magdbline) {
            $orderline = array();
            $orderline['product_name'] = $magdbline->getpop_product_name();
            $orderline['discount_level'] = $magdbline->getDiscountLevel();
            $orderline['row_totalexcl_base'] = $magdbline->getRowTotal_base();
            $orderline['row_totalincl_base'] = $magdbline->getRowTotalWithTaxes_base();
            $orderline['ordered_qty'] = $magdbline->getOrderedQty();
            $orderline['sku'] = $magdbline->getSku();
            $orderlines[] = $orderline;
        } 
        return $orderlines;
    }

    function parseInvoicelines($magdblines) {
        $invoicelines = array();
        foreach ($magdblines as $magdbline) {
            $invoiceline = array();
            $invoiceline['entity_id'] = $magdbline->getEntityId();
            $invoiceline['base_price'] = $magdbline->getBasePrice();
            $invoiceline['base_tax_amount'] = $magdbline->getBaseTaxAmount();
            $invoiceline['tax_amount'] = $magdbline->getTaxAmount();
            $invoiceline['base_row_total'] = $magdbline->getBaseRowTotal();
            $invoiceline['discount_amount'] = $magdbline->getDiscountAmount();
            $invoiceline['base_discount_amount'] = $magdbline->getBaseDiscountAmount();
            $invoiceline['row_total'] = $magdbline->getRowTotal();
            $invoiceline['base_row_total_incl_tax'] = $magdbline->getBaseRowTotalInclTax();
            $invoiceline['row_total_incl_tax'] = $magdbline->getRowTotalInclTax();
            $invoiceline['price'] = $magdbline->getPrice();
            $invoiceline['price_incl_tax'] = $magdbline->getPriceInclTax();
            $invoiceline['base_price_incl_tax'] = $magdbline->getBasePriceInclTax();
            $invoiceline['product_id'] = $magdbline->getProductId();
            $invoiceline['description'] = $magdbline->getDescription();
            $invoiceline['sku'] = $magdbline->getSku();
            $invoiceline['name'] = $magdbline->getName();
            $invoiceline['quantity'] = $magdbline->getQty();
            $invoiceline['ledger_code'] = '';
            $invoicelines[] = $invoiceline;
        } 
        return $invoicelines;
    }

    function parseCreditmemolines($magdblines) {
        $creditmemolines = array();
        foreach ($magdblines as $magdbline) {
            $creditmemoline = array();
            $creditmemoline['entity_id'] = $magdbline->getEntityId();
            $creditmemoline['base_price'] = $magdbline->getBasePrice();
            $creditmemoline['base_tax_amount'] = $magdbline->getBaseTaxAmount();
            $creditmemoline['tax_amount'] = $magdbline->getTaxAmount();
            $creditmemoline['base_row_total'] = $magdbline->getBaseRowTotal();
            $creditmemoline['discount_amount'] = $magdbline->getDiscountAmount();
            $creditmemoline['base_discount_amount'] = $magdbline->getBaseDiscountAmount();
            $creditmemoline['row_total'] = $magdbline->getRowTotal();
            $creditmemoline['base_row_total_incl_tax'] = $magdbline->getBaseRowTotalInclTax();
            $creditmemoline['row_total_incl_tax'] = $magdbline->getRowTotalInclTax();
            $creditmemoline['price'] = $magdbline->getPrice();
            $creditmemoline['price_incl_tax'] = $magdbline->getPriceInclTax();
            $creditmemoline['base_price_incl_tax'] = $magdbline->getBasePriceInclTax();
            $creditmemoline['product_id'] = $magdbline->getProductId();
            $creditmemoline['description'] = $magdbline->getDescription();
            $creditmemoline['sku'] = $magdbline->getSku();
            $creditmemoline['name'] = $magdbline->getName();
            $creditmemoline['quantity'] = $magdbline->getQty();
            $creditmemoline['ledger_code'] = '';
            $creditmemolines[] = $creditmemoline;
        } 
        return $creditmemolines;
    }
    
    function parseSupplieraddress($supplier) {
        $supplierAddress = array();
        $supplierAddress['sup_name'] = $supplier->getsup_name();
        $supplierAddress['sup_address1'] = $supplier->getsup_address1();
        $supplierAddress['sup_address2'] = $supplier->getsup_address2();
        $supplierAddress['sup_zipcode'] = $supplier->getsup_zipcode();
        $supplierAddress['sup_city'] = $supplier->getsup_city();
        $supplierAddress['sup_state'] = $supplier->getsup_state();
         if ($supplier->getsup_country() != ''){
            $supplierAddress['sup_country'] = Mage::getModel('directory/country')->loadByCode($supplier->getsup_country())->getName();
        }
        $supplierAddress['sup_tel'] = $supplier->getsup_tel();
        $supplierAddress['sup_contact'] = $supplier->getsup_contact();
        
        return $supplierAddress;
    }

}
