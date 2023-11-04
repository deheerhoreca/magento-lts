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
 * Class Ophirah_Qquoteadv_IndexController
 */
class Ophirah_Qquoteadv_IndexController extends Mage_Core_Controller_Front_Action
{
    const XML_PATH_QQUOTEADV_REQUEST_EMAIL_TEMPLATE = 'qquoteadv_quote_emails/templates/request';
    const XML_PATH_QQUOTEADV_REQUEST_PROPOSAL_EMAIL_TEMPLATE = 'qquoteadv_quote_emails/templates/proposal';
    const XML_PATH_QQUOTEADV_REQUEST_CANCEL_EMAIL_TEMPLATE = 'qquoteadv_quote_emails/templates/proposal_cancel';
    const XML_PATH_QQUOTEADV_REQUEST_EMAIL_TEMPLATE_FREE = 'qquoteadv_quote_emails_templates_quote_request_notification_free';

    /**
     * @var bool
     */
    protected $_isEmailExists = false;

    /**
     * @var bool
     */
    protected $_isAjax = false;

    /**
     * @var null
     */
    public $params = null;

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
     * get post data for quickquote view.
     */
    public function quickquoteviewAction(){
        $params = $this->getRequest()->getParams();
        $base64 = array_key_exists('base64', $params) && $params['base64'] == true;


        if (Mage::getStoreConfig('qquoteadv_quote_frontend/catalog/quick_quote_mode') != "1" || !isset($params) || !isset($params['product'])) {
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        $product = Mage::getModel('catalog/product')->load($params['product']);

        $this->loadLayout();
        $this->getLayout()->getBlock('quick.quote.product.render')->setData('post_data', $params);

        if(isset($product)){
            $this->getLayout()->getBlock('quick.quote.product.render')->setData('product', $product);
        }

        $output = $this->getLayout()->getOutput();

        Mage::getSingleton('core/translate_inline')->processResponseBody($output);
        $array = ["result" => 1, "html" => $output];
        $json = json_encode($array);
        if($base64){
            $json = base64_encode($json);
        }
        $this->getResponse()->setBody($json);
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

        // Assign $params to object
        $this->params = $params;
        // dispatch event to alter the param data
        // can be useful for other modules that
        // alter product params
        Mage::dispatchEvent('ophirah_qquoteadv_addQuoteRequestItem_before', ['prodParams' => $this]);
        // Reassign $params with object data
        if ($this->params) {
            $params = $this->params;
        }

        if (array_key_exists('cart', $params)) {
            foreach ($params['cart'] as $key => $value) {
                $this->addFilterAction($value);
            }
        } else {
            $this->addFilterAction($params);
        }

        Mage::dispatchEvent('ophirah_qquoteadv_addQuoteRequestItem_after', ['prodParams' => $this]);
    }

    /**
     * Inserts products to quote
     * 1. First try to add multiple products via the form data.
     * 2. If that fails try to add a product to a quote via product parameter in the URL.
     */
    public function addItemAction()
    {
        $addSuccess = false;
        $postData = $this->getRequest()->getPost();

        if(!isset($postData)){
            $this->getCoreSession()->addError($this->__('Error while creating a quote, no post data.'));
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        if ($this->isActiveConfMode()) {
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        Mage::dispatchEvent('ophirah_qquoteadv_addItem_before', ['session' => Mage::getSingleton('checkout/session')]);

        //only multi add when quick quote mode is disabled
        if (Mage::getStoreConfig('qquoteadv_quote_frontend/catalog/quick_quote_mode') != "1") {
            if (array_key_exists('options', $postData)) {
                if (Mage::helper('qquoteadv/compatibility_apo')->isApoEnabled()) {
                    $addSuccess = $this->_multiAddToQuoteApo();
                }
            }

            if (array_key_exists('related_products', $postData)) {
                $addSuccess = $this->_multiAddToQuote();
            }
        }

        if (!$addSuccess) {
            $this->addAction();
            $addSuccess = true;
        }

        Mage::dispatchEvent('ophirah_qquoteadv_addItem_after', ['prodParams' => $this]);

        if ($addSuccess) {
            Mage::dispatchEvent('ophirah_qquoteadv_addItem_after', ['session' => Mage::getSingleton('checkout/session'), 'result' => $addSuccess]);
            Mage::dispatchEvent('ophirah_qquoteadv_addItem_after_success', ['session' => Mage::getSingleton('checkout/session'), 'result' => $addSuccess]);
        } else {
            Mage::dispatchEvent('ophirah_qquoteadv_addItem_after_error', ['session' => Mage::getSingleton('checkout/session'), 'result' => $addSuccess]);
        }

        //This should be already set in addDataAction()
        if (!$this->getResponse()->isRedirect() && !$this->_isAjax) {
            $this->_redirect('qquoteadv/index/');
        }
    }

    /**
     * Action for the add item function in ajax mode
     */
    public function addItemAjaxAction()
    {
        $this->_isAjax = true;
        $this->addItemAction();
    }

    /**
     * Module Magento Mechanics uses multi order configurables
     *
     * @param $params
     * @internal param $ ->  array $params with multi order
     */
    public function explodeMultiOrder($params)
    {
        $sa = $params['sa'];

        foreach ($sa as $key => $value) {
            $newParams = [];
            $newParams['product'] = $params['product'];
            $newParams['related_product'] = $params['related_product'];
            $newParams['qty'] = ($params['qtys'][$key] > 0) ? $params['qtys'][$key] : 0;
            $newParams['super_attribute'] = $value;

            if ($newParams['qty'] > 0) {
                self::addFilterAction($newParams);
            }
        }
    }


    /**
     * Convert param attributeEncode to unserialized attribute
     *
     * @param $params
     * @return mixed => array $params with unserialized attribute
     * @internal param $ => array $params with attributeEncode key
     */
    public function attributeDecode($params)
    {
        $attribute = unserialize(base64_decode($params['attributeEncode']));
        unset($attribute['uenc']);
        return $attribute;
    }


    /**
     * Insert filter quote data
     * @param $params
     */
    public function addFilterAction($params)
    {
        // Magento Mechanics - Configurable Product Grid View
        if (isset($params['is_multi_order'])) {
            if (count($params['sa']) > 0) {
                $this->explodeMultiOrder($params);
            }
        } else {

            // set the qty to 1 or minimum quantity if it is empty
            if (!isset($params['qty']) || !is_numeric($params['qty'])) {
                if(isset($params['product']) && is_numeric($params['product'])){
                    $product = Mage::getModel('catalog/product')->load($params['product']);
                    $minimumQty = $product->getStockItem()->getMinSaleQty();
                    if($minimumQty == 0){
                        $minimumQty = 1;
                    }

                    $params['qty'] = $minimumQty;
                } else {
                    $params['qty'] = 1;
                }
            }

            /**
             * if addAction is called from cart or quote page
             * from cart/quote page, the parameter is serialized string and is passed as base64 encoded form
             * hence, we have to decode it
             */
            if (array_key_exists('attributeEncode', $params)) {
                $superAttribute = serialize(self::attributeDecode($params));
            } else {
                $superAttribute = serialize($params);
            }

            // if the product is Grouped Product
            if (isset($params['super_group'])) {
                $superGroup = $params['super_group'];

                if (array_sum($superGroup) > 0) {
                    // adding each super group product separately as simple product
                    foreach ($superGroup as $key => $value) {
                        // don't add product if it have quantity value 0
                        if ($value != 0 && is_numeric($value)) {
                            $groupParams['product'] = $key;
                            $groupParams['qty'] = (int)$value;
                            // Quick Quote Mode
                            if (isset($params['customer'])) {
                                $groupParams['customer'] = $params['customer'];
                            }
                            $this->addDataAction($groupParams, $superAttribute);
                        }
                    }
                } else {
                    if (!$this->_isAjax) {
                        $this->getCoreSession()->addNotice($this->__('Please specify product quantity.'));
                        $this->_redirectReferer(Mage::getUrl('*/*'));
                    } else {
                        $block = $this->getLayout()->createBlock('core/template')->setTemplate('qquoteadv/ajaxerror.phtml');
                        $output = $block->toHtml();

                        $totalText = Mage::helper('qquoteadv')->totalItemsText();

                        $array = ["result" => 1, "html" => $output, "itemstext" => $totalText];
                        $json = json_encode($array);
                        $this->getResponse()->setBody($json);

                    }
                }
            } else {
                $this->addDataAction($params, $superAttribute);
            }
        }
    }

    /**
     * Overwrite for _redirect with fallback for ajax actions
     *
     * @param $path
     * @param array $arguments
     * @return Mage_Core_Controller_Varien_Action|void
     */
    protected function _redirect($path, $arguments = [])
    {
        if ($this->_isAjax) {
            $this->_returnAjax();
        } else {
            parent::_redirect($path, $arguments);
        }
    }

    /**
     * Overwrite for _redirectUrl with fallback for ajax actions
     *
     * @param $url
     * @return Mage_Core_Controller_Varien_Action|void
     */
    protected function _redirectUrl($url)
    {
        if ($this->_isAjax) {
            $this->_returnAjax();
        } else {
            parent::_redirectUrl($url);
        }
    }

    /**
     * Overwrite for _redirectReferer with fallback for ajax actions
     *
     * @param null $defaultUrl
     * @return Mage_Core_Controller_Varien_Action|void
     */
    protected function _redirectReferer($defaultUrl = null)
    {
        if ($this->_isAjax) {
            $this->_returnAjax();
        } else {
            parent::_redirectReferer($defaultUrl);
        }
    }

    /**
     * Overwrite for _return with fallback for ajax actions
     */
    protected function _return()
    {
        if ($this->_isAjax) {
            $this->_returnAjax();
        }
    }

    /**
     * Function that generates the json for an ajax return
     */
    protected function _returnAjax()
    {
        $base64 = false;
        $msg = Mage::getSingleton('core/session')->getMessages();
        $errors = count($msg->getErrors());
        $product = Mage::registry('product');
        $products = Mage::registry('products_ajax_c2q');
        $postData = $this->getRequest()->getPost();
        if(array_key_exists('base64', $postData)) {
            $base64 = true;
        }

        if ($errors) {
            $array = ["result" => 0, "producturl" => $product->getProductUrl()];
            $json = json_encode($array);
            $this->getResponse()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody($json);
        } else {
            if (Mage::helper('qquoteadv')->isActiveConfirmMode()) {
                $html = $this->getLayout()->getMessagesBlock()->toHtml();
                $html .= '<br><br><button type="button" onclick="cancelMsg();" title="'. Mage::helper('sales')->__('Close').'" class="button btn-cart"><span><span>'. Mage::helper('sales')->__('Close').'</span></span></button>';

                Mage::getSingleton('core/translate_inline')->processResponseBody($html);
                $array = ["result" => 1, "html" => $html];
                $json = json_encode($array);
                if ($base64) {
                    $json = base64_encode($json);
                }
                $this->getResponse()->setBody($json);
            } else {
                //no errors and no confirm mode
                $msg = Mage::getSingleton('core/session')->getMessages(true);
                $this->loadLayout();

                $this->getLayout()->getMessagesBlock()->addMessages($msg);
                $this->getLayout()->getBlock('ajaxadd')->setData('post_data', $postData);
                $this->getLayout()->getBlock('ajaxadd')->setData('product', $product);
                $this->getLayout()->getBlock('ajaxadd')->setData('products', $products);
                $this->getLayout()->getBlock('ajaxadd')->setData('errors', $errors);
                $this->_initLayoutMessages('core/session');
                //$this->renderLayout();

                $output = $this->getLayout()->getOutput();
                $totalText = Mage::helper('qquoteadv')->totalItemsText();

                Mage::getSingleton('core/translate_inline')->processResponseBody($output);
                $array = ["result" => 1, "html" => $output, "itemstext" => $totalText];
                $json = json_encode($array);
                if($base64){
                    $json = base64_encode($json);
                }
                $this->getResponse()->setBody($json);
            }
        }
    }

    /**
     * Insert quote data (main data add function)
     * @param array $params post parameter for product
     * @param string $superAttribute
     * @return null
     */
    public function addDataAction($params, $superAttribute)
    {
        Mage::dispatchEvent('ophirah_qquoteadv_addData_before', ['params' => $params, 'super_attribute' => $superAttribute]);
        $modelCustomer = Mage::getModel('qquoteadv/qqadvcustomer');
        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
        $checkQty = null;

        if ($this->getCustomerSession()->isLoggedIn()) {
            $qcustomer = [
                'created_at' => now(),
                'updated_at' => now(),
                'customer_id' => $this->getCustomerSession()->getId(),
                'store_id' => Mage::app()->getStore()->getStoreId()
            ];
        } else {
            $qcustomer = [
                'created_at' => now(),
                'updated_at' => now(),
                'store_id' => Mage::app()->getStore()->getStoreId()
            ];
        }

        try {
            // if quote_id is not set then insert into qquote_customer table and set quote_id
            if ($this->getCustomerSession()->getQuoteadvId() == null) {
                // save data to qquote_customer table and getting inserted row id
                $qId = $modelCustomer->addQuote($qcustomer)->getQuoteId();
                // setting inserted row id of qquote_customer table into session
                $this->getCustomerSession()->setQuoteadvId($qId);
            }

            $hasOption = 0;
            $options = [];
            if (isset($params['options'])) {
                $options = $params['options'];
            }

            $attr = [];
            if (isset($superAttribute)) {
                $attr = unserialize($superAttribute);

                if (isset($attr['options'])) {
                    $options = $attr['options'];
                    $params['qty'] = $attr['qty'];
                }
            }
            $params['qty'] = $params['qty'] > 0 ? $params['qty'] : 1;
            $params['options'] = $options;

            // Declare Params Object (It should be used instead of the $params array)
            $paramsObj = new Varien_Object($params);

            // Decalre Customer Object
            // Available in Quick Quote Mode
            $customer = new Varien_Object();
            if(isset($params['customer'])){
                $customer->addData($params['customer']);
                // remove data from $paramsObj
                $paramsObj->unsetData('customer');
            }

            $product = Mage::getModel('catalog/product')->load($paramsObj->getData('product'));
            $product->getTypeInstance(true)->prepareForCartAdvanced($paramsObj, $product);
            if ($paramsObj->getData('options')) {
                $hasOption = 1;
                $options = serialize($paramsObj->getData('options'));
            } else {
                $options = '';
            }
            if ($options && $superAttribute) {
                $superAttribute = unserialize($superAttribute);
                $superAttribute['options'] = unserialize($options);
                $superAttribute = serialize($superAttribute);
            }

            /**
             * check if the customer has already added the particular product
             * if the product is already added by the customer then add only the quantity for that row
             * otherwise add new row for product
             */

            $productsCollection = $modelProduct->getCollection()
                ->addFieldToFilter('quote_id', $this->getCustomerSession()->getQuoteadvId())
                ->addFieldToFilter('product_id', $paramsObj->getData('product'));

            if ($hasOption) {
                $productsCollection->addFieldToFilter('has_options', $hasOption);
                $productsCollection->addFieldToFilter('options', $options);
            }

            $product = Mage::getModel('catalog/product')->load($paramsObj->getData('product'));
            $product_url = $product->getData('url_path');

            //register the product
            if (Mage::registry('product') != null) {
                Mage::unregister('product');
            }
            Mage::register('product', $product);

            //register an array of products in case of multi add
            if (Mage::registry('products_ajax_c2q') != null) {
                $ajaxProducts = Mage::registry('products_ajax_c2q');
                Mage::unregister('products_ajax_c2q');
                $ajaxProducts[] = $product;
                Mage::register('products_ajax_c2q', $ajaxProducts);
            } else {
                Mage::register('products_ajax_c2q', [$product]);
            }

            if ($productsCollection->getData() != []) {
                $pID = $paramsObj->getData('product');
                $pInfo = Mage::getModel('catalog/product')->load($pID);

                if ($pInfo->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                    $isFound = false;
                    $type = $pInfo->getTypeId();

                    foreach ($productsCollection as $item) {
                        if (Mage::helper('qquoteadv/catalog_product_data')->compareConfigurable($pID, $superAttribute, $item->getAttribute())) {
                            $isFound = true;

                            // adding qty to product if the customer has previously added in the current session
                            $qtySum = ['qty' => $paramsObj->getData('qty') + $item->getQty()];

                            // Quantity check Configurables simple product
                            $check = $this->checkProdTypeQty($attr, $qtySum['qty'], $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->updateProduct($item->getId(), $qtySum);
                            }

                            break;
                        }

                    }

                    //no_product_merge
                    if (Mage::getStoreConfig('qquoteadv_advanced_settings/general/no_product_merge')) {
                        $isFound = false;
                    }

                    if (!$isFound) {
                        if ($this->getAllowedToQuotemode($pInfo, $paramsObj)) {
                            $qproduct = [
                                'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                                'product_id' => $paramsObj->getData('product'),
                                'qty' => $paramsObj->getData('qty'),
                                'attribute' => $superAttribute,
                                'has_options' => $hasOption,
                                'options' => $options,
                                'store_id' => Mage::app()->getStore()->getStoreId()
                            ];

                            // Quantity check Configurables simple product
                            $check = $this->checkProdTypeQty($attr, $paramsObj->getData('qty'), $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->addProduct($qproduct);
                            }
                        }
                    }
                } elseif ($pInfo->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    $isFound = false;
                    $type = $pInfo->getTypeId();

                    foreach ($productsCollection as $item) {
                        if (Mage::helper('qquoteadv/catalog_product_data')->compareBundles($pID, $superAttribute, $item->getAttribute())) {
                            $isFound = true;
                            // adding qty to product if the customer has previously added in the current session
                            $qtySum = ['qty' => $paramsObj->getData('qty') + $item->getQty()];

                            // Quantity check bundeld simple products
                            $check = $this->checkProdTypeQty($paramsObj->getData(), $qtySum['qty'], $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->updateProduct($item->getId(), $qtySum);
                            }

                            break;
                        }
                    }

                    //no_product_merge
                    if (Mage::getStoreConfig('qquoteadv_advanced_settings/general/no_product_merge')) {
                        $isFound = false;
                    }

                    if (!$isFound) {
                        if ($this->getAllowedToQuotemode($pInfo, $paramsObj)) {
                            $qproduct = [
                                'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                                'product_id' => $paramsObj->getData('product'),
                                'qty' => $paramsObj->getData('qty'),
                                'attribute' => $superAttribute,
                                'has_options' => $hasOption,
                                'options' => $options,
                                'store_id' => Mage::app()->getStore()->getStoreId()
                            ];

                            // Quantity check Configurables simple product
                            $check = $this->checkProdTypeQty($paramsObj->getData(), $paramsObj->getData('qty'), $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->addProduct($qproduct);
                            }
                        }
                    }
                } elseif (Mage::getModel('qquoteadv/qqadvproductdownloadable')->isDownloadable($pInfo)) {
                    /**
                     *  Checks if downloadable exists on the quote.
                     *  If exists then update otherwise create new product.
                     */
                    $downloadableUpdated = false;
                    $links = Mage::getModel('qquoteadv/qqadvproductdownloadable')->getLinksFromParams($params);
                    foreach ($productsCollection as $item) {
                        if (Mage::getModel('qquoteadv/qqadvproductdownloadable')->exists($item, $links)) {
                            // Update Product
                            $qtySum = ['qty' => $paramsObj->getData('qty') + $item->getQty()];
                            $checkQty = $modelProduct->updateProduct($item->getId(), $qtySum);
                            $downloadableUpdated = true;
                            break;
                        }
                    }

                    // Create new product
                    if(!$downloadableUpdated){
                        $type = $pInfo->getTypeId();

                        if ($this->getAllowedToQuotemode($pInfo, $paramsObj)) {
                            $qproduct = [
                                'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                                'product_id' => $paramsObj->getData('product'),
                                'qty' => $paramsObj->getData('qty'),
                                'attribute' => $superAttribute,
                                'has_options' => $hasOption,
                                'options' => $options,
                                'store_id' => Mage::app()->getStore()->getStoreId()
                            ];

                            // Quantity check Configurables simple product
                            $check = $this->checkProdTypeQty($paramsObj->getData(), $paramsObj->getData('qty'), $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->addProduct($qproduct);
                            }
                        }
                    }
                } else {
                    foreach ($productsCollection as $item) {
                        //no_product_merge
                        if(Mage::getStoreConfig('qquoteadv_advanced_settings/general/no_product_merge')){
                            $type = $pInfo->getTypeId();

                            if ($this->getAllowedToQuotemode($pInfo, $paramsObj)) {
                                $qproduct = [
                                    'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                                    'product_id' => $paramsObj->getData('product'),
                                    'qty' => $paramsObj->getData('qty'),
                                    'attribute' => $superAttribute,
                                    'has_options' => $hasOption,
                                    'options' => $options,
                                    'store_id' => Mage::app()->getStore()->getStoreId()
                                ];

                                // Quantity check Configurables simple product
                                $check = $this->checkProdTypeQty($paramsObj->getData(), $paramsObj->getData('qty'), $type);

                                if ($check !== false) {
                                    $url = $this->getRequest()->getServer('HTTP_REFERER');
                                    $checkQty = new Varien_Object();
                                    $checkQty->setHasError(true);
                                    $checkQty->setProductUrl($url);
                                    $checkQty->setMessage($check);
                                } else {
                                    $checkQty = $modelProduct->addProduct($qproduct);
                                }
                            } else {
                                //not allowed to quote mode
//                                $errorMsg = $this->__('Product %s couldn\'t be added to the Quote Request', $product->getName());
//                                if ($this->getCoreSession()->getUseNotice(true)) {
//                                    $this->getCoreSession()->addNotice($errorMsg);
//                                }
                            }
                            break;
                        } else {
                            // adding qty to product if the customer has previously added in the current session
                            $qtySum = ['qty' => $paramsObj->getData('qty') + $item->getQty()];
                            $checkQty = $modelProduct->updateProduct($item->getId(), $qtySum);
                            break;
                        }
                    }
                }
            } else {
                //set paramsAttr
                if($paramsObj->getData('attribute')) {
                    $paramsAttr = $paramsObj->getData('attribute');
                } else {
                    $paramsAttr = [];
                }

                if ($paramsObj->getData('product') || isset($paramsAttr['product'])) {
                    if (isset($paramsAttr['product'])) {
                        $_product = Mage::getModel('catalog/product')->load($paramsAttr['product']);
                    } else {
                        $_product = Mage::getModel('catalog/product')->load($paramsObj->getData('product'));
                    }

                    $type = $_product->getTypeId();
                    if ($type == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                        if ($this->getAllowedToQuotemode($_product, $paramsObj)) {
                            $qproduct = [
                                'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                                'product_id' => $paramsObj->getData('product'),
                                'qty' => $paramsObj->getData('qty'),
                                'attribute' => $superAttribute,
                                'has_options' => $hasOption,
                                'options' => $options,
                                'store_id' => Mage::app()->getStore()->getStoreId()
                            ];

                            // Quantity check bundled simple product
                            $check = $this->checkProdTypeQty($paramsObj->getData(), $paramsObj->getData('qty'), $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->addProduct($qproduct);
                            }
                        }
                    }

                    // Quantity check product
                    if ($_product->getTypeId()) {
                        if (isset($attr) && $_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                            $check = $this->checkProdTypeQty($attr, $paramsObj->getData('qty'), $_product->getTypeId());
                        } elseif ($paramsObj->getData() && $_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                            $check = $this->checkProdTypeQty($paramsObj->getData(), $paramsObj->getData('qty'), $_product->getTypeId());
                        } else {
                            $check = $this->checkProdTypeQty($_product, $paramsObj->getData('qty'), $_product->getTypeId());
                        }

                        if ($check != false) {
                            $url = $this->getRequest()->getServer('HTTP_REFERER');
                            $checkQty = new Varien_Object();
                            $checkQty->setHasError(true);

                            if (isset($product_url) && $_product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                                $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . $product_url;
                            }

                            $checkQty->setProductUrl($url);
                            $checkQty->setMessage($check);
                        }
                    }

                    if ($this->getAllowedToQuotemode($_product, $paramsObj) && is_null($checkQty)) {
                        $qproduct = [
                            'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                            'product_id' => $paramsObj->getData('product'),
                            'qty' => $paramsObj->getData('qty'),
                            'attribute' => $superAttribute,
                            'has_options' => $hasOption,
                            'options' => $options,
                            'store_id' => Mage::app()->getStore()->getStoreId()
                        ];

                        $checkQty = $modelProduct->addProduct($qproduct);
                    }
                }
            }

            if (is_null($checkQty)) { // product has not been added redirect with error
                $checkQty = new Varien_Object();
                $checkQty->setHasError(true);
                if (!$checkQty->getMessage()) {
                    $checkQty->setMessage(Mage::helper('qquoteadv')->__('Product cannot be added to quote list'));
                }

                $url = $this->getRequest()->getServer('HTTP_REFERER');
                $checkQty->setProductUrl($url);
            }

            if ($checkQty->getHasError()) {
                $this->getCoreSession()->addError($checkQty->getMessage());
                $this->_redirectUrl($checkQty->getProductUrl());
                return;
            }

            $product = Mage::getModel('catalog/product')->load($paramsObj->getData('product'));

            /**
             * deleting the item from cart if cartid is set in the url
             * i.e. if the addAction is called from 'Move to quote' button of cart page
             * in this case, we have to add the item to quote and delete from cart
             */

            // Create Quotation from Quick Quote Form
            if (Mage::helper('qquoteadv')->checkQuickQuote() && $customer->getData()){
                // Create a request item
                $requestParams = [];
                $base2QuoteRate = $modelCustomer->getBase2QuoteRate();
                $finalPrice = Mage::helper('qquoteadv')->_applyPrice($modelProduct->getId(), $paramsObj->getQty());

                // Set Request Item parameters
                $requestParams['quote_id'] = $this->getCustomerSession()->getQuoteadvId();
                $requestParams['product_id'] = $modelProduct->getProductId();
                $requestParams['request_qty'] = $paramsObj->getData('qty');
                $requestParams['quoteadv_product_id'] = $modelProduct->getId();
                $requestParams['owner_base_price'] = $finalPrice;
                $requestParams['original_price'] = $finalPrice;
                $requestParams['owner_cur_price'] = $finalPrice * $base2QuoteRate;
                $requestParams['original_cur_price'] = $finalPrice * $base2QuoteRate;

                // Save Request Item
                $requestItemModel = Mage::getModel('qquoteadv/requestitem');
                if (Mage::helper('qquoteadv')->checkAutomaticTierPrices()) {
                    $requestItemModel->getResourceCollection()->saveByProductTierQty($modelProduct);
                }
                $requestItemModel->addItem($requestParams);

                // Create Quote
                $this->quoteRequestAction();

                // Return to success page //This is already trown in the addItemAction
                //$this->_redirect('qquoteadv/index/success');
                return null;
            }

            $succesMsg = $this->__('Product %s successfully added to Quote Request', $product->getName());
            $redirectToQuote = Mage::getStoreConfig('qquoteadv_quote_frontend/shoppingcart_quotelist/redirect_to_quotation', Mage::app()->getStore()->getStoreId());
            if (array_key_exists('cartid', $paramsObj->getData()) && $paramsObj->getCartid()) {
                if ($this->getAllowedToQuotemode($product, $paramsObj)) {
                    $this->getCoreSession()->addSuccess($succesMsg);
                }
                $this->_redirect('qquoteadv/index');
                return null;
            } elseif (!$redirectToQuote || $redirectToQuote == 0) {
                $backUrl = $this->_getRefererUrl();
                $this->getCoreSession()->addSuccess($succesMsg);
                $this->_redirectUrl($backUrl);
                return null;

            } elseif ($redirectToQuote == 2) {
                if (!Mage::getStoreConfig('checkout/cart/redirect_to_cart', Mage::app()->getStore()->getStoreId())) {
                    $backUrl = $this->_getRefererUrl();
                    $this->getCoreSession()->addSuccess($succesMsg);
                    $this->_redirectUrl($backUrl);
                    return null;
                }
            }

            //This happens probably only when $redirectToQuote == 1
            if ($this->_isAjax) {
                $this->getCoreSession()->addSuccess($succesMsg);
            }

            Mage::dispatchEvent('ophirah_qquoteadv_addData_after', ['params' => $params, 'super_attribute' => $superAttribute, 'qquoteadv_product_id'=> $checkQty->getId()]);
            $this->_redirect('*/*/');
            return null;

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

        $this->_return();
    }

    /**
     * Check if the request qty is possible for the given product
     *
     * @param $prodData
     * @param $qty
     * @param $type
     * @return bool|string
     */
    public function checkProdTypeQty($prodData, $qty, $type)
    {
        $return = false;
        $childItems = [];

        if ($prodData['product']) {
            $product = Mage::getModel('catalog/product')->load($prodData['product']);
        } else {
            $product = $prodData;
        }

        if ($type == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $bundleSelection = Mage::getModel('qquoteadv/bundle')->getBundleOptionsSelection($product, $prodData);

            $childItems = [];
            foreach ($bundleSelection as $bundleItem) {
                foreach ($bundleItem['value'] as $option) {
                    if (isset($option['id'])) { // Only check if a product is selected
                        $childItems[$bundleItem['option_id']] = ['id' => $option['id'], 'qty' => $option['qty']];
                    }
                }
            }
        }

        if ($type == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $checkProduct = Mage::getModel('catalog/product_type_configurable')
                ->getProductByAttributes($prodData['super_attribute'], $product);

            //checkProduct is null if the given attributes don't match to a product
            if($checkProduct === null){
                //return Mage::helper('qquoteadv')->__("No product found for the given attributes");
                $checkProduct =  Mage::getModel('catalog/product')->load($product->getId());
            }
        } else {
            $checkProduct = $product;
        }

        if (count($childItems) > 0) {
            foreach ($childItems as $childItem) {
                if ($childItem['id'] > 0 && $childItem['qty'] > 0) {
                    $product = Mage::getModel('catalog/product')->load($childItem['id']);
                    $check = Mage::helper('qquoteadv')->checkQuantities($product, $childItem['qty']);
                    if ($check->getHasError()) {
                        $return .= $check->getMessage() . "<br />";
                    }
                }
            }
        } else {
            $check = Mage::helper('qquoteadv')->checkQuantities($checkProduct, $qty);
            if ($check->getHasError()) {
                $return = $check->getMessage();
            }
        }

        return $return;
    }

    /**
     * Move item to cart
     *
     */
    public function moveAction()
    {
        if ($this->isActiveConfMode()) {
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        $params = [];
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost('quote', []);
            Mage::dispatchEvent('ophirah_qquoteadv_move_before', [$params]);
            //$params = $this->getFilteredParams();

            if (count($params) > 0) {
                $errorCart = [];
                $errorQuote = [];
                foreach ($params as $lineId => $param) {
                    $param['attributeEncode'] = unserialize(base64_decode($param['attributeEncode']));

                    // updating attribute product quantity with the product quantity
                    $param['attributeEncode']['qty'] = $param['qty'];

                    $product = Mage::getModel('catalog/product')->load($param['product']);
                    try {
                        // add item to cart
                        Mage::getModel('checkout/cart')->addProduct($product, $param['attributeEncode'])->save();
                    } catch (Exception $e) {
                        $errorCart[] = $this->__("Item %s wasn't moved to Shopping cart", $product->getName());
                        Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                    }

                    try {
                        // remove item to quote mode
                        Mage::getModel('qquoteadv/qqadvproduct')->deleteQuote($lineId);
                    } catch (Exception $e) {
                        $errorQuote[] = $this->__("Item %s wasn't removed from Quote mode", $product->getName());
                        Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                    }
                }

                $error = '';
                if (count($errorCart) > 0) {
                    $error .= implode("\n", $errorCart);
                }
                if (count($errorQuote) > 0) {
                    $error .= implode("\n", $errorQuote);
                }

                if (strlen($error) > 2) {
                    $this->getCoreSession()->addError($error);
                    $this->_redirect('*/*/');
                    return;
                } else {
                    $this->getCoreSession()->addSuccess($this->__('All items were moved to cart successfully.'));
                    $this->_redirect('checkout/cart/');
                    return;
                }
            }
        }

        $this->_redirect('checkout/cart/');
        //$this->_redirect('*/*/');
        Mage::dispatchEvent('ophirah_qquoteadv_move_after', [$params]);
    }

    /**
     * Actions that loads the miniquote block using ajax
     */
    public function miniQuoteAction(){
        $this->loadLayout();

        $miniQuoteContent = $this->getLayout()->getBlock('miniquote_content');
        if($miniQuoteContent){
            $result['content'] = $this->getLayout()->getBlock('miniquote_content')->toHtml();
            $result['success'] = 1;
            $result['message'] = "";
            $result['qty'] = Mage::helper('qquoteadv')->getLinkQty();
        } else {
            $result['success'] = 0;
            $result['error'] = "";
        }

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Delete product from quote with ajax
     *
     */
    public function ajaxDeleteAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        $params = $this->getRequest()->getParams();
        Mage::dispatchEvent('ophirah_qquoteadv_delete_before', [$id]);
        $result = [];
        if (!isset($params['options'])) {
            $params['options'] = [];
        }
        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
        if ($id) {
            try {
                Mage::dispatchEvent('ophirah_qquoteadv_delete_before', ['product' => $modelProduct]);
                $modelProduct->deleteQuote($id);
                $this->loadLayout();
                $result['content'] = $this->getLayout()->getBlock('miniquote_content')->toHtml();
                $result['success'] = 1;
                $result['message'] = $this->__('Item was deleted successfully.');
                $result['qty'] = Mage::helper('qquoteadv')->getTotalQty();
                Mage::dispatchEvent('ophirah_qquoteadv_delete_after', ['product' => $modelProduct]);
            } catch (Exception $e) {
                $result['success'] = 0;
                $result['error'] = $this->__("Can't remove item");
            }
        }
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        Mage::dispatchEvent('ophirah_qquoteadv_delete_after', [$id]);
    }

    /**
     * Delete product from quote
     *
     */
    public function deleteAction()
    {
        // get the product id to delete
        $id = $this->getRequest()->getParam('id');

        Mage::dispatchEvent('ophirah_qquoteadv_delete_before', [$id]);

        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');

        try {
            Mage::dispatchEvent('ophirah_qquoteadv_delete_before', ['product' => $modelProduct]);
            // delete the row from quote_product table
            //$modelProduct->setId($id)->delete();
            $modelProduct->deleteQuote($id);
            $this->getCoreSession()->addSuccess($this->__('Item was deleted successfully.'));
            Mage::dispatchEvent('ophirah_qquoteadv_delete_after', ['product' => $modelProduct]);
        } catch (Exception $e) {
            $this->getCoreSession()->addError($this->__("Can't remove item"));
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
        }

        $this->_redirectReferer(Mage::getUrl('*/*'));
        Mage::dispatchEvent('ophirah_qquoteadv_delete_after', [$id]);
    }

    /**
     * Delete the tier qty from a quote product
     *
     */
    public function deleteTierAction()
    {
        // get the product id to delete
        $requestid = $this->getRequest()->getParam('request_id');
        $modelProduct = Mage::getModel('qquoteadv/requestitem');

        try {
            $modelProduct->deleteTierQty($requestid);
            $this->getCoreSession()->addSuccess($this->__('Tier quantity was deleted successfully.'));
        } catch (Exception $e) {
            $this->getCoreSession()->addError($this->__("Can not remove quantity"));
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
        }

        $this->_redirectReferer(Mage::getUrl('*/*'));

    }

    /**
     * Action to load the quote basked
     */
    public function indexAction()
    {
        if (!Mage::helper('qquoteadv')->isEnabled()) {
            $this->_forward('404');
            return;
        }

        Mage::dispatchEvent('ophirah_qquoteadv_index_before', []);

        Mage::helper('qquoteadv')->deleteNotAllowedProductsInQuoteFromSession();

        $oldQuoteadvId = Mage::getSingleton('customer/session')->getOldQuoteadvId();
        if(isset($oldQuoteadvId) && !empty($oldQuoteadvId)){
            $link = Mage::getUrl('qquoteadv/index/outeditmode');
            $message = Mage::helper('qquoteadv')->__("You are in a quote edit mode, <a href='%s'>log out</a>.", $link);
            $this->getCoreSession()->addNotice($message);
        }

        $this->loadLayout();
        $this->_initLayoutMessages('core/session');
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
        Mage::dispatchEvent('ophirah_qquoteadv_index_after', []);
    }

    /**
     * Function that removes the edit quote session var and redirect to the quote cart
     */
    public function outeditmodeAction() {
        Mage::dispatchEvent('ophirah_qquoteadv_outeditmode_before', []);

        Mage::getSingleton('customer/session')->unsOldQuoteadvId();
        $this->_redirect('qquoteadv/index');

        Mage::dispatchEvent('ophirah_qquoteadv_outeditmode_after', []);
    }

    /**
     * Initialize quote request before saving
     *
     * @param bool|false $skip
     * @return array
     * @throws Exception
     */
    protected function _initQuoteRequestSave($skip = false)
    {
        $itemsData = [];
        $productsData = [];

        $paramsQuote = $this->getRequest()->getPost('quote', []);
        $paramsProduct = $this->getRequest()->getPost('quote_request', []);

        $quoteId = $this->getCustomerSession()->getQuoteadvId();

        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        if (is_array($paramsProduct) & count($paramsProduct) > 0) {
            foreach ($paramsProduct as $quoteadvProductId => $items) {
                $productId = $items['product_id'];

                // Get Qty for request
                // Could be in different formats
                // according to installed modules
                if (isset($items['qty'])) {
                    $orderQty = $items['qty'];
                } elseif (isset($paramsQuote[$quoteadvProductId]['qty'])) {
                    $orderQty = $paramsQuote[$quoteadvProductId]['qty'];
                } else {
                    $orderQty = 1;
                }
                if (!is_array($orderQty)) {
                    $orderQty = [$orderQty];
                }

                $passTroughAttribute = self::attributeDecode($paramsQuote[$quoteadvProductId]);
                if ($passTroughAttribute == false) {
                    //attributeEncode is not available in the template, fall back to available database data
                    $passTroughAttribute = unserialize(
                        Mage::getModel('qquoteadv/qqadvproduct')->load($quoteadvProductId)->getAttribute()
                    );
                }
                $items['attribute'] = $passTroughAttribute;
                $items['attribute']['qty'] = $orderQty[0];
                $items['attribute'] = serialize($items['attribute']);

                //preparing items
                if (isset($orderQty)) {
                    foreach ($orderQty as $index => $qty) {
                        $qty = ($qty > 0) ? $qty : 1;
                        if (is_numeric($qty) && $qty > 0) {

                            //#originalPrice
                            $ownerPrice = Mage::helper('qquoteadv')->_applyPrice($quoteadvProductId, $qty, false, false, false);
                            $originalPrice = Mage::helper('qquoteadv')->_applyPrice($quoteadvProductId, 1, false, false, false);
                            //#current currency price
                            $ownerCurPrice = Mage::helper('qquoteadv')->_applyPrice($quoteadvProductId, $qty, $currencyCode, false, false);
                            $originalCurPrice = Mage::helper('qquoteadv')->_applyPrice($quoteadvProductId, 1, $currencyCode, false, false);

                            $itemsData[] = [
                                'quote_id' => $quoteId,
                                'product_id' => $productId,
                                'request_qty' => $qty,
                                'owner_base_price' => $ownerPrice,
                                'original_price' => $originalPrice,
                                'owner_cur_price' => $ownerCurPrice,
                                'original_cur_price' => $originalCurPrice,
                                'quoteadv_product_id' => $quoteadvProductId
                            ];
                        }
                    }
                }

                //preparing product notes
                $clientRequest = null;
                if (isset($items['client_request'])) {
                    $clientRequest = trim($items['client_request']);
                    if ($clientRequest == $this->__('Enter your comments at any time. Click Update Quote to save your changes.')) {
                        $clientRequest = "";
                    }
                }

                //update quoteadv product item
                $productsData[] = [
                    'id' => $quoteadvProductId,
                    'qty' => $items['qty'][0],
                    'attribute' => $items['attribute'],
                    'client_request' => $clientRequest
                ];
            }
        }

        return [
            'itemsData' => $itemsData,
            'productsData' => $productsData,
        ];
    }

    /**
     * Store temporary Quote data in Session
     *
     * Used for Quote Request page to
     * store client request comments
     *
     * TODO: Create feature like: 'save Cart for later'
     * store in database to save the
     * Quote for the customer to place the
     * quote request later (feature like save Cart)
     */
    public function storeQuoteAction()
    {
        Mage::dispatchEvent('ophirah_qquoteadv_storeQuote_before', []);

        // Store post data in customer session
        if ($this->getRequest()->getPost()) {
            $postData = $this->getCustomerSession()->setQuoteData($this->getRequest()->getPost());
            $quoteData = $postData->getQuoteData();
            if($quoteData){
                // Check for Quote Id
                if(isset($quoteData['quote_id'])){
                    $qquoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteData['quote_id']);
                    // Add current customer data to the quote
                    $qquoteadv->addData($quoteData['customer']);
                    // Set customer data in the session
                    $this->getCustomerSession()->setData('quoteAddresses', Mage::helper('qquoteadv/address')->getAddresses($qquoteadv));
                }
            }
        }

        // Redirect to correct url
        if ($this->getRequest()->getParam('url') == 'continue') {
            if ($this->getCustomerSession()->getData('lastUrl')) {
                $url = $this->getCustomerSession()->getData('lastUrl');
                $this->_redirectUrl($url);
            } elseif ($this->getCustomerSession()->getData('continue_shopping_url')) {
                $url = $this->getCustomerSession()->getData('continue_shopping_url');
                $this->_redirectUrl($url);
            } else {
                $this->_redirectUrl(Mage::getUrl('*/*'));
            }
        } else {
            $this->_redirectUrl(Mage::getUrl('*/*'));
        }

        Mage::dispatchEvent('ophirah_qquoteadv_storeQuote_after', []);
    }

    /**
     * Save customer request
     *
     * $skip can be set in case of a shipping rate request
     * @param bool $skip
     * @param bool $printOnly
     * @return null
     */
    public function quoteRequestAction($skip = false, $printOnly = false)
    {
        $quoteId = 0;

        try {
            $message = '';
            $welcome = true;
            $email = '';

            $helper = Mage::app()->getHelper('qquoteadv');
            $quoteId = $this->getCustomerSession()->getQuoteadvId();

            Mage::dispatchEvent('ophirah_qquoteadv_quoteRequest_before', [$quoteId]);

            if ($quoteId && $this->getRequest()->isPost()) {
                $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
                $customerData = $this->getRequest()->getPost('customer');

                //check assign customer mode
                $assignCustomer = false;
                $assignCustomerData = $this->getRequest()->getPost('assigncustomer');
                if (isset($assignCustomerData) && !empty ($assignCustomerData)) {
                    $assignCustomer = true;
                }

                // Implode Multi select - Extra Options
                $postData = Mage::getModel('qquoteadv/extraoptions')->implodeOptions($customerData);
                if ($postData !== false) {
                    $this->getRequest()->setPost('customer', $postData);
                }

                if (!$this->getCustomerSession()->isLoggedIn() || $assignCustomer) {
                    try {
                        $email = $customerData['email'];
                        if (!Zend_Validate::is($email, 'EmailAddress')) {
                            Mage::throwException(Mage::helper('newsletter')->__('Please enter a valid email address.'));
                        }

                        if ($assignCustomer) {
                            $salesrepCustomerEmail = $customerData['email'];
                            $email = $assignCustomerData['email'];
                            $customerData = array_merge($customerData, $assignCustomerData);
                            if (!Zend_Validate::is($email, 'EmailAddress')) {
                                Mage::throwException(Mage::helper('newsletter')->__('Please enter a valid email address.'));
                            }
                        }

                        if ($helper->userEmailAlreadyExists($email) && !$assignCustomer) {
                            $this->_setIsEmailExists(true);
                            // If disable account check is no, show message
                            if (Mage::getStoreConfig('qquoteadv_quote_frontend/shoppingcart_quotelist/disable_exist_account_check') == 0) {
                                Mage::throwException($this->__('Email already exists'));
                            }
                        }
                    } catch (Exception $e) {
                        $this->getCoreSession()->addException($e, $e->getMessage());
                        Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                        $welcome = false;
                    }
                } else {
                    $customer = $this->getCustomerSession()->getCustomer();
                    if($customer){
                        $email = $customer->getEmail();
                    }
                }

                if (!$welcome && !$skip) {
                    $this->_redirect("*/*");
                } else {
                    Mage::helper('qquoteadv/address')->deletePreviousQuoteAddress($_quoteadv);
                    $data = $this->_initQuoteRequestSave($skip);
                    $addresses = $this->_createAddress($email, $assignCustomer);
                    $_quoteadv
                        ->addData($customerData)
                        ->setCreatedAt(now())
                        ->setStoreId(Mage::app()->getStore()->getStoreId())
                        ->setItemprice(Mage::getStoreConfig('qquoteadv_quote_configuration/proposal/itemprice'));

                    $status = Ophirah_Qquoteadv_Model_Status::STATUS_REQUEST;
                    if ($printOnly) {
                        $status = Ophirah_Qquoteadv_Model_Status::STATUS_PRINT_ONLY;
                    }

                    if (!$skip) {
                        $increment_id = Mage::getModel('qquoteadv/entity_increment_numeric')->getNextId();
                        $_quoteadv
                            ->setUpdatedAt(now())
                            ->setStatus($status)
                            ->setCurrency(Mage::app()->getStore()->getCurrentCurrencyCode())
                            ->setIncrementId($increment_id)
                            ->setCreateHash(Mage::helper('qquoteadv/license')->getCreateHash($increment_id));
                    }

                    //extra custom fields handling
                    if (!Mage::app()->getHelper('qquoteadv/licensechecks')->isAllowedCustomFields()) {
                        $_quoteadv = Mage::app()->getHelper('qquoteadv/licensechecks')->unsetExtraFields($_quoteadv);
                    } else {
                        //make sure radio button always have a value
                        /** @var Ophirah_Qquoteadv_Helper_Extrafield $extraFieldsHelper */
                        $extraFieldsHelper = Mage::helper('qquoteadv/extrafield');
                        $extraFieldsCount = Mage::helper('qquoteadv')->getNumberOfExtraFields();
                        $currentCustomerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
                        for ($fieldNumber = 1; $fieldNumber <= $extraFieldsCount; $fieldNumber++) {
                            if ($extraFieldsHelper->isExtraFieldSet($fieldNumber)
                                && $extraFieldsHelper->extraFieldIsVisible($fieldNumber, $currentCustomerGroupId)
                                && ($extraFieldsHelper->getFieldType($fieldNumber) == 'checkbox')) {
                                if (!isset($_quoteadv['extra_field_' . $fieldNumber])) {
                                    $_quoteadv['extra_field_' . $fieldNumber] = 'no';
                                }
                            }
                        }
                    }

                    try {
                        foreach($addresses as $type => $address){
                            Mage::helper('qquoteadv/address')->createQuoteAddress($_quoteadv, $address, $type);
                        }
                    } catch (Exception $e) {
                        Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                        $message = $this->__('Can not add customer address.');
                        $this->getCoreSession()->addError($message);
                    }

                    //File upload
                    $this->addFiles($_quoteadv);

                    //#1 insert product with requested quantities values: "one to many"
                    //# product_id / qty1 ;   product_id / qty2
                    if (isset($data['itemsData'])) {
                        foreach ($data['itemsData'] as $key => $item) {

                            // Support for Ayasoftware_SimpleProductPricing
                            if(Mage::helper('core')->isModuleEnabled("Ayasoftware_SimpleProductPricing")){
                                $storeId = Mage::app()->getStore()->getStoreId();
                                if (isset($data['paramsAddress']) && isset($data['paramsAddress']['store_id'])) {
                                    $storeId = $data['paramsAddress']['store_id'];
                                }

                                $_product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($item['product_id']);
                                $quoteItemProduct = null;
                                $quoteItemProductSimple = null;
                                $quoteItems = Mage::helper('qquoteadv')->getQuoteItem($_product, $data['productsData'][$key]['attribute']);
                                foreach ($quoteItems->getItemsCollection() as $quoteItem) {
                                    if ($quoteItem->getParentItem()) {
                                        if ($quoteItem->getParentItem()->getProduct()->isConfigurable ()) {
                                            $quoteItemProduct = $quoteItem->getParentItem();
                                            $quoteItemProductSimple = $quoteItem->getProduct();
                                        }
                                    }
                                }

                                //if isset, then a configurable product was requested
                                if(isset($quoteItemProduct) && isset($quoteItemProductSimple)){
                                    $quoteItemProduct = $quoteItemProduct->getProduct();
                                    $finalSimplePrice = $quoteItemProductSimple->getFinalPrice();
                                    //$quoteItemProduct->setCustomOptions($quoteItemProduct->getOptionsByCode());
                                    $simplePrice = Mage::helper('spp')->applyOptionsPrice($quoteItemProduct, $finalSimplePrice);
                                    if($simplePrice){
                                        $item['original_price'] = $simplePrice;
                                        $item['original_cur_price'] = $simplePrice;
                                        $item['owner_base_price'] = $simplePrice;
                                        $item['owner_cur_price'] = $simplePrice;
                                    }
                                }
                            }

                            $item = Mage::helper('qquoteadv')->updatePriceOnAddress($item, $addresses);
                            $resultIsQuoteable = Mage::helper('qquoteadv')->isQuoteable($item['product_id'], $item['request_qty']);
                            if ($resultIsQuoteable->getHasErrors()) {
                                $errors = $resultIsQuoteable->getErrors();
                                if (isset($errors[0])) {
                                    $this->getCoreSession()->addError($errors[0]);
                                    $url = $_SERVER['HTTP_REFERER'];
                                    $this->_redirectUrl($url);
                                    return null;
                                }
                            }
                            try {
                                $productData = null;
                                if(isset($data['productsData']) && isset($data['productsData'][$key])){
                                    $productKey = $data['productsData'][$key];
                                    if(isset($productKey)) {
                                        if($productKey['id'] == $item['quoteadv_product_id']){
                                            $productData = $data['productsData'][$key];
                                        }
                                    }
                                }

                                if(!$skip) {
                                    if (Mage::helper('qquoteadv')->checkAutomaticTierPrices()) {
                                        $quoteProduct = Mage::getModel('qquoteadv/qqadvproduct')->load($item['quoteadv_product_id']);
                                        $quoteProductId = $quoteProduct->getData('id');
                                        if (!isset($oldQuoteProductId) || ($oldQuoteProductId != $quoteProductId)) {
                                            $oldQuoteProductId = $quoteProductId;
                                            Mage::getModel('qquoteadv/requestitem')->getResourceCollection()->saveByProductTierQty($quoteProduct);
                                        }
                                    }
                                }

                                Mage::getModel('qquoteadv/requestitem')->addItem($item, $productData);
                                //$requestitem = Mage::getModel('qquoteadv/requestitem')->addData($item);
                                //$requestitem->save();

                            } catch (Exception $e) {
                                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                                $message = $this->__('Can not add one of the items to quote request.');
                                $this->getCoreSession()->addError($message);
                            }
                        }
                    }
                    //#2 need update data with client's notes for exists temporary product
                    try {
                        Mage::getModel('qquoteadv/qqadvproduct')->updateQuoteProduct($data['productsData']);
                    } catch (Exception $e) {
                        $message = $this->__('Can not add client note request to the product.');
                        Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                        $this->getCoreSession()->addError($message);
                    }

                    Mage::helper('qquoteadv/logging')->sentAnonymousData('request', 'f', $quoteId);

                    //#3b cancel edit quote
                    $oldQuoteadvId = Mage::getSingleton('customer/session')->getOldQuoteadvId();

                    if (isset($oldQuoteadvId) && !empty($oldQuoteadvId)) {
                        $this->_cancelQuote(Mage::getSingleton('customer/session')->getOldQuoteadvId());
                    }

                    $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
                    $baseCurrency = Mage::app()->getStore()->getBaseCurrencyCode();
                    if ($currencyCode != $baseCurrency) {
                        $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrency, $currencyCode);
                        $rate = $rates[$currencyCode];
                    } else {
                        $rate = 1;
                    }

                    /** @var Ophirah_Qquoteadv_Model_Qqadvcustomer $_quoteadv */
                    $_quoteadv->setCurrency($currencyCode);
                    $_quoteadv->setBaseToQuoteRate($rate);
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_before_quoterequestaction', ['quote' => $_quoteadv]);

                    /** @var Ophirah_Qquoteadv_Helper_Data $helper */
                    $helper = Mage::app()->getHelper('qquoteadv');

                    if (!$skip) {
                        //#Assigned to user
                        $helper->assignQuote($_quoteadv, $this->getRequest()->getPost('user_id'));

                        // Set Expiry Date Proposal
                        if ($printOnly) {
                            $_quoteadv->setNoExpiry(1);
                            $_quoteadv->setNoReminder(1);
                        } else {
                            $_quoteadv->setExpiry($helper->getExpiryDate());
                            $_quoteadv->setNoExpiry(0);
                        }
                    }

                    //disable sales_quote_item_qty_set_after observer
                    Mage::register('QtyObserver', 'disable');

                    try {
                        // set quote address
                        // Could be skipped now address is set
                        // during the 'getAddress' method
                        // Address object can contain more information
                        // then the quote object address
                        $_quoteadv->getAddress(); //sets address data on the quote
                        $_quoteadv->updateAddress();
                        $_quoteadv->collectTotals();
                        $_quoteadv->save();

                        // Enable sales_quote_item_qty_set_after observer
                        Mage::unregister('QtyObserver');
                    } catch (Exception $e) {
                        Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                        $this->getCoreSession()->addError($e->getMessage());
                        $this->_redirectReferer(Mage::getUrl('*/*'));
                    }

                    if (!$skip){
                        /** @var Ophirah_Qquoteadv_Model_Email_Autoproposal $autoProposal */
                        $autoProposal = Mage::getModel('qquoteadv/email_autoproposal');
                        if ($autoProposal->isConfigAllowed() && Mage::helper('qquoteadv/licensechecks')->isAllowAutoProposal($_quoteadv)) {
                            $shippingType = Mage::getStoreConfig('qquoteadv_quote_configuration/proposal/auto_proposal_shipping_type');
                            if (empty($shippingType) && isset($customerData['shipping_method'])) {
                                $shippingType = $customerData['shipping_method'];
                            } else {
                                $shippingPrice = Mage::getStoreConfig('qquoteadv_quote_configuration/proposal/auto_proposal_shipping_price');
                            }
                        } else {
                            if (isset($customerData['shipping_method'])) {
                                $shippingType = $customerData['shipping_method'];
                            } else {
                                $shippingType = null;
                            }
                        }

                        //add shipping if it is available
                        if (isset($shippingType) && !empty($shippingType) && !$_quoteadv->getAddress()->hasShipping()) {
                            $_quoteadv->getAddress()->removeAllShippingRates();
                            $_quoteadv->setShippingType($shippingType);

                            if (isset($shippingPrice) && !empty($shippingPrice) && ($shippingType == 'I' || $shippingType == 'O')) {
                                $_quoteadv->setShippingPrice($shippingPrice);
                                $_quoteadv->setShippingBasePrice($shippingPrice);
                            }

                            try {
                                if (!$_quoteadv->setShippingMethod()) {
                                    $_quoteadv->unsetShippingMethod();
                                } else {
                                    $_quoteadv->getShippingAddress()->requestShippingRates(); //generate shipping prices
                                    $_quoteadv->collectTotals();
                                }

                                $_quoteadv->save();
                            } catch (Exception $e) {
                                Mage::log($e->getMessage());
                                Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                                $this->getCoreSession()->addError($e->getMessage());
                                $this->_redirectReferer(Mage::getUrl('*/*'));
                            }
                        }

                        $autoProposal
                            ->setQuote($_quoteadv)
                            ->sendEmail();
                        $_quoteadv = $autoProposal->setQuoteStatus($_quoteadv);

                        try {
                            Mage::dispatchEvent('qquoteadv_qqadvcustomer_beforesave_final', ['quote' => $_quoteadv]);
                            $_quoteadv->save();
                            Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final', ['quote' => $_quoteadv]);
                            Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final_success', ['quote' => $_quoteadv]);
                            Mage::dispatchEvent('qquoteadv_qqadvcustomer_after_quoterequestaction', ['quote' => $_quoteadv]);
                        } catch (Exception $e) {
                            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                            Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final_error', ['quote' => $_quoteadv]);
                            $this->getCoreSession()->addError($e->getMessage());
                            $this->_redirectReferer(Mage::getUrl('*/*'));
                        }

                        //#4 email with quote place result
                        if (!$printOnly) {
                            if (!Mage::helper('qquoteadv/licensechecks')->showFreeUserOptions()) {
                                if ($assignCustomer && isset($salesrepCustomerEmail)) {
                                    $this->sendEmail($addresses, $email, $salesrepCustomerEmail);
                                } else {
                                    $this->sendEmail($addresses, $email);
                                }
                            } else {
                                $this->sendFreeUserEmail($_quoteadv);
                            }
                        }

                        if (empty($message)) {
                            $newsletter_enabled = Mage::getStoreConfig('qquoteadv_quote_form_builder/options/newsletter_subscribe');
                            if ($newsletter_enabled && $this->getRequest()->getPost('newsletter')) {
                                $session = Mage::getSingleton('core/session');
                                $newsletter = $this->getRequest()->getPost('newsletter');
                                $email = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId)->getData('email');
                                $customerId = $addresses[Ophirah_Qquoteadv_Helper_Address::ADDRESS_TYPE_BILLING]->getCustomerId();

                                if ($newsletter == "on") {
                                    try {
                                        $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                                        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                                        $subscriber->setCustomerId($customerId);
                                        $subscriber->save();

                                        if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                                            $session->addSuccess(Mage::helper('newsletter')->__('Confirmation request has been sent.'));
                                        } else {
                                            $session->addSuccess(Mage::helper('newsletter')->__('Thank you for your subscription.'));
                                        }
                                    } catch (Mage_Core_Exception $e) {
                                        $session->addException($e, Mage::helper('newsletter')->__('There was a problem with the subscription: %s', $e->getMessage()));
                                    } catch (Exception $e) {
                                        $session->addException($e, Mage::helper('newsletter')->__('There was a problem with the subscription.'));
                                        Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                                    }
                                }
                            }

                            $this->_redirect('*/*/success/');
                            return null;
                        }
                    }

                    if ($skip) {
                        /** @var Ophirah_Qquoteadv_Model_Qqadvcustomer */
                        return $_quoteadv;
                    }
                }
            } else {
                $this->_redirectReferer(Mage::getUrl('*/*'));
            }
        } catch (Exception $e) {
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            $this->getCoreSession()->addError($e->getMessage());
            $this->_redirectReferer(Mage::getUrl('*/*'));
        }

        Mage::dispatchEvent('ophirah_qquoteadv_quoteRequest_after', [$quoteId]);
        return null;
    }


    /**
     * Process quote as printed only.
     */
    public function quotePrintOnlyAction()
    {
        $this->quoteRequestAction(false, true);
    }

    /**
     * Get Estimated Shipping rates from
     * quote request data
     */
    public function quoteShippingEstimateAction()
    {
        $_quoteadv = $this->quoteRequestAction(true);
        Mage::dispatchEvent('ophirah_qquoteadv_quoteShippingEstimate_before', [$_quoteadv]);

        // clear session data
        Mage::getSingleton('customer/session')->setData('quoteRatesList', null);
        if ($_quoteadv instanceof Ophirah_Qquoteadv_Model_Qqadvcustomer) {
            $_quoteadv->getAddress()->clearRates();

            $ratesList = Mage::getModel('qquoteadv/quoteshippingrate')->buildOptions($_quoteadv);
            //remove quoteadv shiprate
            foreach ($ratesList as $key => $rate){
                if($rate['value'] == "qquoteshiprate_qquoteshiprate"){
                    unset($ratesList[$key]);
                }
            }

            $addresses = Mage::helper('qquoteadv/address')->buildQuoteAdresses($_quoteadv);

            // CheckCountryID
            $shipping = new Varien_Object();
            if (isset($addresses['shippingAddress'])) {
                $shipping = $addresses['shippingAddress'];
            }

            if ($ratesList && $shipping->getData('country_id')) {
                // Add customer information to session
                $customer = new Varien_Object();
                $customer->setData('email', $_quoteadv->getData('email'));
                $customer->setData('firstname', $_quoteadv->getData('firstname'));
                $customer->setData('lastname', $_quoteadv->getData('lastname'));
                $customer->setData('client_request', $_quoteadv->getData('client_request'));
                // Add filled out addresses to session
                $addresses = Mage::helper('qquoteadv/address')->buildQuoteAdresses($_quoteadv);
                Mage::getSingleton('customer/session')->setData('quoteCustomer', $customer);
                Mage::getSingleton('customer/session')->setData('quoteRatesList', $ratesList);
                Mage::getSingleton('customer/session')->setData('quoteAddresses', $addresses);
                Mage::getSingleton('customer/session')->setData('quoteRateRequest', true);
                // return to frontend
                $this->_redirect("*/*");
                return;
            }
        }

        //return false;
        $notice = Mage::helper('sales')->__('No shipping information available');
        Mage::getSingleton('core/session')->addNotice($notice);
        // return to frontend
        $this->_redirect("*/*");
    }

    /**
     * Show success message
     */
    public function successAction()
    {
        Mage::helper('qquoteadv')->deleteNotAllowedProductsInQuoteFromSession();
        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        if(isset($quoteId)) {
            $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
            if ($quote->getData('status') == Ophirah_Qquoteadv_Model_Status::STATUS_PRINT_ONLY) {
                $print = true;
            }
            Mage::dispatchEvent('ophirah_qquoteadv_success_before', [$quote]);

            $this->getCustomerSession()->setQuoteadvId(null);
            $this->loadLayout();
            $block = $this->getLayout()->getBlock('qquote.success');

            $block->setData('quote', $quote);
            $this->renderLayout();

            Mage::dispatchEvent('ophirah_qquoteadv_success_after', [$quote]);
        } else {
            $this->_redirect("*/*/index");
        }
    }

    /**
     * downloadQuotePdfAction
     */
    public function downloadQuotePdfAction()
    {
        $quoteId = $this->getRequest()->getParam('quoteID');
        if ($quoteId != null) {
            $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
            $pdf = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($quote);
            $file = $pdf->render();
            $realQuoteadvId = $quote->getIncrementId() ? $quote->getIncrementId() : $quote->getId();
            $fileName = Mage::helper('qquoteadv')->__('Price_proposal_%s', $realQuoteadvId . '.pdf');
            return $this->_prepareDownloadResponse($fileName, $file);
        } else {
            Mage::throwException($this->__('Quote id param not set'));
        }
    }

    /**
     * Show Quote success message
     */
    public function quotesuccessAction()
    {
        Mage::helper('qquoteadv')->deleteNotAllowedProductsInQuoteFromSession();
        $quoteId = $this->getCustomerSession()->getQuoteadvId();

        //if the quote is not available on the customer session fall back to the core session
        if ($quoteId == null) {
            $quoteId = $this->getCoreSession()->getProposalQuoteId();
        }

        //if the quote is not available in session, get it from the customer
        $quote = null;
        $customerId = $this->getCustomerSession()->getId();
        if ($quoteId == null && !empty($customerId)) {
            $quote = Mage::getModel('qquoteadv/qqadvcustomer')->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('status', Mage::getModel('qquoteadv/status')->getStatusConfirmed())
                ->addFieldToFilter('is_quote',  ['eq' => 1])
                ->setOrder('updated_at','DESC')
                ->getFirstItem();
        } else {
            $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
        }

        Mage::dispatchEvent('ophirah_qquoteadv_quotesuccess_before', [$quote]);

        $this->getCustomerSession()->setQuoteadvId(null);
        $this->loadLayout();
        $block = $this->getLayout()->getBlock('qquote.quotesuccess');

        $block->setData('quote', $quote);
        $this->renderLayout();

        Mage::dispatchEvent('ophirah_qquoteadv_quotesuccess_after', [$quote]);
    }


    /**
     * Send email to the requester informing about the quote
     *
     * @param $addresses
     * @param $email
     * @param null $bccEmail
     */
    public function sendEmail($addresses, $email, $bccEmail = null)
    {
        $billingAddress = $addresses['billing'];
        $customerId = $billingAddress->getCustomerId();

        //Create an array of variables to assign to template
        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        //$customer_id = $customerId; //$params['customer_id']; //$this->getCustomerSession()->getId();

        /* @var Ophirah_Qquoteadv_Model_Qqadvcustomer $_quoteadv */
        $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);

        //Vars into email templates
        $vars = [
            'quote' => Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId),
            'customer' => Mage::getModel('customer/customer')->load($customerId),
            'quoteId' => $quoteId,
            'store' => Mage::app()->getStore($_quoteadv->getStoreId())
        ];

        $recipientEmail = $email;
        $recipientName = $vars['customer']->getName();

        /**
         * $templateId can be set to numeric or string type value.
         * You can use Id of transactional emails (found in
         * "System->Trasactional Emails"). But better practice is
         * to create a config for this and use xml path to fetch
         * email template info (whatever from file/db).
         */
        $template = Mage::helper('qquoteadv/email')->getEmailTemplateModel($_quoteadv->getStoreId());

        $disabledEmail = Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;
        $quoteadv_param = Mage::getStoreConfig('qquoteadv_quote_emails/templates/request', $_quoteadv->getStoreId());
        if ($quoteadv_param != $disabledEmail){

            if ($quoteadv_param) {
                $templateId = $quoteadv_param;
            } else {
                $templateId = self::XML_PATH_QQUOTEADV_REQUEST_EMAIL_TEMPLATE;
            }

            if (is_numeric($templateId)) {
                $template->load($templateId);
            } else {
                $template->loadDefault($templateId);
            }

            $sender = $_quoteadv->getEmailSenderInfo();
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

            //add bcc email
            if (isset($bccEmail) && !empty($bccEmail)) {
                $template->addBcc($bccEmail);
            }

            $params = [
                "name" => $recipientName,
                "email" => $recipientEmail
            ];

            /**
             * Opens the qquoteadv_request.html, throws in the variable array
             * and returns the 'parsed' content that you can use as body of email
             */
            //$template->getProcessedTemplate($vars);

            /*
             * getProcessedTemplate is called inside send()
             */
            $template->setData('c2qParams', $params);
            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_before', ['template' => $template]);
            $res = $template->send($recipientEmail, $recipientName, $vars);
            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_after', ['template' => $template, 'result' => $res]);

            if (empty($res)) {
                $message = $this->__("Qquote request email was't sent to admin for quote #%s", $quoteId);
                Mage::log('Message: ' .$message, null, 'c2q.log', true);
            }
        }
    }

    /**
     * Generate a random password
     *
     * @param int $length
     * @return string           // Random password
     */
    protected function _generatePassword($length = 8)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }

    /**
     * Add customer account with random password
     *
     * @param $email
     * @param Mage_Customer_Model_Address $billingAddress
     * @param bool $assignCustomer
     * @return false|Mage_Core_Model_Abstract
     * @throws Exception
     */
    protected function _createCustomerAccount($email, Mage_Customer_Model_Address $billingAddress, $assignCustomer = false)
    {
        //#customer account and address
        if (isset($email) && is_string($email)) {
            //#create new account and autologin
            if ((!$this->getCustomerSession()->isLoggedIn() || $assignCustomer) && !$this->_isEmailExists()) {
                $is_subscribed = 0;

                //# NEW USER REGISTRATION
                $customerModel = Mage::getModel('customer/customer');
                $customerModel->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);

                //#create new user
                if (!$customerModel->getId()) {
                    $password = $this->_generatePassword(7);

                    $customer = Mage::getModel('qquoteadv/customer_customer');
                    $customer
                        ->setPrefix($billingAddress->getPrefix())
                        ->setFirstname($billingAddress->getFirstname())
                        ->setMiddlename($billingAddress->getMiddlename())
                        ->setLastname($billingAddress->getLastname())
                        ->setSuffix($billingAddress->getSuffix())
                        ->setGender($billingAddress->getGender())
                        ->setTaxvat($billingAddress->getVatId())
                        ->setEmail($email)
                        ->setPassword($password)
                        ->setPasswordHash(md5($password))
                        ->setWebsiteId(Mage::app()->getStore()->getWebsiteId());

                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_before_newCustomer', ['customer' => $customer]);
                    try {
                        $customer->save();
                    } catch (Exception $e) {
                        Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                        return $this->__("Unable to to save customer. Email: %s", $email);
                    }
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_after_newCustomer', ['customer' => $customer]);
                    $customerId = $customer->getId();

                    try {
                        if ($customer->isConfirmationRequired()) {
                            $this->getCustomerSession()->setNotConfirmedId($customer->getId());
                            $customer->sendNewQuoteAccountEmail('confirmation', $this->getCustomerSession()->getBeforeAuthUrl(), Mage::app()->getStore()->getId());
                        } else {
                            //login the customer (if not in assign customer mode)
                            if (!$assignCustomer) {
                                try {
                                    $loginState = $this->getCustomerSession()->login($email, $password);
                                    if (!$loginState) {
                                        Mage::log('Notice: Could not login customer with email: ' . $email, null, 'c2q.log', true);
                                    }
                                } catch (Exception $e) {
                                    Mage::log('Exception: Could not login customer with email: ' . $email, null, 'c2q_exception.log', true);
                                    Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                                }
                            }

                            $customer->sendNewQuoteAccountEmail('registered_qquoteadv', '', Mage::app()->getStore()->getId());
                        }
                    } catch (Exception $e) {
                        Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                    }
                } else {
                    $customerId = $customerModel->getId();
                    $customer = Mage::getModel('qquoteadv/customer_customer')->load($customerId);
                }
            } else {
                if (!$assignCustomer) {
                    $customer = $this->getCustomerSession()->getCustomer();
                    $customerId = $customer->getId();
                    $customer = Mage::getModel('qquoteadv/customer_customer')->load($customerId);
                }
            }

            //EMAIL IS REGESTERED BUT CUSTOMER IS STILL NOT LOGGED IN
            if ((!isset($customerId) || empty($customerId)) && $this->_isEmailExists()) {
                $email = trim($email);
                $customer = Mage::getModel('qquoteadv/customer_customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);
                $customerId = $customer->getId();
            }

            if (empty($customerId)) {
                throw new Exception('Customer id does not exist. Cannot place quote request');
            }

            if (!isset($customer)) {
                throw new Exception('Customer does not exist. Cannot place quote request');
            } else {
                return $customer;
            }
        }

        throw new Exception('Customer does not exist. Cannot place quote request');
    }

    /**
     * Searching user by email.
     */
    public function useJsEmailAction()
    {
        if ($this->getCustomerSession()->isLoggedIn()) {
            return null;
        }

        return $this->useJsEmailForceAction();
    }

    /**
     * Searching user by email even when user is logged in
     */
    public function useJsEmailForceAction()
    {
        $customer = Mage::getModel('customer/customer');

        // try to confirm by email
        $email = $this->getRequest()->getPost('email');
        Mage::dispatchEvent('ophirah_qquoteadv_useJsEmail_before', [$email]);
        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                return;
            } else {
                $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);
                if (!$customer->getId()) {
                    echo 'notexists';
                } else {
                    echo 'exists';
                }
            }
        }

        Mage::dispatchEvent('ophirah_qquoteadv_useJsEmail_after', [$email]);
        return null;
    }

    /**
     * Function that switches a quote to an order
     *
     * @param array $postData
     * @return bool
     */
    protected function _switch2Order($postData = [])
    {
        //1 quoteid
        //2 get all products by quote
        //3 move them to the shopping cart
        $errorsQuote = [];

        // if quote_id is not set then insert into qquote_customer table and get quote_id
        if ($this->getCustomerSession()->getQuoteadvId() == null) {
            $qcustomer = ['created_at' => now(),
                'updated_at' => now()
            ];

            // save data to qquote_customer table and getting inserted row id
            $qId = Mage::getModel('qquoteadv/qqadvcustomer')->addQuote($qcustomer)->getQuoteId();
            // setting inserted row id of qquote_customer table into session
            $this->getCustomerSession()->setQuoteadvId($qId);
        }

        $products = Mage::getModel('qquoteadv/qqadvproduct')->getQuoteProduct($this->getCustomerSession()->getQuoteadvId());

        foreach ($products as $key => $product) {
            $moved = false;
            $item = Mage::getModel('catalog/product')->load($product->getProductId());
            $requestParams = new Varien_Object(unserialize($product->getAttribute()));

            //# updating attribute product quantity with the product quantity
            //!!! overload old qty value from field 'attribute' quote table to real request qty
            $requestParams->setQty($product->getQty());

            //This way Magento uses the file data in the buyRequest
            /** @var Ophirah_Qquoteadv_Helper_Data $helper */
            $requestParams = Mage::helper('qquoteadv')->prepareFileOptions($requestParams);

            try {
                //#n2o if( $item->getData('allowed_to_ordermode')) {
                // add item to cart
                if (!$item->isSalable()) {
                    throw new Exception('is not salable');
                } else {
                    Mage::getModel('checkout/cart')->addProduct($item, $requestParams)->save();
                    $moved = true;
                }
                //}
            } catch (Exception $e) {
                $errorsQuote[] = $this->__("Item %s wasn't moved to Shopping cart", $item->getName());
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            }

            try {
                // remove item to quote mode
                if($moved){
                    Mage::getModel('qquoteadv/qqadvproduct')->deleteQuote($key);
                }
            } catch (Exception $e) {
                $errorsQuote[] = $this->__("Item %s wasn't removed from Quote mode", $item->getName());
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            }
        }

        if (count($postData) > 0 && $postData['product']) {
            $productId = $postData['product'];
            $item = Mage::getModel('catalog/product')->load($productId);
            $param = $postData;

            $qty = (empty($postData['qty'])) ? 1 : $postData['qty'];

            $param['qty'] = $qty;

            try {
                Mage::getModel('checkout/cart')->addProduct($item, $param)->save();
            } catch (Exception $e) {
                $errorsQuote[] = $e->getMessage();
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            }
        }

        foreach ($errorsQuote as $err) {
            $this->getCoreSession()->addError($err);
        }

        $this->getCoreSession()->setCartWasUpdated(true);

        if (count($errorsQuote)) return false;

        return true;
    }

    /**
     * Action that moves a quote to the checkout
     */
    public function switch2OrderAction()
    {
        if ($this->isActiveConfMode()) {
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        Mage::dispatchEvent('ophirah_qquoteadv_switch2order_before', ['session' => Mage::getSingleton('checkout/session')]);
        $result = $this->_switch2Order();
        if ($result) {
            $this->getCoreSession()->addSuccess($this->__('Item(s) were moved from Quote to Order mode successfully.'));
            Mage::dispatchEvent('ophirah_qquoteadv_switch2order_after', ['session' => Mage::getSingleton('checkout/session'), 'result' => $result]);
            Mage::dispatchEvent('ophirah_qquoteadv_switch2order_after_success', ['session' => Mage::getSingleton('checkout/session'), 'result' => $result]);
        } else {
            Mage::dispatchEvent('ophirah_qquoteadv_switch2order_after_error', ['session' => Mage::getSingleton('checkout/session'), 'result' => $result]);
        }

        $this->_redirect('checkout/cart/');
    }

    /**
     * Function that switches a cart to a quote
     *
     * @return bool
     */
    protected function _swith2Quote()
    {
        $result = false;
        $cartHelper = Mage::helper('checkout/cart');
        $cart = $cartHelper->getItemsCount();

        if ($cart > 0) {
            $session = Mage::getSingleton('checkout/session');
            foreach ($session->getQuote()->getAllVisibleItems() as $item) {
                $productId = $item->getProductId();
                $product = Mage::getModel('catalog/product')->load($productId);
                $superAttribute = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                $optionalAttrib = $this->_prepareOptionalAttributes($superAttribute);

                $isAllow = $this->getAllowedToQuotemode($product, $optionalAttrib);
                if ($isAllow) {
                    $params = [
                        'cartid'          => $item->getId(),
                        'product'         => $item->getProductId(),
                        'qty'             => $item->getQty(),
                        'attributeEncode' => ''
                    ];

                    /**
                     * Add support for OrganicInternet_SimpleConfigurableProducts
                     */
                    if (Mage::helper('core')->isModuleEnabled('OrganicInternet_SimpleConfigurableProducts')) {
                        $cpId = $this->getCpid($optionalAttrib);
                        if ($cpId) {
                            $params['cpid'] = $cpId;
                        }
                    }

                    $this->addDataAction($params, $optionalAttrib);
                }
            }

            $result = true;
        }

        return $result;
    }

    /**
     * Adds multiple products to the quote based on the post data.
     * Returns a bool if the action is successful.
     * @return bool
     */
    protected function _multiAddToQuote()
    {
        $result = false;
        $products = [];
        $qtys = [];
        $postData = $this->getRequest()->getPost();

        if ($postData) {
            if (isset($postData['product']) && !empty($postData['product'])) {
                $product = Mage::getModel('catalog/product')->load($postData['product']);
                $product->addCustomOption('info_buyRequest', serialize($this->getFilteredParams()));
                $products[] = $product;
                if ($postData['qty']) {
                    $qtys[0] = $postData['qty'];
                } else {
                    $qtys[0] = 1;
                }
            }

            if (array_key_exists('related_products', $postData)) {
                $relatedProductsId = $postData['related_products'];
                foreach ($relatedProductsId as $relatedProductId) {
                    $products[] = Mage::getModel('catalog/product')->load($relatedProductId);
                }

                if (array_key_exists('related_products_qty', $postData)) {
                    $relatedProductsQtys = $postData['related_products_qty'];

                    foreach ($relatedProductsQtys as $relatedProductQty) {
                        $qtys[] = $relatedProductQty;
                    }
                } else {
                    foreach ($relatedProductsId as $relatedProductId) {
                        $qtys[] = 1;
                    }
                }
            }

            if (count($products) > 0) {
                foreach ($products as $key => $product) {
                    $isAllow = $this->getAllowedToQuotemode($product);
                    if ($isAllow) {
                        Mage::dispatchEvent('ophirah_qquoteadv_multiAddToQuote_before', ['product' => $product]);
                        //$qty = $this->_checkQty($product->getStockItem()->getMinSaleQty(), $key, $qtys[$key]);
                        $qty = $this->_checkQty($product->getStockItem()->getMinSaleQty(), $qtys[$key]);
                        $this->_addSingleProduct($product, $qty);
                        Mage::dispatchEvent('ophirah_qquoteadv_multiAddToQuote_after', ['product' => $product]);
                    }
                }
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Adds multiple products to the quote based on the post data.
     * Returns a bool if the action is successful.
     * @return bool
     */
    protected function _multiAddToQuoteApo()
    {
        $result = false;
        $postData = $this->getRequest()->getPost();
        $optionData = Mage::helper('qquoteadv/compatibility_apo')->getOptionsFromPostData($postData);
        $qtys = Mage::helper('qquoteadv/compatibility_apo')->getProductQtyFromPostData($postData);

        if (count($optionData['products']) > 0) {
            foreach ($optionData['products'] as $key => $product) {

                Mage::dispatchEvent('ophirah_qquoteadv_multiAddToQuoteApo_before', ['product' => $product]);

                if (is_array($product)) {
                    foreach ($product as $subProductKey => $subProduct) {
                        if ($this->getAllowedToQuotemode($subProduct)) {
                            $qty = $this->_checkQty($subProduct->getStockItem()->getMinSaleQty(), $qtys[$key][$subProductKey]);
                            $this->_addSingleProduct($subProduct, $qty);
                        }
                    }
                } else {
                    if ($this->getAllowedToQuotemode($product)) {
                        $qty = $this->_checkQty($product->getStockItem()->getMinSaleQty(), $qtys[$key]);
                        $this->_addSingleProduct($product, $qty);
                    }
                }
                Mage::dispatchEvent('ophirah_qquoteadv_multiAddToQuoteApo_after', ['product' => $product]);
            }
        }

        if(count($optionData['options']) > 0){
            $product = Mage::getModel('catalog/product')->load($postData['product']);
            $qty = $this->_checkQty($product->getStockItem()->getMinSaleQty(), $postData['qty']);

            $value['product'] = $product->getId();
            $value['qty'] = $qty;
            $value['options'] = $optionData['options'];
            $product->addCustomOption('info_buyRequest', serialize($value));
            $this->_addSingleProduct($product, $qty);
        }

        $result = true;
        return $result;
    }

    /**
     * Function to handle adding of a single product
     *
     * @param $product
     * @param $qty
     */
    protected function _addSingleProduct($product, $qty){
        $superAttribute = $product->getTypeInstance(true)->getOrderOptions($product);
        $optionalAttrib = $this->_prepareOptionalAttributes($superAttribute);

        $params = [
            'cartid' => "",
            'product' => $product->getId(),
            'qty' => $qty,
            'attributeEncode' => ''
        ];

        if (empty($superAttribute)) {
            $optionalAttrib = serialize($params);
        }
        $this->addDataAction($params, $optionalAttrib);
    }

    /**
     * Function that checks the quantity and make sure it isn't zero
     *
     * @param $productMinSaleQty
     * @param $qtyPostData
     * @return int|string
     */
    protected function _checkQty($productMinSaleQty, $qtyPostData){
        $qty = $productMinSaleQty;
        if(is_null($qty)){
            $qty = 1;
        }

        if (isset($qtyPostData) && is_numeric($qtyPostData) && ($qtyPostData > 0)) {
            $qty = $qtyPostData;
            if (isset($productMinSaleQty) && ($qtyPostData < $productMinSaleQty)) {
                $qty = $productMinSaleQty;
            }
        }
        return $qty;
    }

    /**
     * Function that handle the copy cart to quote action
     *
     * case: called from shopping cart page
     */
    public function switch2QquoteAction()
    {
        if ($this->isActiveConfMode()) {
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        Mage::dispatchEvent('ophirah_qquoteadv_switch2quote_before', ['session' => Mage::getSingleton('checkout/session')]);
        $result = $this->_swith2Quote();
        if ($result) {
            $this->getCoreSession()->addSuccess($this->__('Item(s) were moved to Quote mode successfully.'));
            Mage::dispatchEvent('ophirah_qquoteadv_switch2quote_after', ['session' => Mage::getSingleton('checkout/session'), 'result' => $result]);
            Mage::dispatchEvent('ophirah_qquoteadv_switch2quote_after_success', ['session' => Mage::getSingleton('checkout/session'), 'result' => $result]);
        } else {
            Mage::dispatchEvent('ophirah_qquoteadv_switch2quote_after_error', ['session' => Mage::getSingleton('checkout/session'), 'result' => $result]);
        }

        $this->_redirect('qquoteadv/index/');
    }

    /**
     * Function that checks if active confirm mode is set and sets a notice
     *
     * @return bool
     */
    protected function isActiveConfMode()
    {
        if (Mage::helper('qquoteadv')->isActiveConfirmMode()) {
            $link = Mage::getUrl('qquoteadv/view/outqqconfirmmode');
            $message = Mage::helper('qquoteadv')->__("You are in a quote confirmation mode, <a href='%s'>log out</a>.", $link);
            $this->getCoreSession()->addNotice($message);
            return true;
        }

        return false;
    }

    /**
     * Getter for _isEmailExists
     *
     * @return bool
     */
    protected function _isEmailExists()
    {
        return $this->_isEmailExists;
    }

    /**
     * Setter for _isEmailExists
     *
     * @param $param
     */
    protected function _setIsEmailExists($param)
    {
        $this->_isEmailExists = $param;
    }

    /**
     * Functions that adds files to Quote from the Quote Request Page
     *
     * @param Ophirah_Qquoteadv_Model_Qqadvcustomer $_quoteadv
     */
    protected function addFiles($_quoteadv)
    {
        $fileCount = count($_FILES);
        for ($i = 0; $i < $fileCount; $i++) {
            $fileTitleToAdd = $this->getRequest()->getParam('file_title_' . $i);
            $_quoteadv->uploadFile($fileTitleToAdd, 'file_path_' . $i);
        }
    }

    /**
     * Function that generates the next quote id
     *
     * @param $customerId
     * @return $this
     */
    protected function _setNextQuoteadvId($customerId)
    {

        //#init next quote id
        $date = now();
        $qcustomer = [
            'created_at' => $date,
            'updated_at' => $date,
            'customer_id' => $customerId
        ];

        $nextQuoteId = Mage::getModel('qquoteadv/qqadvcustomer')->addQuote($qcustomer)->getQuoteId();

        //# set next quote id into session
        $this->getCustomerSession()->setQuoteId($nextQuoteId);
        return $this;
    }

    /**
     * Action to reconfigure quote item
     */
    public function configureAction()
    {
        // Extract item and product to configure
        $id = (int)$this->getRequest()->getParam('id');
        $quoteItem = null;
        $productId = null;
        $productOptions = null;

        Mage::dispatchEvent('ophirah_qquoteadv_configure_before', [$id]);

        if ($id) {
            $quoteid = $this->getCustomerSession()->getQuoteadvId();
            $data = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
                ->addFieldToFilter("quote_id", $quoteid)
                ->addFieldToFilter("id", $id);
            foreach ($data as $row) {
                $quoteItem = $row;
                break;
            }
        }

        if (!$quoteItem) {
            $this->getCoreSession()->addError($this->__('Quote item is not found.'));
            $this->_redirect('qquoteadv/index');
            return;
        } else {
            $productId = $quoteItem['product_id'];
            $productOptions = unserialize($quoteItem['attribute']);
        }

        try {
            $params = new Varien_Object();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            if (count($productOptions)) {
                $params->setBuyRequest(new Varien_Object($productOptions));
            }

            Mage::helper('catalog/product_view')->prepareAndRender($productId, $this, $params);
        } catch (Exception $e) {
            $this->getCoreSession()->addError(Mage::helper('checkout')->__('Cannot configure product.'));
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            $this->_redirect('*/*/');
            Mage::dispatchEvent('ophirah_qquoteadv_configure_after_error', [$id]);
            return;
        }

        Mage::dispatchEvent('ophirah_qquoteadv_configure_after', [$id]);
    }

    /**
     * Ajax update product qty for a quote item
     */
    public function ajaxUpdateItemQtyAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $params = $this->getRequest()->getParams();

        Mage::dispatchEvent('ophirah_qquoteadv_updateItemQty_before', [$params]);
        $result = [];

        try {
            $quoteItem = Mage::getModel('qquoteadv/qqadvproduct')->load($id);
            if (!$quoteItem) {
                Mage::throwException($this->__('Quote item is not found.'));
            }
            Mage::dispatchEvent('qquoteadv_qqadvproduct_beforesave_updateItemQty', ['qqadvproduct' => $quoteItem]);

            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    ['locale' => Mage::app()->getLocale()->getLocaleCode()]
                );
                $params['qty'] = $filter->filter($params['qty']);
                if ($params['qty'] > 0) {
                    $pid = $quoteItem->getData('product_id');
                    $checkQty = Mage::helper('qquoteadv')->isQuoteable($pid, $params['qty']);
                    if ($checkQty->getHasErrors()) {
                        $errors = $checkQty->getErrors();
                        if (isset($errors[0])) {
                            Mage::throwException($this->__('Quote item is not quotable.'));
                        }
                    }

                    $quoteItem->setQty($params['qty']);
                }
            }

            $quoteItem->save();

            $this->loadLayout();
            $result['content'] = $this->getLayout()->getBlock('miniquote_content')->toHtml();
            $result['qty'] = Mage::helper('qquoteadv')->getTotalQty();

            if (!$quoteItem->getHasError()) {
                $result['message'] = $this->__('Item was updated successfully.');
            } else {
                $result['notice'] = $quoteItem->getMessage();
            }
            $result['success'] = 1;

            Mage::dispatchEvent('qquoteadv_qqadvproduct_aftersave_updateItemQty', ['qqadvproduct' => $quoteItem]);
        } catch (Exception $e) {
            $result['success'] = 0;
            $result['error'] = $this->__('Cannot update the item.');
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
        }

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        Mage::dispatchEvent('ophirah_qquoteadv_updateItemQty_after', [$params]);
    }

    /**
     * Update product configuration for a quote item
     */
    public function updateItemOptionsAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $params = $this->getRequest()->getParams();

        Mage::dispatchEvent('ophirah_qquoteadv_updateItemOptions_before', [$params]);

        if (!isset($params['options'])) {
            $params['options'] = [];
        }
        try {
            $quoteItem = Mage::getModel('qquoteadv/qqadvproduct')->load($id);
            if (!$quoteItem) {
                Mage::throwException($this->__('Quote item is not found.'));
            }
            Mage::dispatchEvent('qquoteadv_qqadvproduct_beforesave_updateItemOptions', ['qqadvproduct' => $quoteItem]);

            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    ['locale' => Mage::app()->getLocale()->getLocaleCode()]
                );
                $params['qty'] = $filter->filter($params['qty']);
                if ($params['qty'] > 0) {

                    $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
                    $pid = $modelProduct->load($id)->getData('product_id');
                    $checkQty = Mage::helper('qquoteadv')->isQuoteable($pid, $params['qty']);
                    if ($checkQty->getHasErrors()) {
                        $errors = $checkQty->getErrors();
                        if (isset($errors[0])) {
                            $this->getCoreSession()->addError($errors[0]);
                            $this->_redirectUrl($this->_getRefererUrl());
                            return;
                        }
                    }

                    $quoteItem->setQty($params['qty']);
                }
            }

            $params = new Varien_Object($params);
            $product = Mage::getModel('catalog/product')->load($params['product']);
            $product->getTypeInstance(true)->prepareForCartAdvanced($params, $product);

            $attribute = $params->toArray();
            $oldAttribute = unserialize($quoteItem->getAttribute());
            if (isset($oldAttribute['options'])) {
                if (isset($attribute['options'])) {
                    $attribute['options'] += $oldAttribute['options'];
                } else {
                    $attribute['options'] = $oldAttribute['options'];
                }
            }
            $quoteItem->setAttribute(serialize($attribute));

            //#options
            if (isset($params['options']) && count($params['options']) > 0) {
                $quoteItem->setHasOptions(1);

                $options = serialize($params['options']);
                $quoteItem->setOptions($options);
            }
            $quoteItem->save();
            Mage::dispatchEvent('qquoteadv_qqadvproduct_aftersave_updateItemOptions', ['qqadvproduct' => $quoteItem]);

        } catch (Mage_Core_Exception $e) {
            if ($this->getCoreSession()->getUseNotice(true)) {
                $this->getCoreSession()->addNotice($e->getMessage());
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->getCoreSession()->addError($message);
                }
            }
            $this->_redirect('*/*/configure', ['id' => $id]);


        } catch (Exception $e) {
            $this->getCoreSession()->addException($e, Mage::helper('checkout')->__('Cannot update the item.'));
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            $this->_redirect('*/*/configure', ['id' => $id]);
        }
        $this->_redirect('*/*');
        Mage::dispatchEvent('ophirah_qquoteadv_updateItemOptions_after', [$params]);
    }

    /**
     * Send email to client to informing about the quote proposition
     * @return bool
     * @internal param array $params $params['email'], $params['name']* $params['email'], $params['name']
     */
    public function sendAutoProposalEmail()
    {
        $this->quoteId = (int)$this->getRequest()->getParam('id');
        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        /* @var Ophirah_Qquoteadv_Model_Qqadvcustomer $_quoteadv */
        $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
        if (!Mage::helper('qquoteadv/license')->validLicense('auto_proposal', $_quoteadv->getCreateHashArray())) {
            return false;
        }

        //Create an array of variables to assign to template
        $vars = [];
        $vars['quote'] = $_quoteadv;
        $vars['store'] = Mage::app()->getStore($_quoteadv->getStoreId());
        $vars['customer'] = Mage::getModel('customer/customer')->load($_quoteadv->getCustomerId());

        $template = Mage::helper('qquoteadv/email')->getEmailTemplateModel($_quoteadv->getStoreId());

        $quoteadv_param = Mage::getStoreConfig('qquoteadv_quote_emails/templates/proposal', $_quoteadv->getStoreId());
        if ($quoteadv_param) {
            $templateId = $quoteadv_param;
        } else {
            $templateId = self::XML_PATH_QQUOTEADV_REQUEST_PROPOSAL_EMAIL_TEMPLATE;
        }

        if (is_numeric($templateId)) {
            $template->load($templateId);
        } else {
            $template->loadDefault($templateId);
        }

        $vars['attach_pdf'] = $vars['attach_doc'] = false;

        //Create pdf to attach to email
        if (Mage::getStoreConfig('qquoteadv_quote_emails/attachments/pdf', $_quoteadv->getStoreId())) {
            $pdf = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($_quoteadv);
            $realQuoteadvId = $_quoteadv->getIncrementId() ? $_quoteadv->getIncrementId() : $_quoteadv->getId();
            try {
                $file = $pdf->render();
                $name = Mage::helper('qquoteadv')->__('Price_proposal_%s', $realQuoteadvId);
                $template->getMail()->createAttachment($file, 'application/pdf', Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64, $name . '.pdf');
                $vars['attach_pdf'] = true;

            } catch (Exception $e) {
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            }

        }

        $doc = Mage::getStoreConfig('qquoteadv_quote_emails/attachments/doc', $_quoteadv->getStoreId());
        if ($doc) {
            $pathDoc = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'quoteadv' . DS . $doc;
            try {
                $file = file_get_contents($pathDoc);

                $info = pathinfo($pathDoc);
                //$extension = $info['extension'];
                $basename = $info['basename'];
                $template->getMail()->createAttachment($file, '', Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64, $basename);
                $vars['attach_doc'] = true;
            } catch (Exception $e) {
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            }
        }

        $remark = Mage::getStoreConfig('qquoteadv_quote_configuration/proposal/qquoteadv_remark', $_quoteadv->getStoreId());
        if ($remark) {
            $vars['remark'] = $remark;
        }

        $vars['link'] = Mage::getUrl("qquoteadv/view/view/", ['id' => $quoteId]);

        $sender = $_quoteadv->getEmailSenderInfo();
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
        $template->setData('c2qParams', ['email' => $_quoteadv->getEmail(), 'name' => $vars['customer']->getName()]);
        Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_before', ['template' => $template]);
        $res = $template->send($_quoteadv->getEmail(), $vars['customer']->getName(), $vars);
        Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_after', ['template' => $template, 'result' => $res]);

        return $res;
    }

    /**
     * Function that based on a hash (from the email) finds a quote and views it
     */
    public function goToQuoteAction()
    {
        $quoteId = (int)$this->getRequest()->getParam('id');
        $hash = $this->getRequest()->getParam('hash');
        $my = $this->getRequest()->getParam('my');
        Mage::dispatchEvent('ophirah_qquoteadv_goToQuote_before', [$quoteId]);

        $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
        $origUrlHash = $quote->getUrlHash();

        $autoConfirm = '';
        $statusAllowed = Mage::getModel('qquoteadv/status')->statusAllowed();
        if (Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/auto_confirm', $quote->getStoreId()) == 1 && in_array($quote->getStatus(), $statusAllowed)) {
            $autoConfirm = $this->getRequest()->getParam('autoConfirm');
        }

        $configured = Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/link_auto_login', $quote->getStoreId());
        $allowed = Mage::helper('qquoteadv/license')->validLicense('email-auto-login', $quote->getCreateHashArray());

        if ($configured && $allowed && $hash === $origUrlHash) {
            $customer = Mage::getModel('customer/customer')->load($quote->getCustomerId());
            $customerSession = Mage::getSingleton('customer/session');

            $customerSession->setCustomerAsLoggedIn($customer);
            $customerSession->renewSession();

            if (!$my && !$autoConfirm) {
                $this->_redirect('*/view/view/', ['id' => $quoteId]);
            } elseif ($my == "quote" && $autoConfirm) {
                $this->_redirect('*/view/confirm/', ['id' => $quoteId]);
            } elseif ($my == "quote") {
                $this->_redirect('*/view/view/', ['id' => $quoteId]);
            } else {
                $this->_redirect('*/view/history/');
            }

        } else {
            ($my == "quotes") ? $this->_redirectUrl(Mage::getUrl('*/view/history/')) : $this->_redirectUrl(Mage::getUrl('*/view/view/', ['id' => $quoteId]));
        }

        Mage::dispatchEvent('ophirah_qquoteadv_goToQuote_after', [$quoteId]);
    }

    /**
     * Action that clears the quote
     */
    public function clearQuoteAction()
    {
        Mage::dispatchEvent('ophirah_qquoteadv_clearQuote_before', []);
        $this->_clearQuote();
        $this->_redirectReferer();
        Mage::dispatchEvent('ophirah_qquoteadv_clearQuote_after', []);
    }

    /**
     * Function that clears the quote
     * @return $this
     */
    private function _clearQuote()
    {
        $products = Mage::helper('qquoteadv')->getQuote();
        foreach ($products as $product) {
            $product->deleteQuote($product->getId());
        }

        return $this;
    }

    /**
     * @param $superAttribute
     * @return string
     */
    protected function _prepareOptionalAttributes($superAttribute)
    {
        $optionalAttrib = '';
        if (isset($superAttribute['info_buyRequest'])) {
            if (isset($superAttribute['info_buyRequest']['uenc'])) {
                unset($superAttribute['info_buyRequest']['uenc']);
            }
            $optionalAttrib = serialize($superAttribute['info_buyRequest']);
            return $optionalAttrib;
        }
        return $optionalAttrib;
    }

    /**
     * @param $oldQuoteId
     */
    protected function _cancelQuote($oldQuoteId)
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        $quoteData = Mage::getModel('qquoteadv/qqadvcustomer')->load($oldQuoteId);
        $quote = Mage::getModel('qquoteadv/qqadvcustomer')->getCollection()
            ->addFieldToFilter('quote_id', $oldQuoteId)
            ->addFieldToFilter('customer_id', $customerId);

        if (count($quote) > 0){
            if($quoteData->getQuoteId() && $quote) {

                $cancelParams = [
                    'updated_at' => now(),
                    'status' => Ophirah_Qquoteadv_Model_Status::STATUS_CANCELED
                ];
                $emailParams = [
                    'email' => $quoteData->getData('email'),
                    'firstname' => $quoteData->getData('firstname'),
                    'lastname' => $quoteData->getData('lastname'),
                    'quoteId' => $quoteData->getQuoteId(),
                    'customerId' => $customerId
                ];

                Mage::getModel('qquoteadv/qqadvcustomer')->updateQuote($quoteData->getQuoteId(), $cancelParams);
                if (!Mage::helper('qquoteadv/licensechecks')->showFreeUserOptions()) {
                    $this->sendEmailCancellation($emailParams);
                }
                Mage::dispatchEvent('quote_proposal_controller_cancel', ['quote' => $quote]);
            }
        }

    }

    /**
     * Send email to administrator informing about the quote cancellation
     * @param array $params customer address
     */
    public function sendEmailCancellation($params)
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
        $disabledEmail = Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;
        $quoteadv_param = Mage::getStoreConfig('qquoteadv_quote_emails/templates/proposal_cancel', $_quoteadv->getStoreId());
        if ($quoteadv_param != $disabledEmail){
            if ($quoteadv_param) {
                $templateId = $quoteadv_param;
            } else {
                $templateId = self::XML_PATH_QQUOTEADV_REQUEST_CANCEL_EMAIL_TEMPLATE;
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
                $message = "Qquote cancel email was't sent to admin for quote #".$quoteId;
                Mage::log('Message: ' .$message, null, 'c2q.log', true);
            }
        }
    }

    /**
     * Action that saves the quote basked
     */
    public function saveAction(){
        try {
            $quoteId = $this->getCustomerSession()->getQuoteadvId();
            Mage::dispatchEvent('ophirah_qquoteadv_saveQuoteCart_before', [$quoteId]);

            if ($quoteId && $this->getRequest()->isPost()) {

                // Check Customer and customer addressdata
                $paramsCustomer = $this->getRequest()->getPost('customer', []);
                $paramsProduct = $this->getRequest()->getPost('quote_request', []);
                $paramsQuoteId = $this->getRequest()->getPost('quote_id');


                $paramsProductFormatted = [];
                foreach ($paramsProduct as $quoteadvProductId => $array) {
                    $qty = $array['qty'];
                    foreach ($array['qty'] as $value) {
                        $paramsProductArray = [];
                        $paramsProductArray['id'] = $quoteadvProductId;

                        //only set client_request when available
                        if(isset($array['client_request'])) {
                            $paramsProductArray['client_request'] = $array['client_request'];
                        }

                        $paramsProductArray['product_id'] = $array['product_id'];

                        //only set qty when it is allowed
                        $checkQty = Mage::helper('qquoteadv')->isQuoteable($array['product_id'], $value);
                        if ($checkQty->getHasErrors()) {
                            $errors = $checkQty->getErrors();
                            if (isset($errors[0])) {
                                //$this->getCoreSession()->addError($errors[0]);
                                return;
                            }
                        } else {
                            $paramsProductArray['qty'] = $value;
                        }

                        $paramsProductFormatted[] = $paramsProductArray;
                        break; //tier price saving not supported
                    }

                    Mage::getModel('qquoteadv/requestitem')
                        ->getResourceCollection()
                        ->addPostDataQty(
                            $paramsProduct,
                            $quoteadvProductId,
                            $paramsQuoteId,
                            $qty
                        );
                }

                //need update data with client's notes for exists temporary product
                try {
                    Mage::getModel('qquoteadv/qqadvproduct')->updateQuoteProduct($paramsProductFormatted);
                } catch (Exception $e) {
                    //$message = $this->__('Can not add client note request to the product.');
                    //$this->getCoreSession()->addError($message);
                    Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                }

                //add remark on the quote
                try {
                    if(isset($paramsCustomer['client_request'])){
                        $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
                        $_quoteadv->setClientRequest($paramsCustomer['client_request']);
                        $_quoteadv->save();
                    }
                } catch (Exception $e) {
                    //$message = $this->__('Can not add client request to the quote.');
                    //$this->getCoreSession()->addError($message);
                    Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                }

            }
        } catch (Exception $e) {
            //$message = $this->__('Can not save client note request to the product.');
            //$this->getCoreSession()->addError($message);
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
        }

        if (!$this->getRequest()->isXmlHttpRequest()) {
            //only redirect when request is non ajax
            $this->_redirect('*/*');
        }

        Mage::dispatchEvent('ophirah_qquoteadv_saveQuoteCart_after', []);
    }

    /**
     * Send email to the store owner when new quote is requested with free trial
     * @param $_quoteadv
     */
    public function sendFreeUserEmail($_quoteadv)
    {
        $adminEmail = Mage::getModel('admin/user')->load($_quoteadv->getUserId())->getEmail();
        if (!empty($adminEmail)) {
            $template = Mage::helper('qquoteadv/email')->getEmailTemplateModel($_quoteadv->getStoreId());
            $incrementId = $_quoteadv->getIncrementId();
            $storeGroupName = $_quoteadv->getStoreGroupName();
            $salesRepName = $_quoteadv->getSalesRepresentative()->getName();
            $this->getRequest()->setParam('quote_id', 0);
            //Vars into email templates
            $vars = [
                'incrementId' => $incrementId,
                'storeGroupName' => $storeGroupName,
                'salesRepresentative' => $salesRepName
            ];

            $templateId = self::XML_PATH_QQUOTEADV_REQUEST_EMAIL_TEMPLATE_FREE;

            if (is_numeric($templateId)) {
                $template->load($templateId);
            } else {
                $template->loadDefault($templateId);
            }

            $sender = $_quoteadv->getEmailSenderInfo();
            $template->setSenderName($sender['name']);
            $template->setSenderEmail($sender['email']);

            //getProcessedTemplate is called inside  $template->send
            //$template->getProcessedTemplate($vars);

            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_before', ['template' => $template]);
            $res = $template->send($adminEmail, null, $vars);
            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_after', ['template' => $template, 'result' => $res]);
            if (empty($res)) {
                $message = Mage::helper('Ophirah_RequestNotification')->__("Qquote request email notification was't sent quote #%s", $_quoteadv->getId());
                Mage::log('Exception: RequestNotification: ' . $message, null, 'c2q_exception.log', true);
            }
        }
    }

    /**
     * Retrieves the address from the post data.
     * The array contains billing and shipping address in the type Mage_Sales_Model_Quote_Address
     * @return array
     */
    protected function _getFormAddresses()
    {
        /** @var Ophirah_Qquoteadv_Helper_Legacy $legacyHelper */
        $legacyHelper = Mage::helper('qquoteadv/legacy');

        /** @var \Ophirah_Qquoteadv_Helper_Address $helperAddress */
        $helperAddress = Mage::helper('qquoteadv/address');

        $customerData = $this->getRequest()->getPost('customer', []);

        //check assign customer mode
        $assignCustomerData = $this->getRequest()->getPost('assigncustomer', []);
        if (isset($assignCustomerData) && !empty ($assignCustomerData)) {
            $customerData = array_merge($customerData, $assignCustomerData);
        }

        $addresses = [];
        $addressTypes = [
            Ophirah_Qquoteadv_Helper_Address::ADDRESS_TYPE_BILLING,
            Ophirah_Qquoteadv_Helper_Address::ADDRESS_TYPE_SHIPPING
        ];

        //check for firstname
        $firstName = '';
        if (isset($customerData['firstname'])) {
            $firstName = $customerData['firstname'];
        }

        //check for lastname
        $lastName = '';
        if (isset($customerData['lastname'])) {
            $lastName = $customerData['lastname'];
        }

        //check for guest mode
        if (empty($firstName) && empty($lastName)) {
            if (isset($customerData['firstname'])) {
                unset($customerData['firstname']);
            }

            if (isset($customerData['lastname'])) {
                unset($customerData['lastname']);
            }
        } else {
            $guestAddressPostData = $helperAddress->prepareGuestAddress($customerData);
        }

        //check for legacy template mode
        if ($legacyHelper->hasDeprecatedPostData($customerData)) {
            $oldAddressPostData = $legacyHelper->prepareOutdatedPostData($customerData);
        }

        foreach ($addressTypes as $addressType) {
            if (isset($oldAddressPostData) && is_array($oldAddressPostData) && count($oldAddressPostData) == 2) {
                $addressPostData = $oldAddressPostData[$addressType];
            } elseif (isset($guestAddressPostData)) {
                $addressPostData = $guestAddressPostData[$addressType];
            } else {
                $addressPostData = $this->getRequest()->getPost($addressType);
                $addressPostData = array_merge($addressPostData, $customerData);
            }

            if (array_key_exists('mage_address_id', $addressPostData) && $addressPostData['mage_address_id'] != 'new') {
                $address = Mage::getModel('customer/address')->load($addressPostData['mage_address_id']);
                $address->setSaveAddressBook(false);
            } else {
                $address = $helperAddress->toCustomerAddressObject($addressPostData);
                $address->setSaveAddressBook(true);
            }

            $addresses[$addressType] = $address->setAddressType($addressType);
            $addresses[$addressType] = $helperAddress->combineStreetByAddress($addresses[$addressType]);
            $addresses[$addressType] = $helperAddress->setRegion($addresses[$addressType]);
        }

        return $addresses;
    }

    /**
     * Processes through customer data
     * -- Create account if needed
     * -- Add (default) address if needed
     *
     * @param $email
     * @param bool $assignCustomer
     * @return mixed
     */
    protected function _createAddress($email, $assignCustomer = false)
    {
        /** @var Ophirah_Qquoteadv_Helper_Address $helperAddress */
        $helperAddress = Mage::helper('qquoteadv/address');
        $addressConfig = new Varien_Object();
        $addressConfig->addData($this->getRequest()->getPost('address', []));

        $addressProcessMode = $helperAddress->getAddressProcessMode($addressConfig);
        $addresses = $helperAddress->processAddress($addressProcessMode, $this->_getFormAddresses());

        $customer = $this->_createCustomerAccount(
            $email,
            $addresses[Ophirah_Qquoteadv_Helper_Address::ADDRESS_TYPE_BILLING],
            $assignCustomer
        );
        $customerId = $customer->getId();

        if ($helperAddress->validateAddress($addresses)) {
            foreach ($addresses as $address) {
                if ($address->getSaveAddressBook()) {
                    if (!$helperAddress->addressExists($customer, $address)) {
                        $address->setCustomerId($customer->getId());
                        $address = $helperAddress->checkDefaultAddress($addressProcessMode, $address, $customer);
                        $helperAddress->createCustomerAddress($address);
                    }
                }
            }
        } elseif (isset($customerId)) {
            foreach ($addresses as $address) {
                $address->setCustomerId($customer->getId());
            }
        }

        return $addresses;
    }

    /**
     * Function to get the Cpid when SCP is enabled
     *
     * OrganicInternet_SimpleConfigurableProducts
     *
     * @param $paramsObj
     * @return bool
     */
    public function getCpid($paramsObj)
    {
        if (Mage::helper('core')->isModuleEnabled('OrganicInternet_SimpleConfigurableProducts')) {
            if ($paramsObj instanceof Varien_Object) {
                return $paramsObj->getCpid();
            }

            if (is_array($paramsObj) && isset($paramsObj['cpid'])) {
                return $paramsObj['cpid'];
            }

            if (is_string($paramsObj)) {
                try {
                    $paramsObj = unserialize($paramsObj);
                    if (is_array($paramsObj) && isset($paramsObj['cpid'])) {
                        return $paramsObj['cpid'];
                    }
                } catch (Exception $exception) {
                    //do nothing
                }
            }
        }

        return false;
    }

    /**
     * Get allowed to quote mode with extra options
     *
     * @param $product
     * @param null $params
     * @return bool
     */
    public function getAllowedToQuotemode($product, $params = null)
    {
        if (is_null($params)) {
            return $product->getAllowedToQuotemode();
        }

        return ($product->getAllowedToQuotemode() || $this->getCpid($params));
    }
}
