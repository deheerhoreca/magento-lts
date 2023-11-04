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
 * Class Ophirah_Qquoteadv_ViewController
 */
class Ophirah_Qquoteadv_ViewController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var null
     */
    private $_quoteId = null;

    const XML_PATH_QQUOTEADV_REQUEST_CANCEL_EMAIL_TEMPLATE = 'qquoteadv_quote_emails/templates/proposal_cancel';
    const XML_PATH_QQUOTEADV_REQUEST_REJECT_EMAIL_TEMPLATE = 'qquoteadv_quote_emails/templates/proposal_reject';
    const FLAG_NO_DISPATCH = 'no-dispatch';

    /**
     * Get customer session data
     */
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Get core session data
     */
    public function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * Filter the request parameter
     * filter case occurs when the product is moved to quote from cart and vice-versa
     * @return array
     */
    public function getFilteredParams()
    {
        $params = $this->getRequest()->getParams();

        // if cartid is present in request parameter
        // occurs when product is moved from cart to quote
        if ($this->getRequest()->getParam('cartid')) {
            if ($this->getRequest()->getParam('cartid') == '0') {
                return $params;
            } else {
                return $params['cart'][$this->getRequest()->getParam('cartid')];
            }
        }
        // if quoteid is present in request parameter
        // occurs when product is moved from quote to cart
        elseif ($this->getRequest()->getParam('quoteid')) {
            return $params['quote'][$this->getRequest()->getParam('quoteid')];
        }
        // if both are not present in request paramter
        // occurs when product is added to quote from product detail page
        else {
            return $params;
        }
    }

    /**
     * Insert quote data
     * Useful when all products from cart page are added to quote
     */
    public function addAction()
    {
        $params = $this->getFilteredParams();
        Mage::dispatchEvent('ophirah_qquoteadv_viewadd_before', [$params]);

        if (array_key_exists('cart', $params)) {
            foreach ($params['cart'] as $key => $value) {
                $this->addFilterAction($value);
            }
        } else {
            $this->addFilterAction($params);
        }
        Mage::dispatchEvent('ophirah_qquoteadv_viewadd_after', [$params]);
    }

    /**
     * Insert filter quote data
     * @param $params
     */
    public function addFilterAction($params)
    {
        Mage::dispatchEvent('ophirah_qquoteadv_viewaddFilter_before', [$params]);
        // set the qty to 1 if it is empty
        if ($params['qty'] == '' || !is_numeric($params['qty'])) {
            $params['qty'] = 1;
        }

        /**
         * if addAction is called from cart or quote page
         * from cart/quote page, the parameter is serialized string and is passed as base64 encoded form
         * hence, we have to decode it
         */
        if (array_key_exists('attributeEncode', $params)) {
            // $superAttribute = base64_decode($params['attributeEncode']);

            /**
             * unsetting 'uenc' key which is present in array when it is moved from cart to quote
             * uenc contains url of the product in base64_decode form
             */
            $testParams = unserialize(base64_decode($params['attributeEncode']));
            unset($testParams['uenc']);
            $superAttribute = serialize($testParams);
        } /**
         * if addAction is called from product detail page
         * from product detail page, parameter is passed as an array
         * hence, we have to serialize the array and make it string
         */ else {
            $superAttribute = serialize($params);
        }

        // if the product is Grouped Product
        if ($this->getRequest()->getParam('super_group')) {
            $superGroup = $this->getRequest()->getParam('super_group');

            if (array_sum($superGroup) > 0) {
                // adding each super group product separately as simple product
                foreach ($superGroup as $key => $value) {
                    // don't add product if it have quantity value 0
                    if ($value != 0 && is_numeric($value)) {
                        $groupParams['product'] = $key;
                        $groupParams['qty'] = (int)$value;
                        $this->addDataAction($groupParams, $superAttribute);
                    }
                }
            } else {
                $this->getCoreSession()->addNotice($this->__('Please specify product quantity.'));
                $this->_redirectReferer(Mage::getUrl('*/*'));
            }
        } else {
            $this->addDataAction($params, $superAttribute);
        }

        Mage::dispatchEvent('ophirah_qquoteadv_viewaddFilter_before', [$params]);
    }

    /**
     * Insert quote data (main data add function)
     * @param array $params post parameter for product
     * @param string $superAttribute
     */
    public function addDataAction($params, $superAttribute)
    {
        Mage::dispatchEvent('ophirah_qquoteadv_viewaddData_before', [$params, $superAttribute]);

        if ($this->getCustomerSession()->isLoggedIn()) {
            $qcustomer = ['created_at' => now(), 'updated_at' => now(), 'customer_id' => $this->getCustomerSession()->getId()];
        } else {
            $qcustomer = ['created_at' => now(), 'updated_at' => now()];
        }

        $modelCustomer = Mage::getModel('qquoteadv/qqadvcustomer');
        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
        try {
            // if quote_id is not set then insert into qquote_customer table and set quote_id
            if ($this->getCustomerSession()->getQuoteadvId() == null) {
                // save data to qquote_customer table and getting inserted row id
                $qId = $modelCustomer->addQuote($qcustomer)->getQuoteId();
                // setting inserted row id of qquote_customer table into session
                $this->getCustomerSession()->setQuoteadvId($qId);
            }

            /**
             * check if the customer has already added the particular product
             * if the product is already added by the customer then add only the quantity for that row
             * otherwise add new row for product
             */
            $dataInProduct = $modelProduct->getCollection()
                ->addFieldToFilter('quote_id', $this->getCustomerSession()->getQuoteadvId())
                ->addFieldToFilter('product_id', $params['product'])
                ->addFieldToFilter('attribute', $superAttribute);

            if ($dataInProduct->getData() != []) {
                foreach ($dataInProduct as $item) {
                    // adding qty to product if the customer has previously added in the current session
                    $qtySum = ['qty' => $params['qty'] + $item->getQty()];
                    $modelProduct->updateProduct($item->getId(), $qtySum);
                }
            } else {
                // save data with the quote_id to qquote_product table
                $qproduct = ['quote_id' => $this->getCustomerSession()->getQuoteadvId(), 'product_id' => $params['product'], 'qty' => $params['qty'], 'attribute' => $superAttribute];
                $modelProduct->addProduct($qproduct);
            }

            /**
             * deleting the item from cart if cartid is set in the url
             * i.e. if the addAction is called from 'Move to quote' button of cart page
             * in this case, we have to add the item to quote and delete from cart
             */
            if (array_key_exists('cartid', $params)) {
                Mage::getModel('checkout/cart')->removeItem($params['cartid'])->save();
                $this->getCoreSession()->addSuccess($this->__('Item was moved to quote successfully.'));
                $this->_redirectReferer(Mage::getUrl('*/*'));
            } else {
                $this->_redirect('*/*/');
            }
        } catch (Exception $e) {
            if ($this->getCoreSession()->getUseNotice(true)) {
                $this->getCoreSession()->addNotice($e->getMessage());
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->getCoreSession()->addError($message);
                }
            }
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
        }

        Mage::dispatchEvent('ophirah_qquoteadv_viewaddData_after', [$params, $superAttribute]);
    }

    /**
     * Move item to cart
     *
     */
    public function moveAction()
    {
        $params = $this->getFilteredParams();
        Mage::dispatchEvent('ophirah_qquoteadv_viewmove_before', [$params]);
        $params['attributeEncode'] = unserialize(base64_decode($params['attributeEncode']));

        // updating attribute product quantity with the product quantity
        $params['attributeEncode']['qty'] = $params['qty'];

        $quoteId = $params['quoteid'];

        $product = Mage::getModel('catalog/product')->load($params['product']);

        // add item to cart
        Mage::getModel('checkout/cart')->addProduct($product, $params['attributeEncode'])->save();

        // delete item from quote
        Mage::getModel('qquoteadv/qqadvproduct')->deleteQuote($quoteId);

        $this->getCoreSession()->addSuccess($this->__('Item was moved to cart successfully.'));
        $this->_redirect('*/*/');
        Mage::dispatchEvent('ophirah_qquoteadv_viewmove_after', [$params]);
    }

    /**
     * Update product quantity from quote
     *
     */
    public function updateAction()
    {
        $quoteData = $this->getRequest()->getParam('quote');
        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
        Mage::dispatchEvent('ophirah_qquoteadv_viewupdate_before', [$quoteData]);

        try {
            if (is_array($quoteData)) {
                // update quote list
                foreach ($quoteData as $key => $value) {
                    // delete product if qty is entered 0
                    if ($value['qty'] == 0) {
                        $modelProduct->deleteQuote($key);
                    } else {
                        $modelProduct->updateProduct($key, $value);
                    }
                }
            }
            $this->getCoreSession()->addSuccess($this->__('Quote list was updated successfully.'));
        } catch (Exception $e) {
            $this->getCoreSession()->addError($this->__("Can't update quote list"));
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
        }
        $this->_redirectReferer(Mage::getUrl('*/*'));
        Mage::dispatchEvent('ophirah_qquoteadv_viewupdate_after', [$quoteData]);
    }

    /**
     * Delete product from quote
     *
     */
    public function deleteAction()
    {
        // get the product id to delete
        $id = $this->getRequest()->getParam('id');
        Mage::dispatchEvent('ophirah_qquoteadv_viewdelete_before', [$id]);

        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');

        try {
            // delete the row from quote_product table
            //$modelProduct->setId($id)->delete();
            $modelProduct->deleteQuote($id);
            $this->getCoreSession()->addSuccess($this->__('Item was deleted successfully.'));
        } catch (Exception $e) {
            $this->getCoreSession()->addError($this->__("Can't remove item"));
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
        }
        $this->_redirectReferer(Mage::getUrl('*/*'));
        Mage::dispatchEvent('ophirah_qquoteadv_viewdelete_after', [$id]);
    }

    /**
     * Show address form after user submits quote
     */
    public function addressAction()
    {
        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        Mage::dispatchEvent('ophirah_qquoteadv_viewaddress_before', [$quoteId]);

        if ($quoteId) {
            $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
            $dataInProduct = $modelProduct->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);

            if ($dataInProduct->getData() != []) {
                $this->loadLayout();
                $this->renderLayout();
            } else {
                $this->getCoreSession()->addError($this->__('No item in quote.'));
                $this->_redirectReferer(Mage::getUrl('*/*'));
            }
        } else {
            $this->getCoreSession()->addError($this->__('No item in quote.'));
            $this->_redirectReferer(Mage::getUrl('*/*'));
        }

        Mage::dispatchEvent('ophirah_qquoteadv_viewaddress_before', [$quoteId]);
    }

    /**
     * Show success message
     */
    public function successAction()
    {
        Mage::dispatchEvent('ophirah_qquoteadv_viewsuccess_before', []);

        // unset customer address
        $this->getCustomerSession()->unsQuoteId();

        $this->loadLayout();
        $this->renderLayout();

        Mage::dispatchEvent('ophirah_qquoteadv_viewsuccess_before', []);
    }

    /**
     * Quoteadv id getter
     *
     * @return null
     */
    public function getQuoteadvId()
    {
        return $this->_quoteId;
    }

    /**
     * Quoteadv id setter
     *
     * @param $id
     */
    public function setQuoteId($id)
    {
        $this->_quoteId = $id;
    }

    /**
     * Initialize requested quote object
     *
     * @return bool|Ophirah_Qquoteadv_Model_Qqadvproduct collection
     */
    protected function _initQuote()
    {
        if (!$this->isCustomerLoggedIn()) {
            $this->_redirect('customer/account/login/');
        }

        Mage::dispatchEvent('quote_controller_init_before', ['controller_action' => $this]);
        $quoteId = (int)$this->getRequest()->getParam('id');

        if (!$quoteId) {
            return false;
        }

        $quote = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
            //->setStoreId(Mage::app()->getStore()->getId())
            ->addFieldToFilter('quote_id', $quoteId);

        Mage::register('quote', $quote);

        try {
            Mage::dispatchEvent('quote_controller_init', ['quote' => $quote]);
            Mage::dispatchEvent('quote_controller_init_after', ['quote' => $quote, 'controller_action' => $this]);
        } catch (Mage_Core_Exception $e) {
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            return false;
        }

        return $quote;
    }

    /**
     * Quote proposal by quoteId
     *
     */
    public function viewAction()
    {
        //is free user?
        if (!Mage::helper('qquoteadv/license')->validLicense('my-quotes', null, true)) {
            //no error message
            $this->_redirect('*/index/'); //redirect to quote page
            return;
        }

        $quote = $this->_initQuote();
        if ($quote) {

            $quoteId = (int)$this->getRequest()->getParam('id');
            Mage::dispatchEvent('ophirah_qquoteadv_view_before', [$quoteId]);

            $quoteData = $this->checkUserQuote($quoteId, $this->getCustomerId());
            if ($quoteData) {
                //# show quote in case proposal was sent
                foreach ($quoteData as $key => $item) {
                    $currency = $item->getCurrency();
                    Mage::helper('qquoteadv')->setCurrentCurrency($currency);

                    if (Ophirah_Qquoteadv_Model_Status::STATUS_BEGIN == $item->getStatus()) {
                        $this->getCoreSession()->addNotice(Mage::helper('adminhtml')->__('Access denied').'!');
                        $this->_forward('noRoute');
                        //return;
                    }
                }

                if (isset($item))
                    if (Ophirah_Qquoteadv_Model_Status::STATUS_REQUEST == $item->getStatus()) {
                        $msg = $this->__("Quote Request in Process, wait for price proposal Shop owner");
                        $this->getCoreSession()->addNotice($msg);
                    }

                $this->loadLayout();
                $this->renderLayout();
                Mage::dispatchEvent('quote_proposal_controller_view', ['quote' => $quote]);
            } else {
                $this->getCoreSession()->addNotice(Mage::helper('adminhtml')->__('Access denied').'!');
                $this->_redirect('qquoteadv/view/history/');
            }
        } else {
            $this->_redirect('qquoteadv/view/history/');
        }

        if(isset($quoteId)){
            Mage::dispatchEvent('ophirah_qquoteadv_view_after', [$quoteId]);
        }
        return null;
    }

    /**
     * Check if a given user is the owner of a given quote
     *
     * @param $quoteId
     * @param $userId
     * @return bool
     */
    private function checkUserQuote($quoteId, $userId)
    {
        $quote = Mage::getModel('qquoteadv/qqadvcustomer')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('customer_id', $userId);

        return (count($quote) > 0) ? $quote : false;
    }

    /**
     * Quote proposal was rejected by client
     *
     * @return bool|null
     */
    public function rejectAction()
    {
        $quote = $this->_initQuote();
        if ($quote) {
            $quoteId = (int)$this->getRequest()->getParam('id');
            Mage::dispatchEvent('ophirah_qquoteadv_reject_before', [$quoteId]);

            if (!$quoteId) {
                return false;
            }

            $quoteData = $this->checkUserQuote($quoteId, $this->getCustomerId());
            if ($quoteData) {
                foreach ($quoteData as $key => $item) {

                    $params = [
                        'updated_at' => now(),
                        'status' => Ophirah_Qquoteadv_Model_Status::STATUS_DENIED
                    ];

                    Mage::getModel('qquoteadv/qqadvcustomer')->updateQuote($item->getId(), $params);
                }
            }


            //Mage::dispatchEvent('quote_proposal_controller_reject', array('quote'=>$quote));

            $resource = Mage::getSingleton('core/resource');
            $read = $resource->getConnection('core_read');
            $userTable = $resource->getTableName('quoteadv_customer');
            $select = $read->select()
                ->from($userTable)
                ->where('customer_id = ?', $this->getCustomerId())
                ->where('quote_id = ?', $quoteId);
            $user_info = $read->fetchRow($select);

            $realId = $quoteId;

            if (is_array($user_info) && !empty($user_info)) {
                $params['email'] = $user_info['email'];
                $params['firstname'] = $user_info['firstname'];
                $params['lastname'] = $user_info['lastname'];
                $params['quoteId'] = $quoteId;
                $params['customerId'] = $this->getCustomerId();

                if (!Mage::helper('qquoteadv/licensechecks')->showFreeUserOptions()) {
                    $this->sendEmailReject($params);
                }

                $realId = $user_info['increment_id'];
            }

            $this->getCoreSession()->addNotice($this->__('Quotation #%s was rejected', $realId));
            $this->_redirect('qquoteadv/view/view/id/' . $quoteId);
        } else {
            $this->_forward('noRoute');
        }

        if(isset($quoteId)){
            Mage::dispatchEvent('ophirah_qquoteadv_reject_after', [$quoteId]);
        }
        return null;
    }

    /**
     * Request proposal was accepted (confirmed) by client
     *
     */
    public function confirmAction()
    {
        $notice = '';
        $_helper = Mage::helper('cataloginventory');

        $quote = $this->_initQuote();
        if ($quote) {
            $quoteId = (int)$this->getRequest()->getParam('id');
            $params = $this->getRequest()->getParams();
            Mage::dispatchEvent('ophirah_qquoteadv_viewconfirm_before', [$quoteId, $params]);

            // Load Quotation Data
            $_quote = Mage::getSingleton('qquoteadv/qqadvcustomer')->load($quoteId);
            $_quote->collectTotals();

            // Check for minimum Cart Amount
            $address = $_quote->getShippingAddress();
            $address->setAddressType('shipping');
            $minAmount = $address->validateMinimumAmount();

            if (!$minAmount && Mage::getStoreConfig('qquoteadv_quote_configuration/proposal/quoteconfirmation') != "0") {
                $notice = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                    Mage::getStoreConfig('sales/minimum_order/error_message') :
                    Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');
                $this->getCoreSession()->addNotice($notice);
                Mage::helper('qquoteadv')->setActiveConfirmMode(false);
                $this->_redirect('qquoteadv/view/view/id/' . $quoteId);
                return $this;
            }

            if (!isset($params['requestQtyLine'])) {
                $params['requestQtyLine'] = $_quote->getAllRequestItemsForCart();
                $params['remove_item_id'] = '';

                if ($params['requestQtyLine'] === false) {
                    $message = "Couldn't auto check out because one or more products are bundle products.";
                    Mage::log('Message: ' .$message, null, 'c2q.log', true);

                    $this->getCoreSession()->addNotice($this->__("Couldn't auto check out because one or more products are bundle products"));

                    $this->_redirect('qquoteadv/view/view/id/' . $quoteId);
                    return $this;
                }
            }

            $quoteData = $this->checkUserQuote($quoteId, $this->getCustomerId());
            if ($quoteData) {
                if (count($params['requestQtyLine']) > 0) {

                    //# Delete items from shopping cart before moving quote items to it
                    Mage::helper('qquoteadv')->setActiveConfirmMode(false); // disable first to clear the cart
                    $this->_clearShoppingCart();

                    // Check for Checkout Url
                    $useAltCheckout = false;
                    $altCheckoutUrl = false;
                    if (Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/checkout_alternative_url')) {
                        $altCheckoutUrl = Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/checkout_alternative_url');
                        $confAltCheckout = Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/checkout_alternative', $_quote->getData('store_id'));
                        $useAltCheckout = $confAltCheckout > 0 && $_quote->getData('alt_checkout') > 0;
                    }

                    if ($useAltCheckout === false){

                        // Add Salesrule
                        if ($_quote->getData('salesrule')) {
                            $couponCode = $_quote->getCouponCodeById($_quote->getData('salesrule'));
                        } else {
                            $couponCode = null;
                        }

                        //# Set QUOTE comfirmation mode to avoid manipulation with qty/price
                        Mage::helper('qquoteadv')->setActiveConfirmMode(true);
                        Mage::getSingleton('core/session')->proposal_quote_id = $quoteId;
                        //# Allow Quoteshiprate shipping method
                        Mage::getSingleton('core/session')->proposal_showquoteship = true;

                        Mage::helper('qquoteadv')->setCurrentCurrency($_quote->getCurrency());

                        // get Cart
                        $cart = Mage::getModel('checkout/cart');

                        foreach ($params['requestQtyLine'] as $keyProductReq => $requestId) {
                            $update = [];
                            $customPrice = 0;
                            $productId = null;

                            $qqadvproduct = Mage::getModel('qquoteadv/qqadvproduct')->load($keyProductReq);
                            $update['attributeEncode'] = unserialize($qqadvproduct->getAttribute());

                            $result = Mage::getModel('qquoteadv/requestitem')->getCollection()->setQuote($_quote)
                                ->addFieldToFilter('quoteadv_product_id', $keyProductReq)
                                ->addFieldToFilter('request_id', $requestId)
                                ->getData();

                            $item = $result[0];
                            if ($item) {
                                $productId = $item['product_id'];
                                $product = Mage::getModel('catalog/product')->load($productId);

                                $update['attributeEncode']['qty'] = $item['request_qty'];

                                //# GET owner price
                                $customPrice = $item['owner_cur_price'];
                                $allowed2Ordermode = $product->getData('allowed_to_ordermode');

                                try {
                                    //# Trying to add item into cart
                                    if ($product->isSalable() or ($allowed2Ordermode == 0 && Mage::helper('qquoteadv')->isActiveConfirmMode(true))) {

                                        $maxSaleQty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getMaxSaleQty() * 1;
                                        if ($maxSaleQty > 0 && ($item['request_qty'] > $maxSaleQty)) {

                                            $notice = $_helper->__('The maximum quantity allowed for purchase is %s.', $maxSaleQty);
                                            $notice .= '<br />' . $_helper->__('Some of the products cannot be ordered in requested quantity.');

                                            continue;
                                        }

                                        if (Mage::helper('qquoteadv')->checkQuantities($product, $item['request_qty'])->getHasError() || Mage::helper('qquoteadv')->isInStock($product, $item['request_qty'])->getHasError()) {
                                            $notice = $_helper->__('Item %s is out of stock and cannot be ordered.', $product->getName());
                                            $this->getCoreSession()->addNotice($notice);
                                            return $this->_redirectReferer();
                                        }
                                        //# step1: register owner price for observer
                                        if (Mage::registry('customPrice')) {
                                            Mage::unregister('customPrice');
                                        }

                                        $priceContainsTax = Mage::helper('tax')->priceIncludesTax($_quote->getStore());
                                        if($priceContainsTax == "1"){
                                            //fallback for situations where getWebsite doesn't return a object
                                            if(is_object(Mage::app()->getWebsite(true))){
                                                $store = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStore();
                                            } else {
                                                $store = Mage::app()->getStore('default');
                                                $message = 'Mage::app()->getWebsite(true) is not a object, fallback applied';
                                                Mage::log('Message: ' .$message, null, 'c2q.log');
                                            }

                                            $taxCalculation = Mage::getModel('tax/calculation');
                                            $customer = $_quote->getCustomer();
                                            if ($customer) {
                                                $taxCalculation->setCustomer($customer);
                                            }
                                            $request = $taxCalculation->getRateOriginRequest($store);
                                            $taxClassId = $product->getTaxClassId();
                                            $percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));

                                            $quoteStore = Mage::getModel('core/store')->load($_quote->getStoreId());
                                            $taxCalculation = Mage::getModel('tax/calculation');
                                            $customer = $_quote->getCustomer();
                                            if ($customer) {
                                                $taxCalculation->setCustomer($customer);
                                            }
                                            $request = $taxCalculation->getRateRequest(null, null, null, $quoteStore);
                                            $taxClassId = $product->getTaxClassId();
                                            $quotePercent = $taxCalculation->getRate($request->setProductClassId($taxClassId));

                                            if ($percent != $quotePercent) {
                                                $customPrice = ($customPrice / (100 + $quotePercent)) * (100 + $percent);
                                            }
                                        }

                                        Mage::register('customPrice', $customPrice);

                                        //# step2: - add item to shopping cart
                                        //         - observer catch register owner price and set it for item adding for shopping cart

                                        // get Cart
                                        //$cart = Mage::getModel('checkout/cart');

                                        //add product to cart
                                        $update['attributeEncode'] = Mage::helper('qquoteadv')
                                            ->prepareFileOptions($update['attributeEncode']);
                                        $cart->addProduct($product, $update['attributeEncode']);

                                        // Apply Coupon code to Cart
                                        if ($couponCode != null && !isset($couponCodeApplied)) {
                                            $cart->getQuote()->setCouponCode($couponCode);
                                            $couponCodeApplied = true;
                                        }

                                        //Setting Address Total Amounts in Cart Shipping address
                                        foreach ($cart->getQuote()->getAllAddresses() as $address) {
                                            // These Totals needs to be set to
                                            // check the minimal Checkout amount
                                            // See: Mage_Sales_Model_Quote::validateMinimumAmount()
                                            $updateAmounts = ['subtotal', 'discount'];
                                            if ($address->getAddressType() == 'shipping') {
                                                foreach ($updateAmounts as $update) {
                                                    $address->setTotalAmount($update, $_quote->getAddress()->getData($update));
                                                    $address->setBaseTotalAmount($update, $_quote->getAddress()->getData('base_'.$update));
                                                }
                                            }
                                        }

                                        Mage::dispatchEvent('checkout_cart_add_product_complete',
                                            ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
                                        );

                                        if (isset($customPrice)) {
                                            Mage::unregister('customPrice');
                                        }

                                    } else {
                                        //check for "AW_Catalogpermissions"
                                        $extra = '';
                                        if(Mage::helper('core')->isModuleEnabled("AW_Catalogpermissions")){
                                            $extra = ' (Note: AW_Catalogpermissions is enabled)';
                                        }
                                        $message = 'Product: '.$product->getName().' could not be added to the cart'.$extra;

                                        //add log
                                        Mage::log('Message: ' .$message, null, 'c2q.log', true);

                                        //add notice
                                        $pre = '';
                                        if($notice != ''){
                                            $pre = '<br>';
                                        }
                                        $notice .= $pre.$_helper->__('Product: "%s" could not be added to the cart', $product->getName());
                                    }
                                } catch (Mage_Core_Exception $e) {
                                    Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                                    $this->getCoreSession()->addError($this->__('Product: "%s" could not be added to the cart', $product->getName()));
                                }
                            }
                        }
                        $cart->getQuote()->setProposalQuoteId($quoteId);

                        $cart->save();
                        //Set Cart2Quote reference ID
                        $mageQuoteId = $cart->getQuote()->getData('entity_id');
                        Mage::helper('qquoteadv')->setReferenceIdInCoreSession($mageQuoteId, $quoteId);

                        Mage::getSingleton('core/session')->setCartWasUpdated(true);

                        // Set Coupon Code message
                        if ($couponCode != null) {
                            if ($couponCode == $cart->getQuote()->getCouponCode()) {
                                $this->getCoreSession()->addSuccess(Mage::helper('checkout')->__('Coupon code "%s" was applied.', $couponCode));
                            } else {
                                $this->getCoreSession()->addError(Mage::helper('checkout')->__('Cannot apply the coupon code.').' '.$couponCode);
                            }
                        }

                    }

                    //# Set Quote status: STATUS_CONFIRMED
                    $data = [
                        'updated_at' => now(),
                        'status' => Mage::getModel('qquoteadv/status')->getStatusConfirmed()
                    ];


                    //# Disallow Quoteshiprate shipping method
                    Mage::getSingleton('core/session')->proposal_showquoteship = false;

                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_beforesave_final', ['quote' => Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId)]);
                    Mage::getModel('qquoteadv/qqadvcustomer')->updateQuote($quoteId, $data)->save();
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final', ['quote' => Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId)]);

                    Mage::helper('qquoteadv/logging')->sentAnonymousData('confirm', 'f', $quoteId);

                    if ($useAltCheckout === false && empty($notice)) {
                        Mage::getModel('qquoteadv/qqadvcustomer')->sendQuoteAccepted($quoteId);
                        $this->getCoreSession()->addSuccess($this->__('All items were moved to cart successfully.'));
                    } elseif ($useAltCheckout === false) {
                        $this->getCoreSession()->addNotice($notice);
                    }

                    if ($useAltCheckout === true && empty($notice)) {
                        Mage::getModel('qquoteadv/qqadvcustomer')->sendQuoteAccepted($quoteId);
                    } elseif ($useAltCheckout === true) {
                        $this->getCoreSession()->addNotice($notice);
                    }
                }

                // Redirect to checkout
                $url = Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/checkout_url');
                if (isset($altCheckoutUrl) && isset($useAltCheckout) && $useAltCheckout === true) {
                    $this->outqqconfirmmodeAction(false);
                    $this->_redirect($altCheckoutUrl);
                } elseif ($url) {
                    $this->_redirect($url);
                } else {
                    $this->_redirect('checkout/onepage/');
                }

            } else {
                $this->getCoreSession()->addNotice(Mage::helper('adminhtml')->__('Access denied').'!');
                $this->_redirect('customer/account/');
                return null;
            }
        } else {
            $this->_forward('noRoute');
        }

        if(isset($quoteId)) {
            Mage::dispatchEvent('ophirah_qquoteadv_viewconfirm_after', [$quoteId]);
        }
        return null;
    }

    /**
     * 1. Set to Quote proposal status 'CANCELED'
     * 2. Create new quote  with clone current items from Quote proposal
     *
     */
    public function editAction()
    {
        Mage::dispatchEvent('ophirah_qquoteadv_viewedit_before', []);
        $this->_checkConfirmMode();

        $oldQuotes = $this->_initQuote();
        if ($oldQuotes) {
            $oldQuoteId = $this->getRequest()->getParam('id');

            $oldQuotes = $this->checkUserQuote($oldQuoteId, $this->getCustomerId());
            if ($oldQuotes) {
                $oldQuoteData = Mage::getModel('qquoteadv/qqadvcustomer')->load($oldQuoteId);
                if($this->_checkQuoteStatus($oldQuoteData, Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL)){
                    $this->_redirectReferer(Mage::getUrl('*/*'));
                }
                $newQuoteId = $this->_createNewQuote();
                $this->_copyProducts($newQuoteId, $oldQuoteId);
                $this->getCustomerSession()->setOldQuoteadvId($oldQuoteId);
                $this->getCustomerSession()->setQuoteadvId($newQuoteId);
                $this->_redirect('qquoteadv/index/');

            } else {
                $this->getCoreSession()->addNotice(Mage::helper('adminhtml')->__('Access denied').'!');
                $this->_redirect('customer/account/');
            }
        } else {
            $this->_forward('noRoute');
        }

        Mage::dispatchEvent('ophirah_qquoteadv_viewedit_after', []);
    }

    /**
     * Update Totals in Block
     * When tier price is selected
     *
     */
    public function updateTotalsAction()
    {
        $request = $this->getRequest();
        Mage::dispatchEvent('ophirah_qquoteadv_viewupdateTotals_before', [$request]);

        if (is_array($updateInfo = $request->getPost('update_item'))) {
            try {
                $itemId = (int)$updateInfo['itemId'];
                $itemQty = (int)$updateInfo['itemQty'];

                /* @var Ophirah_Qquoteadv_Model_Qqadvproduct */
                Mage::getModel('qquoteadv/qqadvproduct')->updateProductQty($itemId, $itemQty);

            } catch (Exception $e) {
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            }
        }

        $this->_redirectReferer('*/*');
        Mage::dispatchEvent('ophirah_qquoteadv_viewupdateTotals_after', [$request]);
    }

    /**
     * Check if customer is logged in
     *
     * @return mixed
     */
    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * Get customer id from session
     *
     * @return mixed
     */
    public function getCustomerId()
    {
        return Mage::getSingleton('customer/session')->getCustomerId();
    }

    /**
     * Function that removes all quote items from the shopping cart
     */
    protected function _clearShoppingCart()
    {
        //Clear shopping cart
        foreach (Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item) {
            Mage::getSingleton('checkout/cart')->removeItem($item->getId())->save();
        }

    }

    /**
     * Function that logs a session out from quote confirmation mode
     *
     * @param bool|true $notify
     */
    public function outqqconfirmmodeAction($notify = true)
    {
        Mage::dispatchEvent('ophirah_qquoteadv_viewoutqqconfirmmode_before', []);

        Mage::helper('qquoteadv')->setActiveConfirmMode(false);
        $this->_clearShoppingCart();
        Mage::getModel('checkout/cart')->getQuote()->setProposalQuoteId(0)->save();

        if ($notify === true) {
            $message = $this->__("You log out successfully from Quote confirmation mode.");
            Mage::getSingleton('core/session')->addNotice($message);
        }

        $this->_redirect('checkout/cart');
        Mage::dispatchEvent('ophirah_qquoteadv_viewoutqqconfirmmode_after', []);
    }

    /**
     * Send email to administrator informing about the quote reject
     * @param array $params customer address
     */
    public function sendEmailReject($params)
    {
        //Create an array of variables to assign to template
        $quoteId = $params['quoteId'];
        $customerId = $params['customerId'];

        /* @var Ophirah_Qquoteadv_Model_Qqadvcustomer $_quoteadv */
        $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);

        //Vars into email templates
        $vars = [
            'quote' => $_quoteadv,
            'customer' => Mage::getModel('customer/customer')->load($customerId),
            'quoteId' => $quoteId,
            'store' => Mage::app()->getStore($_quoteadv->getStoreId())
        ];

        /*
         * Loads the html file named 'qquote_request.html' from
         * app/locale/en_US/template/email/
         */

        $template = Mage::helper('qquoteadv/email')->getEmailTemplateModel($_quoteadv->getStoreId());
        $quoteadv_param = Mage::getStoreConfig('qquoteadv_quote_emails/templates/proposal_reject', $_quoteadv->getStoreId());
        $disabledEmail = Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;
        if ($quoteadv_param != $disabledEmail){
            if ($quoteadv_param) {
                $templateId = $quoteadv_param;
            } else {
                $templateId = self::XML_PATH_QQUOTEADV_REQUEST_REJECT_EMAIL_TEMPLATE;
            }

            if (is_numeric($templateId)) {
                $template->load($templateId);
            } else {
                $template->loadDefault($templateId);
            }

            $sender = $vars['quote']->getEmailSenderInfo();
            $template->setSenderName($sender['name']);
            $template->setSenderEmail($sender['email']);

            $bcc = Mage::getStoreConfig('qquoteadv_quote_emails/sales_representatives/bcc', $_quoteadv->getStoreId());
            if ($bcc) {
                $bccData = explode(";", $bcc);
                $template->addBcc($bccData);
            }

            if ((bool)Mage::getStoreConfig('qquoteadv_quote_emails/sales_representatives/send_linked_sale_bcc', $_quoteadv->getStoreId())
                && Mage::helper('qquoteadv/licensechecks')->isAllowedSalesBcc()) {
                $template->addBcc(Mage::getModel('admin/user')->load($_quoteadv->getUserId())->getEmail());
            }

            /**
             * Opens the qquote_request.html, throws in the variable array
             * and returns the 'parsed' content that you can use as body of email
             */
            //$template->getProcessedTemplate($vars);

            /*
             * getProcessedTemplate is called inside send()
             */
            $template->setData('c2qParams', $params);
            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_before', ['template' => $template]);
            $res = $template->send($params['email'], $params['firstname'] . " " . $params['lastname'], $vars);
            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_after', ['template' => $template, 'result' => $res]);

            if (empty($res)) {
                $message = $this->__("Qquote reject email was't sent to admin for quote #%s", $quoteId);
                Mage::log('Message: ' .$message, null, 'c2q.log', true);
            }

        }

    }

    /**
     * Action to remove an item from a quote
     */
    public function itemDeleteAction()
    {

        $quoteId = $this->getRequest()->getParam('id');
        $id = $this->getRequest()->getParam('remove_item_id');

        Mage::dispatchEvent('ophirah_qquoteadv_viewitemDelete_before', [$quoteId, $id]);

        // get the unique item row id to delete
        if ($id && $quoteId) {
            $quoteData = $this->checkUserQuote($quoteId, $this->getCustomerId());
            if ($quoteData) {
                $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');

                try {
                    // delete the row from quote_product table
                    $modelProduct->deleteQuote($id);
                    $this->getCoreSession()->addSuccess($this->__('Item was deleted successfully.'));
                    Mage::dispatchEvent('ophirah_qquoteadv_viewitemDelete_after', [$quoteId, $id]);
                } catch (Exception $e) {
                    $this->getCoreSession()->addError($this->__("Can't remove item"));
                    Mage::dispatchEvent('ophirah_qquoteadv_viewitemDelete_after_error', [$quoteId, $id]);
                    Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                }
                $this->editAction();
            } else {
                $this->_redirectReferer('*/*');
            }
        } else {
            $this->_redirectReferer('*/*');
        }
    }

    /**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $loginUrl = Mage::helper('customer')->getLoginUrl();

        if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

    /**
     * Customer quoteadv history
     */
    public function historyAction()
    {
        //is free user?
        if (!Mage::helper('qquoteadv/license')->validLicense('my-quotes', null, true)) {
            //no error message
            $this->_redirect('*/index/'); //redirect to quote page
            return;
        }

        Mage::dispatchEvent('ophirah_qquoteadv_viewhistory_before', []);

        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');

        $this->getLayout()->getBlock('head')->setTitle($this->__('My Quotes'));

        $block = $this->getLayout()->getBlock('customer.account.link.back');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->renderLayout();

        Mage::dispatchEvent('ophirah_qquoteadv_viewhistory_after', []);
    }

    /**
     * Action to render the print layout
     */
    public function printAction()
    {
        Mage::dispatchEvent('ophirah_qquoteadv_viewprint_before', []);
        if (!$this->_loadValidQuote()) {
            return;
        }

        $this->loadLayout('print');
        $this->renderLayout();

        Mage::dispatchEvent('ophirah_qquoteadv_viewprint_after', []);
    }

    /**
     * Try to load valid quote by id
     *
     * @param int $id
     * @return bool
     */
    protected function _loadValidQuote($id = null)
    {
        if (null === $id) {
            $id = (int)$this->getRequest()->getParam('id');
        }
        if (!$id) {
            $this->_forward('noRoute');
            return false;
        }

        $quote = $this->_initQuote();
        if ($quote) {
            $quoteData = $this->checkUserQuote($id, $this->getCustomerId());
            if ($quoteData) {
                return true;
            } else {
                $this->_redirect('*/*/history');
            }
        }
        return false;
    }

    /**
     * Action to render the PDF for a given quote id
     *
     * @return Mage_Adminhtml_Controller_Action|null
     */
    public function pdfqquoteadvAction()
    {
        $quoteadvId = $this->getRequest()->getParam('id');
        Mage::dispatchEvent('ophirah_qquoteadv_pdfqquoteadv_before', [$quoteadvId]);

        $flag = false;
        if (!empty($quoteadvId)) {
            $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteadvId);
            $quoteItems = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
                ->addFieldToFilter('quote_id', $quoteadvId)
                ->load();

            if ($quoteItems->getSize()) {
                $flag = true;
                if (!isset($pdf)) {
                    $pdf = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($_quoteadv);
                } else {
                    $pages = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($quoteItems);
                    $pdf->pages = array_merge($pdf->pages, $pages->pages);
                }
            }

            if ($flag && isset($pdf)) {
                $realQuoteadvId = $_quoteadv->getIncrementId() ? $_quoteadv->getIncrementId() : $_quoteadv->getId();
                $fileName = Mage::helper('qquoteadv')->__('Price_proposal_%s', $realQuoteadvId);
                return $this->_prepareDownloadResponse($fileName . '.pdf', $pdf->render(), 'application/pdf');
            } else {
                $this->_getSession()->addError($this->__('There are no printable documents related to selected quotes'));
                $this->_redirect('*/*/');
            }
        }

        $this->_redirect('*/*/');
        Mage::dispatchEvent('ophirah_qquoteadv_pdfqquoteadv_after', [$quoteadvId]);
        return null;
    }

    /**
     * Declare headers and content file in responce for file download
     *
     * @param string $fileName
     * @param string $content set to null to avoid starting output, $contentLength should be set explicitly in that case
     * @param string $contentType
     * @param int $contentLength explicit content length, if strlen($content) isn't applicable
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _prepareDownloadResponse($fileName, $content, $contentType = 'application/octet-stream', $contentLength = null)
    {
        $session = Mage::getSingleton('admin/session');
        if ($session->isFirstPageAfterLogin()) {
            $this->_redirect($session->getUser()->getStartupPageUrl());
            return $this;
        }
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', is_null($contentLength) ? strlen($content) : $contentLength)
            ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName)
            ->setHeader('Last-Modified', date('r'));
        if (!is_null($content)) {
            $this->getResponse()->setBody($content);
        }
        return $this;
    }

    /**
     * Clones the quote products based on the old quote id and new one.
     *
     * @param $newQuoteid
     * @param $oldQuoteId
     * @return void
     */
    protected function _copyProducts($newQuoteid, $oldQuoteId)
    {
        $quoteProducts = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
            ->addFieldToFilter('quote_id', $oldQuoteId);
        if (count($quoteProducts->getItems()) > 0) {
            foreach ($quoteProducts as $key => $values) {
                $newProduct = Mage::getModel('qquoteadv/qqadvproduct')
                    ->setData($values->getData())
                    ->setData('quote_id', $newQuoteid)
                    ->unsetData('id');
                $newProduct->save();
                Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersafe_newproduct', ['quote' => Mage::getModel('qquoteadv/qqadvcustomer')->load($newQuoteid), 'product' => $newProduct]);
            }

            Mage::getSingleton("core/session")->setData("ignoreNotAllowedToQuote", true);
        }
    }

    /**
     * @param $quoteData
     * @param $status
     * @return bool
     */
    protected function _checkQuoteStatus($quoteData, $status)
    {
        if($quoteData->getData('status') != $status){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @return array
     */
    protected function _setQuoteUpdatedBy()
    {
        if ($this->isCustomerLoggedIn()) {
            $data = ['created_at' => now(),
                'updated_at' => now(),
                'customer_id' => $this->getCustomerSession()->getId(),
                'email' => Mage::getSingleton('customer/session')->getCustomer()->getEmail(),
                'is_quote' => 1
            ];
            return $data;
        } else {
            $data = ['created_at' => now(),
                'updated_at' => now(),
                'is_quote' => 1
            ];
            return $data;
        }
    }

    /**
     * Check if confirmmode is active
     */
    protected function _checkConfirmMode()
    {
        if (Mage::helper('qquoteadv')->isActiveConfirmMode()) {
            $link = Mage::getUrl('qquoteadv/view/outqqconfirmmode');
            $message = Mage::helper('qquoteadv')->__("You are in a quote confirmation mode, <a href='%s'>log out</a>.", $link);
            $this->getCoreSession()->addNotice($message);
            $this->_redirectReferer(Mage::getUrl('*/*'));
        }
    }

    /**
     * Creates a new quote
     * @return new quote id
     */
    protected function _createNewQuote()
    {
        $newQuoteId = Mage::getModel('qquoteadv/qqadvcustomer')
            ->addData($this->_setQuoteUpdatedBy())
            ->save()
            ->getQuoteId();
        Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersafe_new', ['quote' => Mage::getModel('qquoteadv/qqadvcustomer')->load($newQuoteId)]);
        return $newQuoteId;
    }
}
