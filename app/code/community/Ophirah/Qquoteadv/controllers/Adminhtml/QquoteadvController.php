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
 * Class Ophirah_Qquoteadv_Adminhtml_QquoteadvController
 */
class Ophirah_Qquoteadv_Adminhtml_QquoteadvController extends Mage_Adminhtml_Controller_Action
{
    const XML_PATH_QQUOTEADV_REQUEST_PROPOSAL_EMAIL_TEMPLATE = 'qquoteadv_quote_emails/templates/proposal';
    const XML_PATH_QQUOTEADV_REQUEST_EMAIL_TEMPLATE = 'qquoteadv_quote_emails/templates/request';
    const FLAG_IS_URLS_CHECKED = 'check_url_settings';
    const FLAG_NO_DISPATCH = 'no-dispatch';

    /**
     * @var bool
     */
    protected $_saveFlag = false;

    /**
     * @var array
     */
    protected $_postData = [];

    /**
     * @var
     */
    protected $_quoteadv;

    /**
     * Init function for the customer grid
     * @param string $idFieldName
     * @return $this
     */
    protected function _initCustomer($idFieldName = 'id')
    {
        $this->_title(Mage::helper('adminhtml')->__('Customers'))->_title(Mage::helper('adminhtml')->__('Manage Customers'));

        $customerId = (int)$this->getRequest()->getParam($idFieldName);
        $customer = Mage::getModel('customer/customer');

        if ($customerId) {
            $customer->load($customerId);
        }

        Mage::register('current_customer', $customer);
        return $this;
    }


    /**
     * Customer quotes grid
     *
     */
    public function quotesAction()
    {
        Mage::dispatchEvent('ophirah_qquoteadv_admin_quotes_before', []);
        $this->_initCustomer();
        $this->loadLayout();
        $this->renderLayout();
        Mage::dispatchEvent('ophirah_qquoteadv_admin_quotes_after', []);
    }


    /**
     * CUSTOMER GRID
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/qquoteadv')
            ->_addBreadcrumb($this->__('Items Manager'), $this->__('Item Manager'));

        return $this;
    }

    /**
     * Index action (renders the quote grid)
     */
    public function indexAction()
    {
        if(!Mage::helper('core')->isModuleEnabled('Ophirah_Crmaddon') ||$this->{'l'}->{$this->{'b'}}()){
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('qquoteadv')->__('The module "Ophirah_Crmaddon" is disabled, pleas enable it. (app/etc/modules/Ophirah_Crmaddon.xml)'));
        }

        Mage::dispatchEvent('ophirah_qquoteadv_admin_index_before', []);
        $this->_initAction();
        // check if need to display the upgrade popup
        if (Mage::helper('qquoteadv/licensechecks')->showFreeUserOptions()) {
            $msgUpgrade = $this->getMsgToUpgrade(false);
            $block = $this->getLayout()->createBlock('core/text', 'example-block');
            $this->_addContent($block->setText($msgUpgrade));
        }
        $this->renderLayout();

        Mage::dispatchEvent('ophirah_qquoteadv_admin_index_after', []);
    }

    /**
     * Edit action of a given quote
     *
     * @return null
     */
    public function editAction()
    {
        if (Mage::helper('qquoteadv/licensechecks')->showFreeUserOptions()) {
            $this->_redirect('*/*/');
            return null;
        }

        if(!Mage::helper('core')->isModuleOutputEnabled('Ophirah_Crmaddon')){
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('qquoteadv')->__('The module output of "Ophirah_Crmaddon" is disabled, pleas enable it. (System>Configuration>Advanced>Advanced>Disable Modules Output>Ophirah_Crmaddon)'));
        }

        $id = $this->getRequest()->getParam('id');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_edit_before', [$id]);
        $model = $this->getQuotationQuote($id);
        $this->_title($this->__('Sales'))->_title($this->__('Quotations'));

        if (isset($id) && ($model->getId() || $id == 0)) {
            if (Mage::helper('qquoteadv/licensechecks')->isAllowedLimitSalesRepQuote()) {
                //check for permission to view/edit this based on salesrepview acl
                $user = Mage::getSingleton('admin/session');
                $userId = $user->getUser()->getUserId();
                $resourceLookup = "admin/sales/qquoteadv/salesrepview";
                $resourceId = $user->getData('acl')->get($resourceLookup)->getResourceId();
                if (!$user->isAllowed($resourceId)) {
                    if (($userId != $model->getUserId()) && $model->getUserId() != '0') {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('qquoteadv')->__('You don\'t have permission to view or edit this Quote'));
                        $this->_redirect('*/*/');
                        return null;
                    }
                }
            }

            //add title
            $this->_title(sprintf("#%s", $model->getIncrementId()));

            //add quoteadv id to the session for the quoteadv shipping option
            Mage::getSingleton('core/session')->proposal_quote_id = $id;

            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            Mage::register('qquote_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('qquoteadv/items');

            // Set currenCurrency from quote
            Mage::helper('qquoteadv')->setCurrentCurrency($model->getCurrency());

            $head = $this->getLayout()->getBlock('head');
            $head->setCanLoadExtJs(true);

            $createHash = $model->getCreateHashArray();
            $access = $this->getAccessLevel();
            if (is_null($access) || $this->isTrialVersion($createHash) || !$this->checkQuoteLicense($model->getStoreId())) {
                Mage::register('createHash', $createHash);
                $msgUpgrade = $this->getMsgToUpgrade(false, $model->getStoreId());
                $this->_addContent($this->getLayout()->createBlock('core/text', 'example-block')->setText($msgUpgrade));
            }

//            $this->_addContent($this->getLayout()->createBlock('qquoteadv/adminhtml_qquoteadv_edit'))
//                ->_addLeft($this->getLayout()->createBlock('qquoteadv/adminhtml_qquoteadv_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError( Mage::helper('checkout')->__('Quote item does not exist.'));
            $this->_redirect('*/*/');
        }

        Mage::dispatchEvent('ophirah_qquoteadv_admin_edit_after', [$id]);
    }

    /**
     * Action for a new quote
     */
    public function newAction()
    {
        Mage::dispatchEvent('ophirah_qquoteadv_admin_new_before', []);

        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->_addContent($this->getLayout()->createBlock('qquoteadv/adminhtml_qquoteadv_edit'));
        $this->renderLayout();

        Mage::dispatchEvent('ophirah_qquoteadv_admin_new_after', []);
    }

    /**
     * Controller predispatch method
     *
     * @return Ophirah_Qquoteadv_Adminhtml_QquoteadvController
     */
    public function preDispatch()
    {
        $a = 'U6lUsFVQT0osTjUziU9JTc5PSVW35lLJAIqqVGqoJ0e4GSYZB+VEhgcZ+RhVFEQahZUmG';
        $a .= '4VlJboDsbGvrbomUHFJRmaxrl0OUItvYnqqlVVGak5BapGGSgZCMglqXmRuOUgPAA==';
        eval(gzinflate(base64_decode($a)));
        return parent::preDispatch();
    }

    /**
     * Function to send a proposal email
     *
     * @param $customerId
     * @param $realQuoteadvId
     * @param $quoteId
     */
    protected function _sendProposalEmail($customerId, $realQuoteadvId, $quoteId)
    {
        $_quoteadv = $this->getQuotationQuote($quoteId);
        if(Mage::getStoreConfig('system/smtp/disable', $_quoteadv->getStoreId()) == "1"){
            $errorMessage = "'System > Configuration > Advanced > System > Mail Sending Settings > Disable Email Communications' is set to 'Yes'";
            Mage::getSingleton('adminhtml/session')->addError($errorMessage);
        }

        try {
            $customer = Mage::getModel('customer/customer')->load($customerId);

            $res = $this->sendEmail(['email' => $customer->getEmail(), 'name' => $customer->getName()]);

            if (empty($res)) {
                $message = $this->__("Cart2Quote proposal email was not sent to the client for quote #%s", $realQuoteadvId);

                //log last error
                $lastError = error_get_last();
                if(is_array($lastError)){
                    if(isset($lastError['message'])){
                        Mage::log("Last error before sendProposalEmail, but could be unrelated: ".$lastError['message'], null, 'c2q_exception.log', true);
                    }
                }

                Mage::getSingleton('adminhtml/session')->addError($message);
            } elseif (is_string($res) && $res == Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL) {
                Mage::getSingleton('adminhtml/session')->addNotice($this->__('Sending proposal Email is disabled'));
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Email was sent to client'));
                Mage::helper('qquoteadv/logging')->sentAnonymousData('proposal', 'b', $quoteId);
            }
        } catch (Exception $e) {
            $message = $this->__("Cart2Quote proposal email was not sent to the client for quote #%s", $realQuoteadvId);
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            Mage::getSingleton('adminhtml/session')->addError($message);
            $this->_redirect('*/*/');
            return;
        }
    }

    /**
     * Edit Quote Action (for quotes have status above requested)
     *
     * @return \Ophirah_Qquoteadv_Adminhtml_QquoteadvController
     */
    public function editLockQuoteAction()
    {
        try {
            $quoteId = (int)$this->getRequest()->getParam('id');
            if ($quoteId) {
                Mage::dispatchEvent('ophirah_qquoteadv_admin_editLockQuote_before', [$quoteId]);
                $_quoteadv = $this->getQuotationQuote($quoteId);
                $status = $_quoteadv->getData('status');

                //check if status is above request and take decide to continue or return
                if (intval($status) >= 50){
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_beforecancel_afterEditLockQuote', ['quote' => $_quoteadv]);
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_beforecancel', ['quote' => $_quoteadv]);
                    //status is above request
                    //check if data is edited that require a new quote

                    $originalId = $_quoteadv->getOriginalIncrementId();
                    if (!$originalId) {
                        $originalId = $_quoteadv->getIncrementId();
                    }

                    //Copy quote
                    $cloneData = $_quoteadv->getData();
                    unset($cloneData['quote_id']);
                    unset($cloneData['relation_child_id']);
                    unset($cloneData['relation_child_real_id']);
                    $new_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->setData($cloneData)->save();
                    $new_quoteadv->setData("original_increment_id"  , $originalId);
                    $new_quoteadv->setData("status", 10); //proposal created, not send

                    //Add increment number, link to old increment
                    $new_quoteadv->setData("original_increment_id"  , $originalId);
                    $new_quoteadv->setData("relation_parent_id"     , $_quoteadv->getId());
                    $new_quoteadv->setData("relation_parent_real_id", $_quoteadv->getIncrementId());
                    $new_quoteadv->setData("edit_increment"         , $_quoteadv->getEditIncrement()+1);
                    $new_quoteadv->setData("increment_id"           , $originalId.'-'.($_quoteadv->getEditIncrement()+1));

                    //change creation date
                    $new_quoteadv->setData("created_at"             , now());

                    //get new shipping type
                    $oldShippingAddress = $_quoteadv->getShippingAddress();
                    if($oldShippingAddress){
                        $oldShippingAddressId = $oldShippingAddress->getId();
                    }

                    $new_quoteadv->updateAddress();
                    $shippingAddress = $new_quoteadv->getShippingAddress();
                    $shippingAddressId = $new_quoteadv->getShippingAddress()->getId();
                    $shippingCode = $new_quoteadv->getShippingCode();

                    if ($shippingAddress && $shippingAddressId && $shippingCode) {
                        $shippingAddress->requestShippingRates();
                        $shippingMethod = Mage::getModel('qquoteadv/quoteshippingrate')->getShippingMethodByCode($shippingAddressId, $shippingCode);
                        if (isset($oldShippingAddressId)) {
                            if ($shippingMethod) {
                                $oldShippingMethod = Mage::getModel('qquoteadv/quoteshippingrate')->getShippingMethodByCode($oldShippingAddressId, $shippingCode);
                                $shippingMethod->setPrice($oldShippingMethod->getPrice());
                                $shippingMethod->save();
                            } else {
                                $message = $this->__('The shipping method could not be transfered to this quote, please re-apply a shipping method.');
                                Mage::getSingleton('adminhtml/session')->addNotice($message);
                            }
                        }
                        if ($shippingMethod) {
                            $new_quoteadv->setShippingType($shippingMethod->getId());
                            $new_quoteadv->setShippingPrice($shippingMethod->getPrice());
                        }
                    }

                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_beforesafe_new', ['quote' => $new_quoteadv]);
                    $new_quoteadv->save();
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersafe_new', ['quote' => $new_quoteadv]);

                    //Link to new increment number.
                    $_quoteadv->setRelationChildId($new_quoteadv->getId());
                    $_quoteadv->setRelationChildRealId($new_quoteadv->getIncrementId());
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_beforesafe_final', ['quote' => $_quoteadv]);
                    $_quoteadv->save();
                    $this->_quoteadv = $_quoteadv;

                    Mage::helper('qquoteadv')->updateQuoteEditIncrements($new_quoteadv);
                    Mage::helper('qquoteadv')->duplicateQuoteProductsToNewQuote($_quoteadv, $new_quoteadv);
                    Mage::helper('crmaddon')->duplicateQuoteMessagesToNewQuote($_quoteadv, $new_quoteadv);

                    //cancels quote
                    $model = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
                    $model->setStatus(Ophirah_Qquoteadv_Model_Status::STATUS_CANCELED);  //STATUS_REJECTED
                    $model->save();
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersafe_final', ['quote' => $model]);

                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftercancel_afterEditLockQuote', ['quote' => $model]);
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftercancel', ['quote' => $model]);


                    //send stats
                    Mage::helper('qquoteadv/logging')->sentAnonymousData('cancel', 'b', $quoteId);
                    Mage::helper('qquoteadv/logging')->sentAnonymousData('request', 'b', $new_quoteadv->getId());

                    //Return to edit copied quote.
                    $urlReturn = '*/*/edit/id/' . $new_quoteadv->getId();
                    $this->_redirect($urlReturn);
                    Mage::dispatchEvent('ophirah_qquoteadv_admin_editLockQuote_after', [$quoteId]);
                }
            }
        } catch (Exception $e){
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);

            //throw new Exception('Something went wrong while duplicating this quote', 0, $e);
            $message = $this->__('Something went wrong while duplicating this quote');
            Mage::log('Exception: ' .$message, null, 'c2q_exception.log', true);
            Mage::getSingleton('adminhtml/session')->addError($message);

            //try to return to the quote edit page
            $quoteId = (int)$this->getRequest()->getParam('id');
            $urlReturn = '*/*/edit/id/' . $quoteId;
            $this->_redirect($urlReturn);
        }
    }

    /**
     * Save Quote Action
     *
     * @return \Ophirah_Qquoteadv_Adminhtml_QquoteadvController
     */
    public function saveAction()
    {
        if (!Mage::helper('qquoteadv/license')->validLicense('create-edit-admin', $this->getRequest()->getPost('createHash'))) {
            Mage::getSingleton('adminhtml/session')->addError($this->__("Please upgrade to Cart2Quote Starter or higher to use this feature"));
            $this->_redirectReferer();
            return null;
        }

        // Retrieve Post data
        $data = $this->getRequest()->getPost();

        //event
        Mage::dispatchEvent('ophirah_qquoteadv_admin_save_before', [$data]);

        //event specific event
        $event = $this->getRequest()->getParam('event');
        if ($event) {
            Mage::dispatchEvent('ophirah_qquoteadv_admin_save_before_' . $event, [$data]);
        }

        $pdfPrint = false;
        // If save is called from creating PDF
        if (isset($this->_flags['qquoteadv']['print'])) {
            if ($this->_flags['qquoteadv']['print'] === true) {
                $data = $this->_postData;
                $pdfPrint = true;
            }
        }

        if (isset($data)) {
            try {
                if (isset($data['product']) && is_array($data['product']) && count($data['product']) > 0) {
                    $quoteId = (int)$this->getRequest()->getParam('id');
                    if ($quoteId) {
                        /** @var \Ophirah_Qquoteadv_Model_Qqadvcustomer $_quoteadv */
                        $_quoteadv = $this->getQuotationQuote($quoteId);
                        Mage::dispatchEvent('qquoteadv_qqadvcustomer_before_saveaction', ['quote' => $_quoteadv]);
                        //event specific event
                        $event = $this->getRequest()->getParam('event');
                        if ($event) {
                            Mage::dispatchEvent('qquoteadv_qqadvcustomer_before_saveaction_' . $event, ['quote' => $_quoteadv]);
                        }

                        // Setting Extra Options
                        if (isset($data['extra_options'])) {
                            if (!is_array($data['extra_options'])) {
                                $data['extra_options'] = [$data['extra_options']];
                            }

                            foreach ($data['extra_options'] as $key => $option) {
                                if (is_array($option)) {
                                    $option = implode(',', $option);
                                }
                                $_quoteadv->setData($key, $option);
                            }
                        }

                        if(isset($data['store'])){
                            $_quoteadv->setStoreId($data['store']);
                        }

                        // Rate gets calculated from base=>quoterate
                        $rate = $_quoteadv->getBase2QuoteRate();

                        $errors = [];
                        foreach ($data['product'] as $id => $arr) {
                            $price = $arr['price'];
                            $qty = $arr['qty'];
                            $model = Mage::getModel('qquoteadv/requestitem')->load($id);
                            $productId = $model->getProductId();

                            $quoteProduct = Mage::getModel('qquoteadv/qqadvproduct')->getQuoteItemChildren((int)$productId, $model->getQuoteadvProductId());

                            // Creating ChildProducts array
                            // in case product has a product type Bundle
                            // All childproducts need to be checked
                            $checkQty = $qty;
                            $checkProductArray = [];
                            // Parent product gets added first
                            $checkProductArray[] = $quoteProduct;
                            if(isset($quoteProduct) && is_object($quoteProduct)){
                                if ($quoteProduct->getChildren()) {
                                    $checkProductArray = array_merge($checkProductArray, $quoteProduct->getChildren());
                                }
                            }

                            // Cycle through childproducts
                            foreach ($checkProductArray as $checkProduct) {
                                if ($checkProduct->getQuoteItemQty()) {
                                    $checkQty = $checkProduct->getQuoteItemQty();

                                    if(is_array($checkQty)){
                                        //bad way of getting the first value of the array
                                        foreach($checkQty as $qtyValue){
                                            $checkQty = $qtyValue;
                                            break;
                                        }
                                    }
                                }
                                //echo 'save action: ';
                                $check = Mage::helper('qquoteadv')->isQuoteable($checkProduct, $checkQty);
                            }

                            if (isset($check) && $check->getHasErrors()) {
                                $errors = $check->getErrors();
                                //#return back in case any error found
                                if ($pdfPrint === true) {
                                    return $errors;
                                } else {
                                    if (count($errors)) {
                                        $lastMessage = null;
                                        foreach ($errors as $message) {
                                            if ($message != $lastMessage) {
                                                $message .= $this->__('Quote could not be saved.');
                                                Mage::getSingleton('adminhtml/session')->addError('<br />'.$message);
                                            }
                                            $lastMessage = $message;
                                        }
                                    }
                                    if(isset($quoteId) && isset($data['redirect2neworder']) && $data['redirect2neworder'] == 1){
                                        //continue with error
                                        foreach ($errors as $message) {
                                            Mage::log('Message: ' .$message, null, 'c2q.log');
                                        }
                                    } else {
                                        return $this->_redirect('*/*/edit', ['id' => $quoteId]);
                                    }
                                }
                            }

                            try {
                                $model->setOwnerCurPrice($price);
                                $basePrice = $price / $rate;
                                $model->setOwnerBasePrice($basePrice);

                                $model->save();
                            } catch (Exception $e) {
                                $errors[] = $this->__("Item #%s was't updated", $id);
                                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                            }
                        }

                        if (is_array($data['requestedproduct']) && count($data['requestedproduct']) > 0) {

                            $errors = [];
                            $counter = 0;
                            foreach ($data['requestedproduct'] as $clientRequestId => $clientRequestData) {

                                //if($client_request = $arr['client_request']){
                                $client_request = $clientRequestData['client_request'];
                                $comment = trim(strip_tags($client_request));

                                try {
                                    $requestItemId = $data['q2o'][$counter];

                                    //use more solid way of getting the request item id on newer admin templates v6.2.3
                                    //this fixes the comments not always sticking to the request item on sorting
                                    foreach ($data['product'] as $requestItemIdProduct => $product) {
                                        if (in_array($requestItemIdProduct, $data['q2o'])
                                            && isset($product['requestedproduct'])
                                            && $product['requestedproduct'] == $clientRequestId
                                        ) {
                                            $requestItemId = $requestItemIdProduct;
                                        }
                                    }

                                    $qquoteadvItem = Mage::getModel('qquoteadv/requestitem')->load($requestItemId);
                                    $qquoteadvProduct = Mage::getModel('qquoteadv/qqadvproduct')->load($qquoteadvItem->getQuoteadvProductId());
                                    $qquoteadvProduct->setClientRequest($comment);

                                    // Update tier qty
                                    if ($data['product'][$requestItemId]['qty']) {
                                        $newQty = $data['product'][$requestItemId]['qty'];
                                        $attribute = unserialize($qquoteadvProduct->getAttribute());
                                        $attribute['qty'] = $newQty;
                                        $qquoteadvProduct->setAttribute(serialize($attribute));
                                        $qquoteadvProduct->setQty($newQty);
                                    }
                                    $qquoteadvProduct->save();
                                } catch (Exception $e) {
                                    if (isset($qquoteadvItem)) {
                                        $errors[] = $this->__("Item #%s was't updated", $qquoteadvItem->getProductId());
                                    }

                                    Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                                }

                                $counter++;
                            }
                        }

                        //File upload
                        $fileIncrementNumber = 0;
                        foreach($_FILES as $file) {
                            $fileTitleToAdd = $this->getRequest()->getParam('file_title_'.$fileIncrementNumber);
                            $_quoteadv->uploadFile($fileTitleToAdd, 'file_path_'.$fileIncrementNumber);
                            $fileIncrementNumber++;
                        }


                        //File removal
                        foreach($this->getRequest()->getParams() as $key => $imageTitle){
                            if(substr($key, 0, 12) == "removeImage_"){
                                $imageRemoveSuccessfully = $_quoteadv->removeFile($imageTitle);

                                if(!$imageRemoveSuccessfully){
                                    $this->_getSession()->addError($this->__('Unable to remove the file.'));
                                }
                            }
                        }

                        // Setting Status
                        if (isset($data['status'])) {
                            // Auto update to proposal sent
                            if ($this->getRequest()->getParam('back')) {
                                $oldStatus = $_quoteadv->getStatus();
                                $oldSubstatus = $_quoteadv->getSubstatus();
                                $_quoteadv->setStatus(Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL);
                                $_quoteadv->setSubstatus();
                                // Setting Proposal sent date and time
                                $_quoteadv->setProposalSent(now());
                            } elseif (!$this->getRequest()->getParam('hold')) {
                                // check for status and substatus
                                // @var Varien_Object
                                $status = Mage::getModel('qquoteadv/substatus')->getStatus($data['status']);
                                $substatus = Mage::getModel('qquoteadv/substatus')->getSubstatus($data['status']);
                                $_quoteadv->setStatus($status);
                                $_quoteadv->setSubstatus($substatus);
                            }
                        }

                        // Client Request
                        $client_request = $this->getRequest()->getParam('client_request');
                        if ($client_request) {
                            $comment = trim(strip_tags($client_request));
                            $_quoteadv->setClientRequest($comment);
                        } else {
                            $_quoteadv->setClientRequest();
                        }

                        // Internal Comment
                        $internal_comment = $this->getRequest()->getParam('internal_comment');
                        if ($internal_comment) {
                            $internalComment = trim(strip_tags($internal_comment));
                            $_quoteadv->setInternalComment($internalComment);
                        } else {
                            $_quoteadv->setInternalComment();
                        }

                        // Get date format
                        $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);

                        // Expiry date
                        $expiry = $this->getRequest()->getParam('expiry');
                        if ($expiry) {
                            $expiryFormatted = Mage::app()->getLocale()->date($expiry, $dateFormat, null, false);
                            $_quoteadv->setExpiry($expiryFormatted->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
                        }
                        $no_expiry = ($this->getRequest()->getParam('no_expiry') && $this->getRequest()->getParam('no_expiry') == "on") ? 1 : 0;
                        $_quoteadv->setNoExpiry($no_expiry);

                        // Reminder
                        $reminder = $this->getRequest()->getParam('reminder');
                        if ($reminder) {
                            $reminderFormatted = Mage::app()->getLocale()->date($reminder, $dateFormat, null, false);
                            $_quoteadv->setReminder($reminderFormatted->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
                        }
                        $no_reminder = ($this->getRequest()->getParam('no_reminder') && $this->getRequest()->getParam('no_reminder') == "on") ? 1 : 0;
                        $_quoteadv->setNoReminder($no_reminder);

                        // Follow Up
                        $followup = $this->getRequest()->getParam('followup');
                        if ($followup) {
                            $followupFormatted = Mage::app()->getLocale()->date($followup, $dateFormat, null, false);
                            $_quoteadv->setFollowup($followupFormatted->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
                        }
                        $no_followup = ($this->getRequest()->getParam('no_followup') && $this->getRequest()->getParam('no_followup') == "on") ? 1 : 0;
                        if ($no_followup == 1) {
                            $_quoteadv->setFollowup(); //clear date
                            $_quoteadv->setNoFollowup(0); //clear checked
                        }
                        // Show item price
                        if ($this->getRequest()->getParam('itemprice') == "on") {
                            $_quoteadv->setData('itemprice', 1);
                        } else {
                            $_quoteadv->setData('itemprice', 0);
                        }

                        // Alternative Checkout Page
                        if ($this->getRequest()->getParam('alt_checkout') == "on") {
                            $_quoteadv->setData('alt_checkout', 1);
                        } elseif (Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/checkout_alternative', $_quoteadv->getData('store_id')) > 0) {
                            $_quoteadv->setData('alt_checkout', 1);
                        } else {
                            $_quoteadv->setData('alt_checkout', 0);
                        }

                        // Salesrule
                        $salesrule = $this->getRequest()->getParam('salesrule');
                        if ($salesrule) {
                            $_quoteadv->setSalesrule($salesrule);
                            //collect totals is needed
                            if(!Mage::getStoreConfig('qquoteadv_advanced_settings/backend/calculate_quote_totals_on_load')){
                                //but only when it isn't done on page load
                                $_quoteadv->collectTotals();
                            }
                        } else {
                            if ($salesrule !== null) {
                                //salesrule is "0", remove salesrule
                                if ($_quoteadv->getSalesrule() != null) {
                                    $_quoteadv->setSalesrule(null);
                                    //collect totals is needed
                                    if (!Mage::getStoreConfig('qquoteadv_advanced_settings/backend/calculate_quote_totals_on_load')) {
                                        //but only when it isn't done on page load
                                        $_quoteadv->collectTotals();
                                    }
                                }
                            }
                        }

                        // Assign Salesrep
                        $assignedTo = $this->getRequest()->getParam('assigned_to');
                        if ($assignedTo) {
                            $saveas = Mage::getModel('admin/user')->load($assignedTo);
                            if (!$saveas->getUserId()) {
                                Mage::getSingleton('adminhtml/session')->addError($this->__('Could not find user with id: %s', $assignedTo));
                                $saveas = Mage::getSingleton('admin/session')->getUser();
                            }
                        } else {
                            $saveas = Mage::getSingleton('admin/session')->getUser();
                            //check for an assigned sales rep
                            if($_quoteadv->getCustomerId()){
                                $customer = Mage::getModel('customer/customer')->load($_quoteadv->getCustomerId());
                                if($customer && $customer->getAssignedSalesRep()){
                                    $saveas = Mage::getModel('admin/user')->load($customer->getAssignedSalesRep());
                                }
                            }
                        }

                        $oldSalesRep = $_quoteadv->getUserId();
                        $_quoteadv->setUserId($saveas->getUserId());

                        //#save shipping price
                        $shippingType = $this->getRequest()->getPost("shipping_type", "");
                        $shippingPrice = $this->getRequest()->getPost("shipping_price", -1);

                        $quoteStoreId = $_quoteadv->getStoreId();
                        $priceContainsTax = Mage::helper('tax')->shippingPriceIncludesTax($quoteStoreId);
                        if ($priceContainsTax) {
                            //fallback for situations where getWebsite doesn't return a object
                            if(is_object(Mage::app()->getWebsite(true))){
                                $store = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStore();
                            } else {
                                $store = Mage::app()->getStore('default');
                                $message = 'Mage::app()->getWebsite(true) is not a object, fallback applied';
                                Mage::log('Message: ' .$message, null, 'c2q.log');
                            }

                            $taxCalculation = Mage::getModel('tax/calculation');
                            $customer = $_quoteadv->getCustomer();
                            if ($customer) {
                                $taxCalculation->setCustomer($customer);
                            }
                            $request = $taxCalculation->getRateOriginRequest($store);

                            //get shipping tax id (store)
                            $taxClassId = Mage::helper('tax')->getShippingTaxClass($store);
                            $percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));

                            $quoteStore = Mage::getModel('core/store')->load($quoteStoreId);
                            $taxCalculation = Mage::getModel('tax/calculation');
                            $customer = $_quoteadv->getCustomer();
                            if ($customer) {
                                $taxCalculation->setCustomer($customer);
                            }
                            $request = $taxCalculation->getRateRequest(null, null, null, $quoteStore);

                            //get shipping tax id (quote)
                            $taxClassId = Mage::helper('tax')->getShippingTaxClass($store);
                            $quotePercent = $taxCalculation->getRate($request->setProductClassId($taxClassId));

                            if($percent != $quotePercent){
                                //100/((100+($percent-$quotePercent))/100);
                                $rateFix = 100/((100+$percent)/100);
                                $rateFix = $rateFix*((100+$quotePercent)/100);
                                $rateFix = 100/$rateFix;

                                $shippingPrice = $shippingPrice * $rateFix;
                            }
                        }

                        //set shipping data from post data and save old data
                        //$orgShippingType = $_quoteadv->getShippingType();
                        //$orgShippingPrice = $_quoteadv->getShippingPrice();
                        $_quoteadv->setShippingType($shippingType);
                        $_quoteadv->setShippingPrice($shippingPrice);
                        $shippingBasePrice = $shippingPrice / $rate;
                        $_quoteadv->setShippingBasePrice($shippingBasePrice);

                        $_quoteadv->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());

                        $userId = $_quoteadv->getUserId();
                        if (empty($userId)) {
                            $adm_id = Mage::getSingleton('admin/session')->getUser()->getId();

                            //check for an assigned sales rep
                            if($_quoteadv->getCustomerId()){
                                $customer = Mage::getModel('customer/customer')->load($_quoteadv->getCustomerId());
                                if($customer && $customer->getAssignedSalesRep()){
                                    $adm_id = $customer->getAssignedSalesRep();
                                }
                            }

                            $_quoteadv->setUserId($adm_id);
                        } else {
                            $model = Mage::getModel('admin/user')->load($userId);
                            //#admin is not exists
                            if (!$model->getId() && isset($id) && $id) {
                                $adm_id = Mage::getSingleton('admin/session')->getUser()->getId();
                                $_quoteadv->setUserId($adm_id);
                            }
                        }

                        // Unset data from sales rep if not allowed
                        if (!Mage::getSingleton('admin/session')->isAllowed('sales/qquoteadv/salesrep')) {
                            $_quoteadv->setUserId($_quoteadv->getOrigData('user_id'));
                        }

                        try {
                            $shippingType = $_quoteadv->getShippingType();
                            if ($shippingType == "I" || $shippingType == "O") {

                                // Add Cart2Quote shippingrate
                                $shippingPrice = $_quoteadv->getShippingPrice();
                                $carrier = Mage::getStoreConfig("carriers/qquoteshiprate/title", $_quoteadv->getStoreId());
                                $method = Mage::getStoreConfig("carriers/qquoteshiprate/name", $_quoteadv->getStoreId());
                                $methodDescription = $carrier . " - " . $method;

                                $rateData = Mage::getModel('qquoteadv/quoteshippingrate');
                                $rateData->setData('carrier', 'qquoteshiprate');
                                $rateData->setData('carrier_title', $carrier);
                                $rateData->setData('code', 'qquoteshiprate_qquoteshiprate');
                                $rateData->setData('method_description', $methodDescription);
                                $rateData->setData('price', $shippingPrice / $rate);
                                $rateData->setData('method', 'qquoteshiprate');
                                $rateData->setData('method_title', $method);

                                $_quoteadv->setShippingMethod($rateData);
                            } elseif (is_integer((int)$shippingType)) {
                                // Default Shipping Method
                                $_quoteadv->getAddress();
                                $rateData = Mage::getModel('qquoteadv/quoteshippingrate')->load($shippingType);
                                if ($rateData) {
                                    $_quoteadv->setShippingMethod($rateData);
                                }
                            } else {
                                // Remove Shipping Method
                                $_quoteadv->unsetShippingMethod();
                            }

                            // Quote Price Recalculation
                            $recalPrice = $this->getRequest()->getParam('recal_price');
                            if ($recalPrice) {
                                $validRecalPrice = false;

                                //check fixed price
                                if ($recalPrice['fixed'] != "") {
                                    $fixedPriceNummeric = is_numeric($recalPrice['fixed']);
                                    $fixedPriceInvalid = ($recalPrice['fixed'] != null && $fixedPriceNummeric && !((float)$recalPrice['fixed'] >= 0));

                                    if ($fixedPriceInvalid || !$fixedPriceNummeric) {
                                        Mage::getSingleton('adminhtml/session')->addNotice($this->__('Quote Reduction was not a valid decimal number'));
                                    } else {
                                        $validRecalPrice = true;
                                    }
                                }

                                //check percentage price
                                if ($recalPrice['percentage'] != "") {
                                    $percentagePriceNummeric = is_numeric($recalPrice['percentage']);
                                    $percentagePriceInvalid = ($recalPrice['percentage'] != null && $percentagePriceNummeric && !((float)$recalPrice['percentage'] >= 0));

                                    if ($percentagePriceInvalid || !$percentagePriceNummeric) {
                                        Mage::getSingleton('adminhtml/session')->addNotice($this->__('Quote Reduction was not a valid decimal number'));
                                    } else {
                                        $validRecalPrice = true;
                                    }
                                }

                                //recalulate price if given recal price is valid
                                if ($validRecalPrice) {
                                    if ($_quoteadv->recalculateFixedPrice($recalPrice)) {
                                        $_quoteadv->setTotalsCollectedFlag(false); //force recalculation
                                    } else {
                                        Mage::getSingleton('adminhtml/session')->addError($this->__('Could not recalculate Quote Price'));
                                    }
                                }
                            }

                            //generate shipping prices
                            $_quoteadv->save();
                            $shippingAddress = $_quoteadv->getShippingAddress();
                            if($shippingAddress){
                                $shippingAddress->requestShippingRates();
                            }

                            //trigger collect totals if needed
                            $collectTotalsOnPageLoad = Mage::getStoreConfig('qquoteadv_advanced_settings/backend/calculate_quote_totals_on_load');
//                            if ($orgShippingType != $shippingType || $orgShippingPrice != $shippingPrice) {
//                                //collect totals is needed
//                                if (!$collectTotalsOnPageLoad) {
//                                    //but only when it isn't done on page load
//                                    $_quoteadv->setTotalsCollectedFlag(false); //force recalculation
//                                    $_quoteadv->collectTotals();
//                                }
//                            } else {
                                //collect totals after all needed actions
                                if (!$collectTotalsOnPageLoad) {
                                    //but only when it isn't done on page load
                                    $_quoteadv->setTotalsCollectedFlag(false); //force recalculation
                                    $_quoteadv->collectTotals();
                                    $_quoteadv->save();
                                }
//                            }

                            $this->_quoteadv = $_quoteadv;

                        } catch (Exception $e) {
                            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                        }

                        // unset flag and data from creating PDF method
                        if ($pdfPrint === true) {
                            $this->setFlag('qquoteadv', 'print', false);
                            unset($this->_postData);
                            return $this;
                        }

                        if ($this->getRequest()->getParam('back')) {
                            Mage::helper('qquoteadv/logging')->sentAnonymousData('save', 'b', $quoteId);

                            // Check for negative profit
                            if (Mage::getStoreConfig('qquoteadv_quote_configuration/proposal/profit', $_quoteadv->getData('store_id')) != 1) {
                                $profit = $_quoteadv->getQuoteProfit();

                                if ($profit < 0 && isset($oldStatus) && isset($oldSubstatus)) {
                                    // Reverting status
                                    $_quoteadv->setStatus($oldStatus);
                                    $_quoteadv->setSubstatus($oldSubstatus);
                                    $_quoteadv->save();
                                    $this->_quoteadv = $_quoteadv;
                                    Mage::getSingleton('adminhtml/session')->addNotice($this->__('Proposal was not sent, no profit'));
                                    $this->_redirect('*/*/edit', ['id' => $quoteId]);
                                    return null;
                                }
                            }

                            $realQuoteadvId = $_quoteadv->getIncrementId() ? $_quoteadv->getIncrementId() : $_quoteadv->getId();

                            //#send Proposal email
                            $customerId = $_quoteadv->getCustomerId();
                            if ($customerId) {
                                Mage::register('qquoteadv', $_quoteadv);
                                $this->_sendProposalEmail($customerId, $realQuoteadvId, $_quoteadv->getId());
                                Mage::unregister('qquoteadv');
                            }
                        }

                        // check for hold status
                        $hold = $this->getRequest()->getParam('hold');
                        if ($hold) {
                            if ($hold == 1) { // Set Quote to 'Hold'
                                if (Mage::getModel('qquoteadv/substatus')->getParentStatus($_quoteadv->getSubstatus()) != $_quoteadv->getStatus()) {
                                    $_quoteadv->setSubstatus($_quoteadv->getStatus());
                                }
                                $_quoteadv->setStatus(Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_SAVED);
                                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Quote is currently on hold'));
                            } elseif ($hold == 2) { // Set Quote to 'Unhold'
                                $statusses = Mage::getModel('qquoteadv/substatus')->getStatuses($_quoteadv->getSubstatus());
                                $status = Mage::getModel('qquoteadv/substatus')->getStatus($_quoteadv->getSubstatus());
                                $substatus = Mage::getModel('qquoteadv/substatus')->getSubstatus($_quoteadv->getSubstatus());
                                if ($statusses) {
                                    $statusses->checkUnholdStatus($_quoteadv);
                                    Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Quote is succesfully unhold!'));
                                    $_quoteadv->setStatus($status);
                                    $_quoteadv->setSubstatus($substatus);
                                } else {
                                    Mage::getSingleton('adminhtml/session')->addNotice($this->__('Old status could not be determined'));
                                    $_quoteadv->setStatus(Ophirah_Qquoteadv_Model_Status::STATUS_REQUEST);
                                }
                            }
                            $_quoteadv->save();
                            $this->_quoteadv = $_quoteadv;
                            Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final', ['quote' => $_quoteadv]);
                            Mage::dispatchEvent('qquoteadv_qqadvcustomer_after_saveaction', ['quote' => $_quoteadv]);
                            //event specific event
                            $event = $this->getRequest()->getParam('event');
                            if ($event) {
                                Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final_' . $event, ['quote' => $_quoteadv]);
                                Mage::dispatchEvent('qquoteadv_qqadvcustomer_before_saveaction_' . $event, ['quote' => $_quoteadv]);
                            }
                        }
                    }
                }

                //check if $_quoteadv is set
                if (!isset($_quoteadv) || empty($_quoteadv)) {
                    $quoteId = (int)$this->getRequest()->getParam('id');
                    if ($quoteId) {
                        $_quoteadv = $this->getQuotationQuote($quoteId);
                    } else {
                        $_quoteadv = $this->getQuotationQuote();
                    }
                }

                // Send email to the new salesrep when assigned to the quote
                if (isset($saveas) && $saveas->getUserId()) {
                    if (!isset($oldSalesRep) || ($oldSalesRep != $saveas->getUserId())) {
                        $this->sendEmailSalesRep($_quoteadv, $saveas);
                    }
                }

                //check for errors
                if (count(Mage::getSingleton('adminhtml/session')->getMessages()->getErrors())) {
                    Mage::getSingleton('adminhtml/session')->addNotice($this->__('Quote was saved with errors'));
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final_error', ['quote' => $_quoteadv]);
                    //event specific event
                    $event = $this->getRequest()->getParam('event');
                    if ($event) {
                        Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final_error_' . $event, ['quote' => $_quoteadv]);
                    }
                } else {
                    Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Quote was successfully saved'));
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final_success', ['quote' => $_quoteadv]);
                    //event specific event
                    $event = $this->getRequest()->getParam('event');
                    if ($event) {
                        Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_final_success_' . $event, ['quote' => $_quoteadv]);
                    }
                }

                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if (isset($quoteId)) {
                    if (isset($data['redirect2neworder']) && $data['redirect2neworder'] == 1) {
                        $this->_redirect(
                            '*/*/convert/', [
                                              'id'         => $quoteId,
                                              'q2o_serial' => base64_encode(json_encode($data['q2o']))
                            ]
                        );
                    } elseif ($this->getRequest()->getParam('back')) {
                        $this->_redirect('*/*/edit', ['id' => $quoteId]);
                    } else {
                        $this->_redirectAnhcor('*/*/edit', ['id' => $quoteId], '#products');
                        Mage::getSingleton("core/session")->setCollectTotals(1);
                    }
                } else {
                    $this->_redirect('*/*/');
                }

                return null;
            } catch (Exception $e) {
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return null;
            }
        }

        Mage::getSingleton('adminhtml/session')->addError($this->__('Unable to find item to save'));
        $this->_redirect('*/*/');

        Mage::dispatchEvent('ophirah_qquoteadv_admin_save_after', [$data]);

        //event specific event
        $event = $this->getRequest()->getParam('event');
        if ($event) {
            Mage::dispatchEvent('ophirah_qquoteadv_admin_save_after_' . $event, [$data]);
        }

        return null;
    }

    /**
     * Action to cancel a given quote
     */
    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_delete_before', [$id]);

        if ($id > 0) {
            try {
                $model = $this->getQuotationQuote($id);
                $model->setStatus(Ophirah_Qquoteadv_Model_Status::STATUS_CANCELED); //STATUS_REJECTED
                $model->save();
                Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftercancel', ['quote' => $model]);

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Quote was successfully canceled'));
                Mage::helper('qquoteadv/logging')->sentAnonymousData('cancel', 'b', $id);
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }

        $this->_redirect('*/*/');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_delete_after', [$id]);
    }

    /**
     * Mass delete action (used in the grid)
     */
    public function massDeleteAction()
    {
        //The user must have permission for "mass delete quote" to use this
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/qquoteadv/actions/massdelete')) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('qquoteadv')->__('Mass delete action is only allowed for users with permission "Mass Delete Quote".')
            );
            $this->_redirect('*/*/index');
            return;
        }

        $qquoteIds = $this->getRequest()->getParam('qquote');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_massDelete_before', [$qquoteIds]);

        if (!is_array($qquoteIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('Please select items.'));
        } else {
            try {
                foreach ($qquoteIds as $qquoteId) {
                    $qquote = Mage::getModel('qquoteadv/qqadvcustomer')->load($qquoteId);
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_beforedelete_massDelete', ['quote' => $qquote]);
                    //$qquote->delete();
                    $qquote->setIsQuote(3);
                    $qquote->setStatus(Ophirah_Qquoteadv_Model_Status::STATUS_CANCELED);
                    $qquote->save();
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_afterdelete_massDelete', ['quote' => $qquote]);
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were deleted', count($qquoteIds))
                );
            } catch (Exception $e) {
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_massDelete_after', [$qquoteIds]);
    }

    /**
     * Set Status and substatus for quote
     *
     */
    public function massStatusAction()
    {
        $qquoteIds = $this->getRequest()->getParam('qquote');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_massStatus_before', [$qquoteIds]);

        if (!is_array($qquoteIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('Please select items.'));
        } else {
            try {
                $requestStatus = $this->getRequest()->getParam('status');
                $status = Mage::getModel('qquoteadv/status')->getStatus($requestStatus);
                if(is_object($status)){
                    $status = $status->getStatus();
                }

                foreach ($qquoteIds as $qquoteId) {
                    $qquote = Mage::getSingleton('qquoteadv/qqadvcustomer')->load($qquoteId);
                    if ($status == Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_SAVED) {
                        if ($qquote->getStatus() != Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_SAVED) {
                            $qquote->setSubstatus($qquote->getStatus());
                        }
                    } else {
                        $qquote->unsetSubStatus();
                    }

                    $qquote->setStatus($status);
                    $qquote->setIsMassupdate(true);
                    $qquote->save();
                    Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_massStatus', ['quote' => $qquote]);
                }

                $this->_getSession()->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were updated', count($qquoteIds))
                );
            } catch (Exception $e) {
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_massStatus_after', [$qquoteIds]);
    }

    /**
     * Mass Follow Up Update
     *
     * Updates follow up date for
     * selected quotes. If no valid date
     * is given, date is set to null
     */
    public function massFollowupAction()
    {
        $qquoteIds = $this->getRequest()->getParam('qquote');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_massFollowup_before', [$qquoteIds]);

        if (!is_array($qquoteIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('Please select items.'));
        } else {
            try {
                foreach ($qquoteIds as $qquoteId) {

                    if (strtotime($this->getRequest()->getParam('followup'))) {
                        $qquote = Mage::getSingleton('qquoteadv/qqadvcustomer')
                            ->load($qquoteId)
                            ->setFollowup($this->getRequest()->getParam('followup'))
                            ->setIsMassupdate(true)
                            ->save();
                        Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_massFollowup', ['quote' => $qquote]);
                    } else {
                        $qquote = Mage::getSingleton('qquoteadv/qqadvcustomer')
                            ->load($qquoteId)
                            ->setFollowup()
                            ->setIsMassupdate(true)
                            ->save();
                        Mage::dispatchEvent('qquoteadv_qqadvcustomer_aftersave_massFollowup', ['quote' => $qquote]);
                    }
                }
                $this->_getSession()->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were updated', count($qquoteIds))
                );
            } catch (Exception $e) {
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_massFollowup_after', [$qquoteIds]);
    }

    /**
     * Mass update action (used in the grid)
     */
    public function massUpdateAction()
    {
        Mage::dispatchEvent('ophirah_qquoteadv_admin_massUpdate_before', [$this->getRequest()->getParam('ranges')]);

        // Check for a valid Enterprise License
        if (Mage::helper('qquoteadv/license')->validLicense('mass_update_quote_requests')) { //not allowed in trial mode
            if ($this->getRequest()->isXmlHttpRequest()) {
                Mage::getModel('qquoteadv/massupdate')->startMassUpdateAllowedToQuote($this->getRequest()->getParam('quote_mode'), $this->getRequest()->getParam('ranges'));
            }
        } else {
            $message = $this->__("This function is only available in the enterprise (non-trial) edition. <a href='https://www.cart2quote.com/magento-quotation-module-pricing.html'>Upgrade</a>");
            Mage::getSingleton('adminhtml/session')->addNotice($message);
        }

        Mage::dispatchEvent('ophirah_qquoteadv_admin_massUpdate_after', [$this->getRequest()->getParam('ranges')]);
    }

    /**
     * Send email to client to informing about the quote proposition
     * @param array $params customer address
     * @return string
     */
    public function sendEmail($params)
    {
        //Create an array of variables to assign to template
        $vars = [];

        $this->quoteId = (int)$this->getRequest()->getParam('id');
        /* @var Ophirah_Qquoteadv_Model_Qqadvcustomer $_quoteadv */
        $_quoteadv = $this->getQuotationQuote($this->quoteId);

        $quoteItems = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
            ->addFieldToFilter('quote_id', $this->quoteId)
            ->load();

        // check items
        $errorMsg = [];
        $errors = [];
        foreach ($quoteItems as $quoteItem) {
            $check = Mage::helper('qquoteadv')->isInStock($quoteItem->getData('product_id'));
            if ($check->getData('has_error')) {
                $errors[] = $check->getData('message');
            }
        }

        //#return back in case any error found
        if (count($errors)) {
            $errorMsg = array_merge($errorMsg, $errors);
            foreach ($errorMsg as $message) {
                Mage::getSingleton('adminhtml/session')->addError($message);
            }
        }

        $vars['quote'] = $_quoteadv;
        $vars['customer'] = Mage::getModel('customer/customer')->load($_quoteadv->getCustomerId());
        $vars['store'] = Mage::app()->getStore($_quoteadv->getStoreId());

        $template = Mage::helper('qquoteadv/email')->getEmailTemplateModel($_quoteadv->getStoreId());

        // Default template
        $quoteadv_param = Mage::getStoreConfig('qquoteadv_quote_emails/templates/proposal', $_quoteadv->getStoreId());
        // Get the checkout URL template
        if (Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/checkout_alternative', $_quoteadv->getData('store_id')) && $_quoteadv->getData('alt_checkout')) {
            $quoteadv_param = Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/checkout_alternative_email', $_quoteadv->getStoreId());
        }

        $disabledEmail = Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;
        if ($quoteadv_param != $disabledEmail){
            if ($quoteadv_param) {
                $templateId = $quoteadv_param;
            } else {
                $templateId = self::XML_PATH_QQUOTEADV_REQUEST_PROPOSAL_EMAIL_TEMPLATE;
            }

            // get locale of quote sent so we can sent email in that language	
            $storeLocale = Mage::getStoreConfig('general/locale/code', $_quoteadv->getStoreId());

            if (is_numeric($templateId)) {
                $template->load($templateId);
            } else {
                $template->loadDefault($templateId, $storeLocale);
            }

            $vars['attach_pdf'] = $vars['attach_doc'] = false;

            //Create pdf to attach to email
            if (Mage::getStoreConfig('qquoteadv_quote_emails/attachments/pdf', $_quoteadv->getStoreId())) {
                $_quoteadv->_saveFlag = true;

                //totals need to be collected before generating the pdf (until we save the totals in the database)
                $_quoteadv->collectTotals();

                $pdf = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($_quoteadv);
                $_quoteadv->_saveFlag = false;
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
            //Check if attachment needs to be sent with email
            $doc = Mage::getStoreConfig('qquoteadv_quote_emails/attachments/doc', $_quoteadv->getStoreId());
            if ($doc) {
                $pathDoc = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'quoteadv' . DS . $doc;
                try {
                    $file = file_get_contents($pathDoc);
                    $mimeType = Mage::helper('qquoteadv/file')->getMimeType($pathDoc);

                    $info = pathinfo($pathDoc);
                    //$extension = $info['extension']; 
                    $basename = $info['basename'];
                    $template->getMail()->createAttachment($file, $mimeType, Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64, $basename);
                    $vars['attach_doc'] = true;
                } catch (Exception $e) {
                    Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                }
            }
            //Get remark
            $remark = Mage::getStoreConfig('qquoteadv_quote_configuration/proposal/qquoteadv_remark', $_quoteadv->getStoreId());
            if ($remark) {
                $remark = nl2br($remark);
                $vars['remark'] = $remark;
            }

            $adm_name = $this->getAdminName($_quoteadv->getUserId());
            $adm_name = trim($adm_name);
            if (empty($adm_name)) {
                $adm_name = $this->getAdminName(Mage::getSingleton('admin/session')->getUser()->getId());
            }
            if (!empty($adm_name)) {
                $vars['adminname'] = $adm_name;
            }

            $vars['link'] = Mage::helper('qquoteadv/licensechecks')->getAutoLoginUrl($_quoteadv, 2);

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

            $template->setDesignConfig(['store' => $_quoteadv->getStoreId()]);

            /**
             * Opens the qquote_request.html, throws in the variable array
             * and returns the 'parsed' content that you can use as body of email
             */
            //emulate quote store for corret email design
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($_quoteadv->getStoreId());

            //generate template getProcessedTemplate is called inside send
            $template->setData('c2qParams', $params);
            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_before', ['template' => $template]);
            $res = $template->send($params['email'], $params['name'], $vars);
            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_after', ['template' => $template, 'result' => $res]);

            // Stop store emulation process
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

            return $res;

        }

        Mage::getSingleton('adminhtml/session')->addError("Quote email is disabled");
        return $disabledEmail;
    }

    /**
     * Send email to the new sales represent when saving the quote
     * @param $_quoteadv
     * @param $saveas
     */
    public function sendEmailSalesRep($_quoteadv, $saveas)
    {
        $emailAddresses = $saveas->getEmail();
        if (!empty($emailAddresses)) {
            $emailAddresses = array_filter(explode(';', $emailAddresses));

            $template = Mage::helper('qquoteadv/email')->getEmailTemplateModel($_quoteadv->getStoreId());

            $disabledEmail = Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;
            $quoteadv_param = Mage::getStoreConfig('qquoteadv_quote_emails/templates/quote_request_notification', $_quoteadv->getStoreId());

            if ($quoteadv_param != $disabledEmail && count($emailAddresses) > 0) {

                //Vars into email templates
                $vars = [
                    'quoteUrl' => Mage::helper("adminhtml")->getUrl("adminhtml/redirect/quoteEdit", ['id' => $_quoteadv->getId()]),
                    'quote' => Mage::getModel('qquoteadv/qqadvcustomer')->load($_quoteadv->getId()),
                    'customer' => Mage::getModel('customer/customer')->load($_quoteadv->getCustomerId()),
                    'quoteId' => $_quoteadv->getId()
                ];


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

                //getProcessedTemplate is called inside  $template->send
                //$template->getProcessedTemplate($vars);

                Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_before', ['template' => $template]);
                $res = $template->send($emailAddresses, null, $vars);
                Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_after', ['template' => $template, 'result' => $res]);
                if (empty($res)) {
                    $message = Mage::helper('Ophirah_RequestNotification')->__("Qquote request email notification was't sent quote #%s", $_quoteadv->getId());
                    Mage::log('Exception: RequestNotification: ' . $message, null, 'c2q_exception.log', true);
                }
            }
        }
    }

    /**
     * Add quote comment action
     *
     * @depricated
     */
    public function addCommentAction()
    {
        $qquoteadv = $this->_initQuoteadv();
        if ($qquoteadv) {
            try {
                $response = false;
                $data = $this->getRequest()->getPost('history');

                Mage::dispatchEvent('ophirah_qquoteadv_admin_addComment_before', [$data]);

                //seems not used?
                //$comment = trim(strip_tags($data['comment']));

                $this->loadLayout('empty');
                $this->renderLayout();
            } catch (Mage_Core_Exception $e) {
                $response = [
                    'error' => true,
                    'message' => $e->getMessage(),
                ];
            } catch (Exception $e) {
                $response = [
                    'error' => true,
                    'message' => $this->__('Can not add quote history.')
                ];
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            }
            if (is_array($response)) {
                $response = Zend_Json::encode($response);
                $this->getResponse()->setBody($response);
            }
        }

        Mage::dispatchEvent('ophirah_qquoteadv_admin_addComment_after', []);
    }

    /**
     * Initialize qquoteadv model instance
     *
     * @return Quote|bool
     */
    protected function _initQuoteadv()
    {
        $id = $this->getRequest()->getParam('quote_id');
        $qquoteadv = $this->getQuotationQuote($id);

        if (!$qquoteadv->getId()) {
            $this->_getSession()->addError($this->__('This quote no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }

        Mage::register('qquote_data', $qquoteadv);

        return $qquoteadv;
    }

    /**
     * Function that generates the PDF for a given quote
     *
     * @return null
     */
    public function pdfqquoteadvAction()
    {
        $errorMsg = [];
        $errors = [];

        // From Print button, save quote first
        if ($this->getRequest()->getPost() && $this->_saveFlag === false) {
            $this->setFlag('qquoteadv', 'print', true);
            $this->_postData = $this->getRequest()->getPost();
            $save = $this->saveAction();
            if (!empty($save) && !is_object($save)) {
                $errors = $save;
            }
            $this->setFlag('qquoteadv', 'print', false);
        }

        $quoteadvId = $this->getRequest()->getParam('id');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_pdfqquoteadv_before', [$quoteadvId]);

        $flag = false;
        if (!empty($quoteadvId) && !$errors) {
            $_quoteadv = $this->getQuotationQuote($quoteadvId);
            $quoteItems = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
                ->addFieldToFilter('quote_id', $quoteadvId)
                ->load();

            // check items
            foreach ($quoteItems as $quoteItem) {
                $check = Mage::helper('qquoteadv')->isInStock($quoteItem->getData('product_id'));
                if ($check->getData('has_error')) {
                    $errors[] = $check->getData('message');
                }
            }

            if (count($errors) < 1) {

                if ($quoteItems->getSize()) {
                    $flag = true;
                    if (!isset($pdf)) {
                        $_quoteadv->collectTotals();
                        $pdf = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($_quoteadv);
                    } else {
                        $pages = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($quoteItems);
                        $pdf->pages = array_merge($pdf->pages, $pages->pages);
                    }
                }

                if ($flag && isset($pdf)) {
                    $realQuoteadvId = $_quoteadv->getIncrementId();
                    $fileName = Mage::helper('qquoteadv')->__('Price_proposal_%s', $realQuoteadvId);

                    return $this->_prepareDownloadResponse($fileName . '.pdf', $pdf->render(), 'application/pdf');
                } else {
                    $this->_getSession()->addError($this->__('There are no printable documents related to selected quotes'));
                    $this->_redirect('*/*/');
                }
            }
        }

        //#return back in case any error found
        if (count($errors)) {
            $urlReturn = '*/*/edit/id/' . $quoteadvId;
            $errorMsg = array_merge($errorMsg, $errors);
            foreach ($errorMsg as $message) {
                Mage::getSingleton('adminhtml/session')->addError($message);
            }
        }

        if (!isset($urlReturn) || empty($urlReturn)) {
            $urlReturn = '*/*/';
        }
        $this->_redirect($urlReturn);
        Mage::dispatchEvent('ophirah_qquoteadv_admin_pdfqquoteadv_after', [$quoteadvId]);
        return null;
    }

    /**
     * Function that returns if this installation is a trial version of Cart2Quote
     *
     * @param null $createHash
     * @return mixed
     */
    final public function isTrialVersion($createHash = null)
    {
        return Mage::helper('qquoteadv/license')->isTrialVersion($createHash);
    }

    /**
     * Function that generates the update/trial message in the quote edit screen
     *
     * @param bool|false $updateMsg
     * @param int $storeId
     * @return string
     */
    public function getMsgToUpgrade($updateMsg = false, $storeId = 0)
    {
        $createHash = Mage::registry('createHash');

        $msg = '
        <style>

        #quoteadv-box-header {

        }
        .leightbox1 .text {

        }
        #overlay, #overlaylink{
            display:none;
            position:absolute;
            top:0;
            left:0;
            width:100%;
            height:200%;
            z-index:1000;
            background-color:#333;
            -moz-opacity: 0.8;
            opacity:.80;
            filter: alpha(opacity=80);
        }

        </style>

        <script type="text/javascript">
        function prepareIE(height, overflow) {
            bod = document.getElementsByTagName(\'body\')[0];
            bod.style.height = height;
            bod.style.overflow = overflow;
    
            htm = document.getElementsByTagName(\'html\')[0];
            htm.style.height = height;
            htm.style.overflow = overflow;
        }

        function initMsg() {
            bod 				= document.getElementsByTagName(\'body\')[0];
            overlay 			= document.createElement(\'div\');
            overlay.id			= \'overlay\';
            bod.appendChild(overlay);
            $(\'overlay\').style.display = \'block\';
            $(\'lightbox1\').style.display = \'block\';
            prepareIE("auto", "auto");
        }

        function hideBox() {
            $(\'lightbox1\').style.display = \'none\';
            $(\'overlay\').style.display = \'none\';
        }

        </script>';

        $headerText = "";
        $onClick = 'hideBox()';
        $openingDiv = "";
        $closingDiv = "";
        $btn1 = "";
        $btn2 = "";
        $btn3 = "";
        $smallPrint = false;
        $text = '';

        //check what the situation is
        if (Mage::helper('qquoteadv/licensechecks')->showFreeUserOptions()) {
        //if ((true)) {
            $expiry = Mage::helper('qquoteadv/license')->getC2QExpiryDate();
            if ($expiry > date("Ymd")){
                $expiryFormatted = "has been disabled";
            } else {
                $expiryFormatted = "has expired on " . substr($expiry, 6, 2) . "." . substr($expiry, 4, 2) . "." . substr($expiry, 0, 4);
            }

            $text = $this->__(Mage::helper('qquoteadv/licensechecks')->_expiryText, $expiryFormatted);
            //$onClick = 'history.back()';
            $onClick = 'hideBox()';

            $headerText = $this->__('License Notice');
            $openingDiv = '<div class="c2q-activate-popup">';
            $closingDiv = '</div>';

            $btn1 = '<button target="_blank" formtarget="_blank" class="button button2 floatleft" title="Contact Sales" onclick="document.location.href=\'https://cart2quotesales.zendesk.com/hc/en-us/requests/new\'">' . $this->__('Contact Sales') . '</button> ';
            $btn2 = '<button target="_blank" formtarget="_blank" class="button button2 btn-prim" title="Editions and Pricing" onclick="document.location.href=\'https://www.cart2quote.com/magento-quotation-module-plans-pricing.html\'">' . $this->__('Editions and Pricing') . '</button> ';

        } elseif ($this->isTrialVersion($createHash) && !$this->hasExpired()) {
            $expiry = Mage::helper('qquoteadv/license')->getC2QExpiryDate();
            $now = now();
            $expiry = substr($expiry, 0, 4) . "-" . substr($expiry, 4, 2) . "-" . substr($expiry, 6, 2);
            $diff = abs(strtotime($expiry) - strtotime($now));
            $days = floor($diff / (60 * 60 * 24));
            $headerText = $this->__('Development Trial');
            $openingDiv = '<div class="c2q-activate-popup">';
            $closingDiv = '</div>';
            $daysToGo = sprintf("%d", $days);

            $text = $this->__(Mage::helper('qquoteadv/licensechecks')->_trialText, $daysToGo);
            $onClick = 'hideBox()';

            $btn1 = '<button class="button button1 floatleft" title="Continue Trial" href="" onclick="' . $onClick . '">' . $this->__('Continue Trial') . '</button>';
            $btn2 = '<button target="_blank" formtarget="_blank" class="button button2" title="Purchase a license" onclick="document.location.href=\'https://www.cart2quote.com/magento-quotation-module-pricing.html?utm_source=Client_Website&utm_medium=Popup_trialText&utm_campaign=Client_Website_Upgrade\'">' . $this->__('See Plans and Pricing') . '</button> ';
        } elseif (!$this->checkQuoteLicense($storeId)) {
            $text = $this->__(Mage::helper('qquoteadv/licensechecks')->_wrongLicenseText);
            $onClick = 'history.back()';
            $headerText = $this->__('Please Upgrade');
            $openingDiv = '<div class="c2q-activate-popup">';
            $closingDiv = '</div>';

            $btn1 = '<button class="button button1" title="Continue" onclick="' . $onClick . '">' . $this->__('Not Now') . '</button>';
            $btn2 = '<button target="_blank" formtarget="_blank" class="button button2" title="Upgrade" onclick="document.location.href=\'https://www.cart2quote.com/magento-quotation-module-pricing.html?utm_source=Client_Website&utm_medium=Popup_wrongLicenseText&utm_campaign=Client_Website_Upgrade\'">' . $this->__('See Plans and Pricing') . '</button> ';
            $smallPrint = $this->__('<a href="https://www.cart2quote.com/ordering-licenses?utm_source=Client_Website&utm_medium=Popup_wrongLicenseText&utm_campaign=Client_Website_Upgrade" class="sublinkBottom">read more about licensing</a>');
        }

        $msg .= '<div id="lightbox1" class="leightbox1" style="display:none;">';
        $msg .= $openingDiv;
        $msg .= '   <div>';
        $msg .= '           <a onclick="' . $onClick . '" id="quoteadv-box-header-close-btn"></a>';
        $msg .= '<div class="text-content">';
        $msg .= '       <div id="quoteadv-box-header">';
        $msg .=             $headerText;
        $msg .= '       </div>';
        $msg .= '       <div class="text" >' . $text . '</div>';
        if($smallPrint){
            $msg .= '   <div class="smallprint" >' . $smallPrint . '</div>';
        }
        $msg .= '</div>';
        $msg .= '<div class="button-container">';
        $msg .=         $btn1;
        $msg .=         $btn3;
        $msg .=         $btn2;
        $msg .= '</div>';
        $msg .= $closingDiv;
        $msg .= '    </div>';
        $msg .= '</div>';

        //add initMsg() javascript
        $msg .= '<script type="text/javascript">document.observe(\'dom:loaded\', function(){
                    initMsg();
                 });</script>';

        Mage::unregister('createHash');

        //fallback for unfinished quotes
        if (!is_array($createHash) || (empty($createHash[0]) && empty($createHash[1]))) {
            if ($text == '') {
                return '';
            }
        }

        return $msg;
    }

    /**
     * Check quote license for a given store
     *
     * @param $storeId
     * @return mixed
     */
    final private function checkQuoteLicense($storeId)
    {
        return Mage::helper('qquoteadv/license')->checkQuoteLicense($storeId);
    }

    /**
     * Get the access level from the license helper
     *
     * @param null $createHash
     * @return mixed
     */
    final private function getAccessLevel($createHash = null)
    {
        return Mage::helper('qquoteadv/license')->getAccessLevel($createHash);
    }

    /**
     * Check if trial has expired
     *
     * @return mixed
     */
    final private function hasExpired()
    {
        return Mage::helper('qquoteadv/license')->hasExpired();
    }

    /**
     * Retrieve session object
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    /**
     * Retrieve order create model
     *
     * @return Mage_Adminhtml_Model_Sales_Order_Create
     */
    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }

    /**
     * Function that converts a quote to an order (backend)
     *
     * @param $quoteadvId
     * @param $requestedItems
     */
    protected function _convertQuoteItemsToOrder($quoteadvId, $requestedItems)
    {
        //# build sql
        $resource = Mage::getSingleton('core/resource');
        $read = $resource->getConnection('core_read');
        $tblProduct = $resource->getTableName('quoteadv_product');
        $tblRequestItem = $resource->getTableName('quoteadv_request_item');

        $sql = "SELECT * 
                FROM $tblProduct p 
                INNER JOIN $tblRequestItem i
                  ON p.quote_id=i.quote_id 
                  AND i.quoteadv_product_id=p.id
                  AND p.quote_id=$quoteadvId";

        if (count($requestedItems)) {
            $requestedItemsInts = [];
            foreach ($requestedItems as $index => $requestedItem) {
                $requestedItemsInts[$index] = intval($requestedItem);
            }

            $items = implode(',', $requestedItemsInts);
            $sql .= " AND i.request_id IN($items)";
            $sql .= " ORDER BY p.sort_order ASC";
        } else {
            return;
        }

        //add items from quote to order
        $data = $read->fetchAll($sql);
        foreach ($data as $item) {
            $productId = $item['product_id'];

            $product = Mage::getModel('catalog/product')->load($productId);
            //observer will check customPrice after add item to card/quote

            $customPrice = $item['owner_cur_price'];

            /** @var Ophirah_Qquoteadv_Model_Qqadvcustomer $_quote */
            $_quote = $this->getQuotationQuote($quoteadvId);
            $quoteStoreId = $_quote->getStoreId();
            $priceContainsTax = Mage::helper('tax')->priceIncludesTax($quoteStoreId);
            if ($priceContainsTax) {
                //fallback for situations where getWebsite doesn't return a object
                if (is_object(Mage::app()->getWebsite(true))) {
                    $store = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStore();
                } else {
                    $store = Mage::app()->getStore('default');
                    $message = 'Mage::app()->getWebsite(true) is not a object, fallback applied';
                    Mage::log('Message: ' . $message, null, 'c2q.log');
                }

                /** @var \Mage_Tax_Model_Calculation $taxCalculation */
                $taxCalculation = Mage::getModel('tax/calculation');
                $customer = $_quote->getCustomer();
                if ($customer) {
                    $taxCalculation->setCustomer($customer);
                }
                $request = $taxCalculation->getRateOriginRequest($store);

                //get tax percent
                $taxClassId = $product->getTaxClassId();
                $percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));

                //Magento needs default store tax here
//                //get user tax on this quote
//                if ($_quote->getCustomerTaxClassId()) {
//                    $userRequest = $taxCalculation->getRateRequest(
//                        $_quote->getShippingAddress(),
//                        $_quote->getBillingAddress(),
//                        $_quote->getCustomerTaxClassId(),
//                        $store
//                    );
//
//                    $percent = $taxCalculation->getRate($userRequest->setProductClassId($taxClassId));
//                }

                $quoteStore = Mage::getModel('core/store')->load($quoteStoreId);
                /** @var \Mage_Tax_Model_Calculation $taxCalculation */
                $taxCalculation = Mage::getModel('tax/calculation');
                $customer = $_quote->getCustomer();
                if ($customer) {
                    $taxCalculation->setCustomer($customer);
                }
                //$request = $taxCalculation->getRateRequest(null, null, null, $quoteStore);
                $request = $taxCalculation->getRateOriginRequest($quoteStore);
                $taxClassId = $product->getTaxClassId();
                $quotePercent = $taxCalculation->getRate($request->setProductClassId($taxClassId));

//                //get user tax on this quote -- only store tax, no user tax yet
//                if ($_quote->getCustomerTaxClassId()) {
//                    $userRequest = $taxCalculation->getRateRequest(
//                        $_quote->getShippingAddress(),
//                        $_quote->getBillingAddress(),
//                        $_quote->getCustomerTaxClassId(),
//                        $_quote->getStore()
//                    );
//
//                    $quotePercent = $taxCalculation->getRate($userRequest->setProductClassId($taxClassId));
//                }

                if ($percent != $quotePercent) {
                    $customPrice = ($customPrice / (100 + $quotePercent)) * (100 + $percent);
                }
            }
            Mage::register('customPrice', $customPrice);

            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                $attr = [];
                $attr[$productId] = @unserialize($item['attribute']);
                $attr[$productId]['qty'] = (float)$item['request_qty'];
                $this->_getOrderCreateModel()->addProducts($attr);
            } else {
                $params = @unserialize($item['attribute']);
                $params['qty'] = (float)$item['request_qty'];

                try {
                    $params = Mage::helper('qquoteadv')->prepareFileOptions($params);
                    $this->_getOrderCreateModel()->addProduct($product, $params);
                } catch (Exception $e) {
                    Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                }
            }

            Mage::unregister('customPrice');
        }
    }

    /**
     * @Description This function echos an article body of Cart2Quote Zendesk help center. @link https://cart2quote.zendesk.com
     * @Return JSON array of articles
     */
    public function requestArticleAction()
    {
        echo Mage::helper('qquoteadv')->executeArticleRequest(); // Get array of articles
    }

    /**
     * Action to convert a quote to an order
     *
     * params ([id] => 64, [q2o_serial] => array(109,110))
     */
    public function convertAction()
    {
        $quoteadvId = $this->getRequest()->getParam('id');
        $requestedItems = $this->getRequest()->getParam('q2o');

        Mage::dispatchEvent('ophirah_qquoteadv_admin_convert_before', [$quoteadvId, $requestedItems]);

        if (empty($requestedItems)) {
            $requestedItems = $this->getRequest()->getParam('q2o_serial');
            if (!empty($requestedItems)) {
                $requestedItems = json_decode(base64_decode($requestedItems));
            }
        }

        if ($requestedItems) {
            foreach ($requestedItems as $k => $v) {
                if (empty($v)) {
                    unset($requestedItems[$k]);
                }
            }
        }

        if (!empty($quoteadvId)) {
            /** @var Ophirah_Qquoteadv_Model_Qqadvcustomer $_quoteadv */
            $_quoteadv = $this->getQuotationQuote($quoteadvId);
            Mage::dispatchEvent(
                'qquoteadv_qqadvcustomer_before_convert',
                [
                    'quote' => $_quoteadv,
                    'requestItems' => $requestedItems
                ]
            );

            $currencyCode = $_quoteadv->getData('currency');
            $storeId = $_quoteadv->getStoreId();
            $this->_getSession()->setStoreId((int)$storeId);

            $customerId = (int)$_quoteadv->getCustomerId();
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $this->_getSession()->setCustomerId($customerId);

            // empty the quote before adding the items
            $this->_getOrderCreateModel()->getQuote()->removeAllItems();

            // get customer address
            $helperAddress = Mage::helper('qquoteadv/address');
            $customerAddresses = $helperAddress->buildQuoteAdresses($_quoteadv);

            $this->_getOrderCreateModel()
                ->getQuote()
                ->setBillingAddress(
                    $helperAddress->getQuoteAddress(
                        $customer,
                        $customerAddresses['billingAddress'],
                        $storeId,
                        Mage_Customer_Model_Address_Abstract::TYPE_BILLING
                    )
                );

            $this->_getOrderCreateModel()
                ->getQuote()
                ->setShippingAddress(
                    $helperAddress->getQuoteAddress(
                        $customer,
                        $customerAddresses['shippingAddress'],
                        $storeId,
                        Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING
                    )
                );

            if ($customerAddresses['billingAddress'] != $customerAddresses['shippingAddress']) {
                $this->_getOrderCreateModel()->getQuote()->getShippingAddress()->setData('same_as_billing', 0);
            } else {
                $this->_getOrderCreateModel()->getQuote()->getShippingAddress()->setData('same_as_billing', 1);
            }

            $this->_getOrderCreateModel()->getQuote()->setCustomerId($customerId);

            //set customer group
            $customerGroupId = (int)$_quoteadv->getCustomerGroupId();
            $this->_getOrderCreateModel()->getQuote()->setCustomerGroupId($customerGroupId);

            //set tax class id
            $customerTaxClassId = (int)$_quoteadv->getData('customer_tax_class_id');
            if($customerTaxClassId){
                $this->_getOrderCreateModel()->getQuote()->setCustomerTaxClassId($customerTaxClassId);
            }

            // Apply Coupon Code
            if ($_quoteadv->getData('salesrule') > 0) {
                $this->_getOrderCreateModel()->applyCoupon(
                    $_quoteadv->getCouponCodeById($_quoteadv->getData('salesrule'))
                );
            }

            // Set Default Shipping Method
            if ($_quoteadv->getAddress()->getShippingMethod() !== null && (int)$_quoteadv->getData('shipping_type')) {
                $this->_getOrderCreateModel()->getQuote()->getShippingAddress()->setData(
                    'shipping_method',
                    $_quoteadv->getAddress()->getData('shipping_method')
                );
                $this->_getOrderCreateModel()->getQuote()->getShippingAddress()->setData('collect_shipping_rates', '1');
            }

            if (count($requestedItems)) {
                //convert quote items to order
                Mage::helper('qquoteadv')->setCurrentCurrency($currencyCode);

                $this->_convertQuoteItemsToOrder($quoteadvId, $requestedItems);
                Mage::getSingleton('adminhtml/session')->setUpdateQuoteId($quoteadvId);
            } else {
                $msg = $this->__('To create an order, select product(s) and quantity');
                Mage::getSingleton('adminhtml/session')->addError($msg);

                if (isset($_SERVER['HTTP_REFERER'])) {
                    $url = $_SERVER['HTTP_REFERER'];
                    $this->_redirectUrl($url);
                    return;
                } else {
                    $this->_redirect('*/*');
                    return;
                }
            }

            //add quoteadv id to the session for the quoteadv shipping option
            Mage::getSingleton('core/session')->proposal_quote_id = $quoteadvId;

            //add quoteadv id to the new order
            $this->_getOrderCreateModel()->getQuote()->setProposalQuoteId($quoteadvId);

            //set the selected shipping method on the sales_flat_quote item
            try {
                $quoteShippingCode = $_quoteadv->getShippingCode();
                if (empty($quoteShippingCode)) {
                    $quoteShippingCode = $_quoteadv->getShippingMethod();
                }

                if (isset($quoteShippingCode) && !empty($quoteShippingCode)) {
                    $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
                    foreach ($methods as $_ccode => $_carrier) {
                        $_methods = $_carrier->getAllowedMethods();
                        if ($_methods) {
                            foreach ($_methods as $_mcode => $_method) {
                                $_code = $_ccode . '_' . $_mcode;
                                if ($_code == $quoteShippingCode) {
                                    $this->_getOrderCreateModel()->setShippingMethod($quoteShippingCode);
                                    $this->_getOrderCreateModel()->getShippingAddress()->requestShippingRates();
                                    break;
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                //in case the shipping method is disabled in the meantime, this could trow an error
                Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
            }

            //set payment
            try {
                $createFromId = $_quoteadv->getCreatedFromQuoteId();
                if (isset($createFromId) && !empty($createFromId)) {
                    $paymetData = Mage::getModel('sales/quote_payment')->getCollection()
                        ->setQuoteFilter($createFromId)
                        ->getFirstItem();

                    if (isset($paymetData) && !empty($paymetData)) {
                        $paymentDataArray = $paymetData->getData();
                        if (!empty($paymentDataArray)) {
                            $this->_getOrderCreateModel()->setPaymentData($paymentDataArray);
                        }
                    } else {
                        $qPaymentMethod = $_quoteadv->getPaymentMethod();
                        if (isset($qPaymentMethod) && !empty($qPaymentMethod)) {
                            $this->_getOrderCreateModel()->setPaymentMethod($qPaymentMethod);
                        }
                    }
                }
            } catch (Exception $e) {
                //in case the payment method is disabled in the meantime, this could trow an error
                Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->unsetData('method');
                $this->_getOrderCreateModel()->getQuote()->removePayment();
            }

            //add redirect ID
            $mageQuoteId = $this->_getOrderCreateModel()->getQuote()->getData('entity_id');
            Mage::helper('qquoteadv')->setReferenceIdInCoreSession($mageQuoteId, $quoteadvId);

            $this->_getOrderCreateModel()
                ->initRuleData()
                ->saveQuote();
            $this->_getOrderCreateModel()->getSession()->setCurrencyId($currencyCode);
            Mage::dispatchEvent(
                'qquoteadv_qqadvcustomer_after_convert',
                ['orderCreateModel' => $this->_getOrderCreateModel()]
            );

            Mage::helper('qquoteadv/logging')->sentAnonymousData('confirm', 'b', $quoteadvId);

            $url = $this->getUrl('adminhtml/sales_order_create/index');
            $this->_redirectUrl($url);

            return;
        } else {
            $this->_redirect('*/*');
        }

        Mage::dispatchEvent('ophirah_qquoteadv_admin_convert_after', [$quoteadvId, $requestedItems]);
    }

    /**
     * Function that adds an error to the session and try's to redirect to a relevant page
     *
     * @param $errorMsg
     * @param null $url
     */
    protected function _redirectErr($errorMsg, $url = null)
    {
        if (is_string($errorMsg)) {
            $errorMsg = [$errorMsg];
        }

        if (count($errorMsg)) {
            foreach ($errorMsg as $msg) {
                Mage::getSingleton('adminhtml/session')->addError($msg);
            }
            if ($url == null) {
                if(isset($_SERVER['HTTP_REFERER'])){
                    $url = $_SERVER['HTTP_REFERER'];
                }
            }

            if($url != null){
                $this->_redirectUrl($url);
            } else {
                $this->_redirect('*/*');
            }
        }
    }

    /**
     * Save customer
     *
     * @param \Mage_Sales_Model_Quote $quote
     */
    protected function _saveCustomerAfterQuote($quote)
    {
        $customer = $quote->getCustomer();
        $store = $quote->getStore();
        $billingAddress = $quote->getBillingAddress()->exportCustomerAddress()->getData();
        if (!$quote->getCustomer()->getId()) {
            $customer = Mage::getModel('qquoteadv/customer_customer');
            $customer->setData($quote->getCustomer()->getData());
            $customer->setPassword($customer->generatePassword());
            $customer->setStore($store);
            $customer->setEmail($quote->getData('customer_email'));
            $customer->setGroupId($quote->getData('customer_group_id'));
            $customer->setFirstname($billingAddress['firstname']);
            $customer->setLastname($billingAddress['lastname']);
            try {
                Mage::dispatchEvent('qquoteadv_qqadvcustomer_before_newCustomer', ['customer' => $customer, 'quote' => $quote]);
                $customer->save();
                Mage::dispatchEvent('qquoteadv_qqadvcustomer_after_newCustomer', ['customer' => $customer, 'quote' => $quote]);
                $customer->sendNewQuoteAccountEmail('registered', '', $customer->getStoreId());
            } catch (Exception $e) {
                $this->_redirectErr([$e->getMessage()]);
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                return;
            }
        }

        // set customer to quote and convert customer data to quote
        $quote->setCustomer($customer);
    }

    /**
     * Alias for an old typo
     */
    public function switch2QuoteAction()
    {
        $this->swith2QuoteAction();
    }

    /**
     * Function that switches an order to a quote
     */
    public function swith2QuoteAction()
    {
        //Check if free user
        if (Mage::helper('qquoteadv/licensechecks')->showFreeUserOptions()) {
            $this->_redirect('*/*');
            return;
        }

        //unique id for c2q session
        $c2qId = Mage::getSingleton('adminhtml/session')->getUpdateQuoteId(); //null;

        //pool error messages
        $errorMsg = [];

        /**
         * quote Data from session
         * @var \Mage_Sales_Model_Quote $quote
         */
        $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();

        //post data from input fields
        $data = $this->getRequest()->getPost();

        Mage::dispatchEvent('ophirah_qquoteadv_admin_swith2Quote_before', [$c2qId, $quote, $data]);

        //created from (magento) quote data
        $createdFromQuote = Mage::getSingleton('adminhtml/session_quote');
        $createdFromQuoteId = $createdFromQuote->getQuoteId();

        $baseToQuoteRate = $quote->getData('base_to_quote_rate');
        $currencyCode = $quote->getData('quote_currency_code');
        $customerId = $quote->getCustomer()->getId();

        if (!$customerId && $quote->getData('customer_email')) {
            $this->_saveCustomerAfterQuote($quote);
            $customerId = $quote->getCustomer()->getId();
        }

        $helperAddress = Mage::helper('qquoteadv/address');
        $billingAddress = $quote->getBillingAddress();
        $shipAddress = $quote->getShippingAddress();
        $customerBillingAddressId = $quote->getBillingAddress()->getCustomerAddressId();
        $saveBilling = $billingAddress->getData('save_in_address_book');
        $customerShipAddressId = $quote->getShippingAddress()->getCustomerAddressId();
        $saveShipping = $shipAddress->getData('save_in_address_book');
        $shippingAsBilling = $data['shipping_as_billing'];

        //Save or update customer billing address
        if (($saveBilling == 1) && ($customerBillingAddressId == '0')) {
            $customerAddress = $helperAddress->addCustomerAddress($customerId, $billingAddress->exportCustomerAddress()->getData());
            $customerAddressId = $customerAddress->getEntityId();
            if ($customerAddressId) {
                $billingAddress->setCustomerAddressId($customerAddressId);
                if ($shippingAsBilling == 1) {
                    $shipAddress->setCustomerAddressId($customerAddressId);
                }
            }
        } elseif ($customerBillingAddressId > 0 && $saveBilling == 1) {
            $helperAddress->updateCustomerAddress($customerId, $customerBillingAddressId, $billingAddress->exportCustomerAddress()->getData());
        }

        //Save or update customer shipping address
        if ($saveShipping == 1 && $customerShipAddressId == '0') {
            $customerAddress = $helperAddress->addCustomerAddress($customerId, $shipAddress->exportCustomerAddress()->getData());
            $customerAddressId = $customerAddress->getEntityId();
            if ($customerAddressId) {
                $shipAddress->setCustomerAddressId($customerAddressId);
            }
        } elseif ($customerShipAddressId > 0 && $saveShipping == 1) {
            $helperAddress->updateCustomerAddress($customerId, $customerShipAddressId, $shipAddress->exportCustomerAddress()->getData());
        }

        if ($quote->getData('customer_email') != $quote->getCustomer()->getEmail()) {
            try {
                $email = $quote->getData('customer_email');
                $customer = Mage::getModel('qquoteadv/customer_customer');
                $customer->setData($quote->getCustomer()->getData());
                $customer->setEmail($email);

                Mage::dispatchEvent(
                    'qquoteadv_qqadvcustomer_before_newCustomer',
                    [
                        'customer' => $customer,
                        'quote' => $quote
                    ]
                );

                $customer->save();

                Mage::dispatchEvent(
                    'qquoteadv_qqadvcustomer_after_newCustomer',
                    [
                        'customer' => $customer,
                        'quote' => $quote
                    ]
                );

                $customer->sendNewQuoteAccountEmail('registered', '', $customer->getStoreId());
            } catch (Exception $e) {
                $this->_redirectErr([$e->getMessage()]);
                Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                return;
            }
        } else {
            $email = $quote->getCustomer()->getEmail();
        }

        $items = $quote->getAllItems();

        if (!Mage::getStoreConfig('qquoteadv_general/quotations/enabled', $quote->getStoreId())) {
            $errorMsg[] = $this->__("Module Ophirah_Qquoteadv / Cart2Quote is disabled, please enable it in System>Configuration>Cart2Quote>General");
        }

        if (empty($customerId)) {
            $errorMsg[] = $this->__("Customer not recognized for new quote");
        } else {
            if (!Mage::getStoreConfig('customer/account_share/scope',  $quote->getStoreId()) == 0) {
                $currentModel = Mage::getModel('customer/customer')->load($customerId);
                if ($currentModel) {
                    $websiteId = $currentModel->getWebsiteId();
                    $website = Mage::getModel('core/website')->load($websiteId);
                    $storeIds = $website->getStoreIds();

                    if(!in_array($quote->getStoreId(), $storeIds)) {
                        $errorMsg[] = $this->__("The selected customer is not linked to the current store, Please set 'System>Configuration>Customers>Customer Configuration>Account Sharing Options>Share Customer Accounts' to global");
                    }
                }
            }
        }

        if (empty($email)) {
            $errorMsg[] = $this->__("Customer's email was undefined");
        }

        if (!count($items)) {
            $errorMsg[] = $this->__("There was an error, because the product quantities were not defined");
        }

        foreach ($items as $item) {
            // Simple child products from configurable
            // needs to be checked with qty of the parent item
            // Check if product is a configurable product
            $checkConfigurable = Mage::helper('qquoteadv')->isConfigurable($item, $item->getData('qty'));
            if ($checkConfigurable != false) {
                $qty = $checkConfigurable;
            } else {
                $qty = $item->getData('qty');
            }

            // Bundled products need to be checked,
            // including child products
            if ($item->getData('product')->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                $bundleOptions = Mage::getModel('qquoteadv/bundle')->getOrderOptions($item->getData('product'));
                $itemChildren = Mage::getModel('qquoteadv/qqadvproduct')->getBundleOptionProducts($item->getData('product'), $bundleOptions['info_buyRequest']);
            }

            // Creating ChildProducts array
            // in case product has a product type Bundle
            // All childproducts need to be checked
            $checkQty = $qty;
            $checkProductArray = [];
            // Parent product gets added first
            $checkProductArray[] = $item->getData('product');
            if (isset($itemChildren)) {
                $checkProductArray = array_merge($checkProductArray, $itemChildren);
            }

            // Cycle through childproducts
            foreach ($checkProductArray as $checkProduct) {
                if ($checkProduct->getId() == $item->getData('product')->getId()) {
                    if ($checkProduct->getQuoteItemQty()) {
                        $checkQty = $checkProduct->getQuoteItemQty();

                        if (is_array($checkQty)) {
                            //bad way of getting the first value of the array
                            foreach ($checkQty as $qtyValue) {
                                $checkQty = $qtyValue;
                                break;
                            }
                        }
                    }

                    $check = Mage::helper('qquoteadv')->isQuoteable($item->getData('product'), $checkQty);
                }
            }

            if (isset($check) && $check->getHasErrors()) {
                $errors = $check->getErrors();
                $errorMsg = array_merge($errorMsg, $errors);
            }
        }

        Mage::getSingleton('adminhtml/session')->setConfParent();

        //#return back in case any error found
        if (count($errorMsg)) {
            $this->_redirectErr($errorMsg);
            return;
        }

        //#c2q insert data
        if ($customerId && $email) {
            $modelCustomer = Mage::getModel('qquoteadv/qqadvcustomer');
            $copyShippingParams = [
                'shipping_amount' => 'shipping_amount',
                'base_shipping_amount' => 'base_shipping_amount',
                'shipping_amount_incl_tax' => 'shipping_amount_incl_tax',
                'base_shipping_amount_incl_tax' => 'base_shipping_amount_incl_tax',
                'base_shipping_tax_amount' => 'base_shipping_tax_amount',
                'shipping_tax_amount' => 'shipping_tax_amount',
                'address_shipping_method' => 'shipping_method',
                'address_shipping_description' => 'shipping_description',
            ];

            $shipRates = $shipAddress->getShippingRatesCollection();

            $copyRateParams = [];
            $rate = null;
            foreach ($shipRates as $rates) {
                if ($rates['code'] == $shipAddress->getShippingMethod()) {
                    $rate = $rates;
                    $copyRateParams = [
                        'shipping_method' => 'method',
                        'shipping_description' => 'method_description',
                        'shipping_method_title' => 'method_title',
                        'shipping_carrier' => 'carrier',
                        'shipping_carrier_title' => 'carrier_title',
                        'shipping_code' => 'code'
                    ];
                    break;
                }
            }

            $shipStreet = "";
            $billStreet = "";
            $shipAddressExists = false;
            foreach ($shipAddress->getStreet() as $addressLine) {
                if ($addressLine != "") {
                    $shipAddressExists = true;
                }
            }

            $billAddressExists = false;
            foreach ($billingAddress->getStreet() as $addressLine) {
                if ($addressLine != "") {
                    $billAddressExists = true;
                }
            }

            if ($shipAddressExists) {
                $shipStreet = implode(PHP_EOL, $shipAddress->getStreet());
            }

            if ($billAddressExists) {
                $billStreet = implode(PHP_EOL, $billingAddress->getStreet());
            }

            //Add customer Group
            $customerGroup = $quote->getCustomer()->getData('group_id');
            //If customer group is set in quote replace this with the default value.
            if (array_key_exists('order', $data)) {
                if (array_key_exists('account', $data['order'])) {
                    if (array_key_exists('group_id', $data['order']['account'])) {
                        $customerGroup = $data['order']['account']['group_id'];
                    }
                }
            }

            //add customer tax class id
            $customerTaxClassId = Mage::getModel('customer/group')->getTaxClassId($customerGroup);

            if (!$c2qId) {
                $name = $billingAddress->getFirstname();
                if ($name != "") { // &&  count($quote->getCustomer()->getAddresses()) ){
                    /* @var Ophirah_Qquoteadv_Helper_Data $helper */
                    $helper = Mage::helper('qquoteadv');

                    /* @var Mage_Admin_Model_Session $admin */
                    $admin = Mage::getSingleton('admin/session');

                    $itemPrice = (Mage::getStoreConfig('qquoteadv_quote_configuration/proposal/itemprice') == 1) ? 1 : 0;
                    $billAdrId = $billingAddress->getCustomerAddressId() > 0  ? $billingAddress->getCustomerAddressId() : null;
                    $shipAdrId = $shipAddress->getCustomerAddressId() > 0 ? $shipAddress->getCustomerAddressId() : null;

                    $quoteCustomer = [
                        'created_at' => now(),
                        'updated_at' => now(),

                        'customer_id' => $customerId,
                        'currency' => $currencyCode,
                        'base_to_quote_rate' => $baseToQuoteRate,
                        'prefix' => $billingAddress->getPrefix(),
                        'firstname' => $billingAddress->getFirstname(),
                        'middlename' => $billingAddress->getMiddlename(),
                        'lastname' => $billingAddress->getLastname(),
                        'suffix' => $billingAddress->getSuffix(),
                        'company' => $billingAddress->getCompany(),
                        'email' => $email,
                        'country_id' => $billingAddress->getCountryId(),
                        'region' => $billingAddress->getRegion(),
                        'region_id' => $billingAddress->getRegionId(),
                        'city' => $billingAddress->getCity(),
                        'address' => $billStreet,
                        'postcode' => $billingAddress->getPostcode(),
                        'telephone' => $billingAddress->getTelephone(),
                        'fax' => $billingAddress->getFax(),
                        'store_id' => $quote->getStoreId(),
                        'itemprice' => $itemPrice,
                        'customer_address_id' => $billAdrId,

                        'vat_id' => $billingAddress->getData('vat_id'),
                        'vat_is_valid' => $billingAddress->getData('vat_is_valid'),
                        'vat_request_id' => $billingAddress->getData('vat_request_id'),
                        'vat_request_data' => $billingAddress->getData('vat_request_data'),
                        'vat_request_success' => $billingAddress->getData('vat_request_success'),

                        //#shipping
                        'shipping_prefix' => $shipAddress->getData("prefix"),
                        'shipping_firstname' => $shipAddress->getData("firstname"),
                        'shipping_middlename' => $shipAddress->getData("middlename"),
                        'shipping_lastname' => $shipAddress->getData("lastname"),
                        'shipping_suffix' => $shipAddress->getData("suffix"),
                        'shipping_company' => $shipAddress->getData("company"),
                        'shipping_country_id' => $shipAddress->getData("country_id"),
                        'shipping_region' => $shipAddress->getData("region"),
                        'shipping_region_id' => $shipAddress->getData("region_id"),
                        'shipping_city' => $shipAddress->getData("city"),
                        'shipping_address' => $shipStreet,
                        'shipping_postcode' => $shipAddress->getData("postcode"),
                        'shipping_telephone' => $shipAddress->getData("telephone"),
                        'shipping_fax' => $shipAddress->getData("fax"),

                        'shipping_vat_id' => $shipAddress->getData('vat_id'),
                        'shipping_vat_is_valid' => $shipAddress->getData('vat_is_valid'),
                        'shipping_vat_request_id' => $shipAddress->getData('vat_request_id'),
                        'shipping_vat_request_data' => $shipAddress->getData('vat_request_data'),
                        'shipping_vat_request_success' => $shipAddress->getData('vat_request_success'),
                        'shipping_customer_address_id' => $shipAdrId,

                        'created_from_quote_id' => $createdFromQuoteId,
                        'customer_group_id' => $customerGroup,
                        'shipping_customer_group_id' => $customerGroup,
                        'customer_tax_class_id' => $customerTaxClassId

                    ];

                    // Assigning SalesRep
                    $modelCustomer->setData($quoteCustomer);
                    $quoteCustomer['user_id'] = $helper->getExpectedQuoteAdminId($modelCustomer, $admin->getUserId(), true);

                    foreach ($copyShippingParams as $key) {
                        $quoteCustomer[$key] = $shipAddress->getData($key);
                    }

                    foreach ($copyRateParams as $key => $value) {
                        $quoteCustomer[$key] = $rate[$value];
                    }

                    //#add customer to c2q
                    try {
                        $c2qId = $modelCustomer->addQuote($quoteCustomer)->getQuoteId();

                        //#save c2q id into session
                        $this->getCustomerSession()->setQuoteadvId($c2qId);
                    } catch (Exception $e) {
                        Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                    }
                } else {
                    $errorMsg[] = $this->__("There was an error, because the customer address was undefined");
                }
            } else {
                //$c2qId is given
                $this->getCustomerSession()->setQuoteadvId($c2qId);
                $shipStreet = implode(PHP_EOL, $shipAddress->getStreet());
                $billingStreet = implode(PHP_EOL, $billingAddress->getStreet());
                $params = [];
                $params['currency'] = $currencyCode;

                $addressData = Mage::helper('qquoteadv/address')->addressFieldsArray();
                foreach ($addressData as $key) {
                    // Setting params to fix street/address naming issue
                    if ($key != 'address') {
                        $params[$key] = $billingAddress->getData($key);
                        $params["shipping_" . $key] = $shipAddress->getData($key);
                    }
                }

                foreach ($copyShippingParams as $key => $value) {
                    $params[$key] = $shipAddress->getData($value);
                }

                foreach ($copyRateParams as $key => $value) {
                    $params[$key] = $rate[$value];
                }

                // Setting params to fix street/address naming issue
                $params['address'] = $billingStreet;
                $params['shipping_address'] = $shipStreet;

                $params['shipping_email'] = $email;
                $params['email'] = $email;

                //created_from_quote_id
                $params['created_from_quote_id'] = $createdFromQuoteId;
                $params['customer_group_id'] = $customerGroup;
                $params['customer_tax_class_id'] = $customerTaxClassId;

                if (count($params) > 0) {
                    try {
                        $modelCustomer = Mage::getModel('qquoteadv/qqadvcustomer')->updateQuote($c2qId, $params);
                        $modelCustomer->updateAddress();
                    } catch (Exception $e) {
                        Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                    }
                }
            }

            //#return back in case any error found
            if (count($errorMsg)) {
                $this->_redirectErr($errorMsg);
                return;
            }

            //#parse in case quote has items
            $quoteCollection = Mage::getModel('qquoteadv/qqadvproduct');
            $quoteProductAdded = [];

            //CHECK Compare order to quote to see what needs to be added or updated.
            $handledRequestIds = [];
            foreach ($quote->getAllVisibleItems() as $item) {
                Mage::dispatchEvent(
                    'ophirah_qquoteadv_admin_swith2Quote_additem_before',
                    [
                        'quote_item' => $item,
                        'qqadvcustomer_id' => $c2qId
                    ]
                );

                $product = $item->getProduct();

                $taxStoreConfig = Mage::helper('tax')->priceIncludesTax($quote->getStoreId());

                $quoteTax = 0;
                $customerTax = 0;
                $percent = 0;
                $quoteCustomerTaxDifferent = false;
                $itemBasePriceInclTax = $item->getBasePriceInclTax();
                if ($taxStoreConfig) {
                    /** @var Mage_Catalog_Model_Product $product */
                    $product = Mage::getModel('catalog/product')
                        ->setStoreId($quote->getStoreId())
                        ->load($item->getProduct()->getId());

                    /** @var \Mage_Tax_Model_Calculation $taxCalculation */
                    $taxCalculation = Mage::getModel('tax/calculation');

                    $customer = $quote->getCustomer();
                    if ($customer) {
                        $taxCalculation->setCustomer($customer);
                    }

                    $request = $taxCalculation->getRateOriginRequest($quote->getStore());

                    //get tax percent
                    $taxClassId = $product->getTaxClassId();
                    $percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));
                    $quoteTax = $percent;

                    //get user tax
                    $customerTaxClass = null;
                    if ($quote->getCustomerTaxClassId()) {
                        $userRequest = $taxCalculation->getRateRequest(
                            $quote->getShippingAddress(),
                            $quote->getBillingAddress(),
                            $quote->getCustomerTaxClassId(),
                            $quote->getStore()
                        );

                        $percent = $taxCalculation->getRate($userRequest->setProductClassId($taxClassId));
                        $customerTax = $percent;
                    }

                    if ($quoteTax != $customerTax) {
                        $quoteCustomerTaxDifferent = true;
                        $itemBasePriceInclTax = ($itemBasePriceInclTax / (100 + $customerTax)) * (100 + $quoteTax);
                    }
                }

                if ($item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    $original_price = Mage::getModel('bundle/product_price')->getFinalPrice(1, $item->getProduct());

                    if ($taxStoreConfig) {
                        $price = $itemBasePriceInclTax;
                    } elseif ($item->getProduct()->getPriceType() == 0) {
                        $price = $item->getPrice();
                    } else {
                        $price = $item->getBasePrice();
                    }
                } else {
                    //no need to reload the product, it is already reloaded at #2746
//                    $product = Mage::getModel('catalog/product')
//                        ->setStoreId($quote->getStoreId())
//                        ->load($item->getProduct()->getId());

                    $price = $product->getPrice();

                    if ($taxStoreConfig) {
                        if (Mage::getStoreConfig('qquoteadv_advanced_settings/backend/force_original_product_price')) {
                            $original_price = $price;
                        } else {
                            $original_price = $itemBasePriceInclTax;
                        }
                    } else {
                        if (Mage::getStoreConfig('qquoteadv_advanced_settings/backend/force_original_product_price')) {
                            $original_price = $price;
                        } else {
                            $original_price = $item->getBasePrice();
                        }
                    }
                }

                // Only Custom Prices needs to be recalculated by currency rate
                if ($item->getOriginalCustomPrice()) {
                    $customPrice = $item->getOriginalCustomPrice();
                } else {
                    if ($taxStoreConfig) {
                        $customPrice = $itemBasePriceInclTax * $baseToQuoteRate;
                    } elseif ($item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                        && $item->getProduct()->getPriceType() == 0) {
                        $customPrice = $item->getPrice() * $baseToQuoteRate;
                    } else {
                        $customPrice = $item->getBasePrice() * $baseToQuoteRate;
                    }

                    $magentoPrecision = 2; //Yes, we round to 2, as Magento doesn't support more in their tax calculator
                    $customPrice = round($customPrice, $magentoPrecision);
                }

                $basePrice = $customPrice / $baseToQuoteRate;
                if (isset($customPrice) && !empty($customPrice)) {
                    $price = $customPrice;
                }
                $orgPrice = $original_price;
                $orgCurPrice = $orgPrice * $baseToQuoteRate;

                if ($item->getBaseDiscountAmount() > 0) {
                    $item->setNoDiscount(0);
                    $useDiscount = 1;
                } else {
                    $item->setNoDiscount(1);
                    $useDiscount = 0;
                }

                $qqadvproductId = $quoteCollection->getIdByQuoteAndProduct($item, $c2qId);
                if ($qqadvproductId) {
                    $qqadvproduct = Mage::getModel('qquoteadv/qqadvproduct')->load($qqadvproductId);
                    Mage::dispatchEvent(
                        'ophirah_qquoteadv_admin_swith2Quote_update_qqadvproduct_before',
                        [
                            'quote_item' => $item,
                            'qqadvcustomer_id' => $c2qId,
                            'qqadvproduct' => $qqadvproduct
                        ]
                    );

                    //todo: check quoteadv_product_id? maybe in this case not.
                    $request_items = Mage::getModel('qquoteadv/requestitem')->getCollection()
                        ->addFieldToFilter('quote_id', $qqadvproduct->getQuoteId())
                        ->addFieldToFilter('product_id', $qqadvproduct->getProductId())
                        ->addFieldToFilter('request_qty', $qqadvproduct->getQty());

                    //exclude items that are already handled
                    foreach($handledRequestIds as $handledRequestId){
                        $request_items->addFieldToFilter('request_id', ['neq' => $handledRequestId]);
                    }

                    $request_item = $request_items->getFirstItem();
                    if ($request_item->getId()) {
                        $handledRequestIds[] = $request_item->getId();
                        $attribute = unserialize($qqadvproduct->getAttribute());

                        $superAttribute = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                        if (isset($superAttribute['info_buyRequest'])) {
                            if (isset($superAttribute['info_buyRequest']['super_attribute'])) {
                                $attribute['super_attribute'] = $superAttribute['info_buyRequest']['super_attribute'];
                            }
                        }
                        if (isset($superAttribute['options'])) {
                            $qqadvproduct->setOptions(serialize($superAttribute['options']));
                            $qqadvproduct->setHasOption(1);
                        }

                        $attribute['qty'] = $item->getQty();
                        $qqadvproduct->setAttribute(serialize($attribute));
                        $qqadvproduct->setQty($item->getQty());
                        $qqadvproduct->setUseDiscount($useDiscount);

                        try {
                            Mage::dispatchEvent(
                                'ophirah_qquoteadv_admin_swith2Quote_update_qqadvproduct_save_before',
                                [
                                    'quote_item' => $item,
                                    'qqadvcustomer_id' => $c2qId,
                                    'qqadvproduct' => $qqadvproduct
                                ]
                            );

                            $qqadvproduct->save();

                            Mage::dispatchEvent(
                                'ophirah_qquoteadv_admin_swith2Quote_update_qqadvproduct_save_after_success',
                                [
                                    'quote_item' => $item,
                                    'qqadvcustomer_id' => $c2qId,
                                    'qqadvproduct' => $qqadvproduct
                                ]
                            );
                        } catch (Exception $e) {
                            Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                            Mage::dispatchEvent(
                                'ophirah_qquoteadv_admin_swith2Quote_update_qqadvproduct_save_after_fail',
                                [
                                    'quote_item' => $item,
                                    'qqadvcustomer_id' => $c2qId,
                                    'qqadvproduct' => $qqadvproduct,
                                    'exception' => $e
                                ]
                            );
                        }

                        $request_item->setOwnerBasePrice($basePrice);
                        $request_item->setOwnerCurPrice($price);
                        $request_item->setOriginalPrice($orgPrice);
                        $request_item->setOriginalCurPrice($orgCurPrice);
                        $request_item->setRequestQty($item->getQty());
                        try {
                            Mage::dispatchEvent(
                                'ophirah_qquoteadv_admin_swith2Quote_update_requestitem_save_before',
                                [
                                    'quote_item' => $item,
                                    'qqadvcustomer_id' => $c2qId,
                                    'qqadvproduct' => $qqadvproduct
                                ]
                            );

                            $request_item->save();

                            Mage::dispatchEvent(
                                'ophirah_qquoteadv_admin_swith2Quote_update_requestitem_save_after_success',
                                [
                                    'quote_item' => $item,
                                    'qqadvcustomer_id' => $c2qId,
                                    'qqadvproduct' => $qqadvproduct
                                ]
                            );
                        } catch (Exception $e) {
                            Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                            Mage::dispatchEvent(
                                'ophirah_qquoteadv_admin_swith2Quote_update_requestitem_save_after_fail',
                                [
                                    'quote_item' => $item,
                                    'qqadvcustomer_id' => $c2qId,
                                    'qqadvproduct' => $qqadvproduct,
                                    'exception' => $e
                                ]
                            );
                        }
                    }

                    Mage::dispatchEvent(
                        'ophirah_qquoteadv_admin_swith2Quote_update_qqadvproduct_after',
                        [
                            'quote_item' => $item,
                            'qqadvcustomer_id' => $c2qId,
                            'qqadvproduct' => $qqadvproduct
                        ]
                    );

                    $quoteProductAdded[] .= $qqadvproductId;
                } else {
                    $superAttribute = $item->getProduct()
                        ->getTypeInstance(true)
                        ->getOrderOptions($item->getProduct());

                    $optionalAttrib = '';
                    if (isset($superAttribute['info_buyRequest'])) {
                        if (isset($superAttribute['info_buyRequest']['uenc'])) {
                            unset($superAttribute['info_buyRequest']['uenc']);
                        }

                        $superAttribute['info_buyRequest']['product'] = $item->getData('product_id');
                        $superAttribute['info_buyRequest']['qty'] = $item->getQty();

                        $optionalAttrib = serialize($superAttribute['info_buyRequest']);
                    }

                    $params = [
                        'product_id' => $item->getProductId(),
                        'qty' => $item->getQty(),
                        'price' => $price,
                        'custom_price' => $customPrice,
                        'original_price' => $original_price,
                        'base_quote_rate' => $baseToQuoteRate,
                        'use_discount' => $useDiscount
                    ];

                    $qqadvproduct = $this->_create($params, $optionalAttrib);

                    $latestId = max($quoteCollection->getIdsByQuoteId($c2qId));
                    $quoteProductAdded[] .= $latestId;
                }

                Mage::dispatchEvent(
                    'ophirah_qquoteadv_admin_swith2Quote_additem_after',
                    [
                        'quote_item' => $item,
                        'qqadvcustomer_id' => $c2qId,
                        'qqadvproduct' => $qqadvproduct
                    ]
                );
            }

            //remove unwanted products (removes by id, that could be an issue)
            foreach ($quoteCollection->getCollection()->addFieldToFilter('quote_id', $c2qId) as $qItem) {
                $delete = true;
                foreach ($quoteProductAdded as $id) {
                    if ($id == $qItem->getId()) {
                        $delete = false;
                        break;
                    }
                }
                if ($delete) {
                    Mage::getModel('qquoteadv/qqadvproduct')->deleteQuote($qItem->getId());
                }
            }

            //#update c2q status to make visible c2q request
            try {
                $modelCustomer->load($c2qId);
                $modelCustomer->setIsQuote(1);

                //#for new quote we need correct increment id
                // And set a status
                if (!Mage::getSingleton('adminhtml/session')->getUpdateQuoteId()) {
                    $modelCustomer->setIncrementId(Mage::getModel('qquoteadv/entity_increment_numeric')
                        ->getNextId($modelCustomer->getStoreId()));
                    $modelCustomer->setStatus(Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_BEGIN);
                }

                // Add create hash
                $modelCustomer->setCreateHash(Mage::helper('qquoteadv/license')
                    ->getCreateHash($modelCustomer->getIncrementId()));

                //# Add applied SalesRule
                if ($quote->getData('applied_rule_ids')) {
                    $code = null;
                    if (is_string($quote->getData('applied_rule_ids'))) {
                        $code = $quote->getData('coupon_code');
                    }

                    if ($code != null) {
                        $modelCustomer->setData('salesrule', $quote->getData('applied_rule_ids'));
                    }
                }

                // Update Address data
                // TODO: create a better way to store all this data
                $updateArray = [
                    'vat_id',
                    'vat_is_valid',
                    'vat_request_id',
                    'vat_request_date',
                    'vat_request_success'
                ];

                foreach ($updateArray as $updateValue) {
                    $modelCustomer->setData($updateValue, $billingAddress->getData($updateValue));
                    $modelCustomer->setData('shipping_' . $updateValue, $shipAddress->getData($updateValue));
                }

                if ($shipAddress->getData('same_as_billing') && isset($updateValue)) {
                    $modelCustomer->setData($updateValue, $shipAddress->getData('same_as_billing'));
                }

                // Shipping Method
                if ($shipAddress->getShippingMethod()) {
                    //$modelCustomer->setData('shipping_method',      $shipAddress->getShippingMethod());
                    $shippingCodeParts = explode("_", $shipAddress->getShippingMethod());
                    if (isset($shippingCodeParts[1])) {
                        $shippingCodeMethod = $shippingCodeParts[1];
                    } else {
                        $shippingCodeMethod = $shipAddress->getShippingMethod();
                    }

                    $modelCustomer->setData('shipping_method',              $shippingCodeMethod);
                    $modelCustomer->setData('shipping_code',                $shipAddress->getShippingMethod());
                    $modelCustomer->setData('shipping_description',         $shipAddress->getShippingDescription());
                    $modelCustomer->setData('address_shipping_method',      $shipAddress->getShippingMethod());
                    $modelCustomer->setData('address_shipping_description', $shipAddress->getShippingDescription());
                    $modelCustomer->setData('base_shipping_amount',         $shipAddress->getBaseShippingAmount());
                    $modelCustomer->setData('weight',                       $shipAddress->getWeight());
                }

                // Payment Method
                if (isset($data['payment']) && isset($data['payment']['method'])) {
                    $paymentMethod = $data['payment']['method'];
                    $modelCustomer->setPaymentMethod($paymentMethod);
                }
                // call getPayment just to trigger some compatibility actions
                $quote->getPayment();

                //prepare for save
                $modelCustomer->getShippingAddress()->requestShippingRates(); //generate shipping prices
                $modelCustomer->updateAddress();
                $modelCustomer->collectTotals();

                // Save data
                Mage::dispatchEvent(
                    'qquoteadv_qqadvcustomer_beforesave_final',
                    ['quote' => $modelCustomer]
                );

                $modelCustomer->save();

                Mage::dispatchEvent(
                    'qquoteadv_qqadvcustomer_aftersave_final',
                    ['quote' => $modelCustomer]
                );

                Mage::helper('qquoteadv/logging')->sentAnonymousData('request', 'b', $modelCustomer->getData('quote_id'));
            } catch (Exception $e) {
                Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
            }

            // Clear session quote data
            Mage::getSingleton('adminhtml/session_quote')->clear();
        }

        Mage::getSingleton('adminhtml/session')->setUpdateQuoteId(null);

        if ($c2qId) {
            $this->_redirect('adminhtml/qquoteadv/edit', ['id' => $c2qId]);
        } else {
            $this->_redirect('*/*');
        }

        Mage::dispatchEvent('ophirah_qquoteadv_admin_swith2Quote_after', [$c2qId, $quote, $data]);
    }

    /**
     * Get customer session data
     */
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Insert quote data
     * $params = array(
     * 'product' => $item->getProductId(),
     * 'qty'     => $item->getQty(),
     * 'price'   => $item->getPrice()
     * 'original_price' => $item->getProduct()->getPrice();
     * );
     * @param $params
     * @param string $superAttribute
     * @return bool
     */
    private function _create($params, $superAttribute)
    {
        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');

        $hasOption = 0;
        $options = '';
        if (isset($params['options'])) {
            $options = serialize($params['options']);
            $hasOption = 1;
        } elseif (isset($superAttribute)) {
            $attr = unserialize($superAttribute);

            if (isset($attr['options'])) {
                $options = serialize($attr['options']);
                $hasOption = 1;
                $params['qty'] = $attr['qty'];
            }
        }

        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        $qproduct = [
            'quote_id' => $quoteId,
            'product_id' => $params['product_id'],
            'qty' => $params['qty'],
            'attribute' => $superAttribute,
            'has_options' => $hasOption,
            'options' => $options,
            'use_discount' => $params['use_discount'],
            'store_id' => Mage::getSingleton('adminhtml/session_quote')->getStoreId() //$this->getCustomerSession()->getStoreId()
        ];

        // Get Currency rate
        $rate = (isset($params['base_quote_rate'])) ? $params['base_quote_rate'] : 1;

        // Defining Prices
        $basePrice = $params['custom_price'] / $rate;
        $price = $params['custom_price'];
        $orgPrice = $params['original_price'];
        $orgCurPrice = $orgPrice * $rate;
        $mageProduct = Mage::getModel('catalog/product')->load($params['product_id']);
        try {
            Mage::dispatchEvent('ophirah_qquoteadv_admin_swith2Quote_add_qqadvproduct_save_before', [
                    'quote_item' => $mageProduct,
                    'qqadvcustomer_id' => $quoteId]
            );
            $obj = $modelProduct->addProduct($qproduct);
            Mage::dispatchEvent('ophirah_qquoteadv_admin_swith2Quote_add_qqadvproduct_save_after_success', [
                    'quote_item' => $mageProduct,
                    'qqadvcustomer_id' => $quoteId,
                    'qqadvproduct' => $obj]
            );
        } catch (Exception $e) {
            Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
            Mage::dispatchEvent('ophirah_qquoteadv_admin_swith2Quote_add_qqadvproduct_save_after_fail', [
                    'quote_item' => $mageProduct,
                    'qqadvcustomer_id' => $quoteId,
                    'exception' => $e]
            );
        }

        if (isset($obj) && ($obj instanceof Ophirah_Qquoteadv_Model_Qqadvproduct) && $obj->getId()) {
            try {
                $requestData = [
                    'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                    'product_id' => $params['product_id'],
                    'request_qty' => $params['qty'],
                    'owner_base_price' => $basePrice,
                    'owner_cur_price' => $price,
                    'original_price' => $orgPrice,
                    'original_cur_price' => $orgCurPrice,
                    'quoteadv_product_id' => $obj->getId()
                ];
                Mage::dispatchEvent('ophirah_qquoteadv_admin_swith2Quote_add_requestData_save_before', [
                        'quote_item' => $mageProduct,
                        'qqadvcustomer_id' => $quoteId]
                );
                Mage::getModel('qquoteadv/requestitem')->setData($requestData)->save();
                Mage::dispatchEvent('ophirah_qquoteadv_admin_swith2Quote_add_requestData_save_after_success', [
                        'quote_item' => $mageProduct,
                        'qqadvcustomer_id' => $quoteId]
                );
            } catch (Exception $e) {
                Mage::log('Exception: ' . $e->getMessage(), null, 'c2q_exception.log', true);
                Mage::dispatchEvent('ophirah_qquoteadv_admin_swith2Quote_add_requestData_save_after_fail', [
                        'quote_item' => $mageProduct,
                        'qqadvcustomer_id' => $quoteId]
                );
            }
            return $obj;
        } else {
            return false;
        }
    }

    /**
     * Get core session data
     */
    public function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * Action for the delete qty button in the quote edit page
     */
    public function deleteQtyFieldAction()
    {
        $requestId = (int)$this->getRequest()->getParam('request_id');
        $c2qId = null;
        if (empty($requestId)) {
            $this->_redirect('*/*/*');
        }

        $item = Mage::getModel('qquoteadv/requestitem')->load($requestId);
        $c2qId = $item->getData('quote_id');
        Mage::dispatchEvent('ophirah_qquoteadv_admin_deleteQtyField_before', [$requestId, $c2qId]);

        $_quote = Mage::getSingleton('qquoteadv/qqadvcustomer')->load($c2qId);

        $quoteProductId = $item->getData('quoteadv_product_id');
        $listRequests = Mage::getModel('qquoteadv/requestitem')->getCollection()->setQuote($_quote);
        $listRequests->addFieldToFilter('quoteadv_product_id', $quoteProductId);
        $size = $listRequests->getSize();

        if ($size > 1) {
            try {
                $item->delete();
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            }
        } else {
            $msg = $this->__('Minimum of one Qty is required');
            Mage::getSingleton('adminhtml/session')->addError($msg);
        }

        $this->_redirect('*/*/edit', ['id' => $c2qId]);
        Mage::dispatchEvent('ophirah_qquoteadv_admin_deleteQtyField_after', [$requestId, $c2qId]);
    }

    /**
     * Action for the add qty button on the edit quote page
     *
     * @return null
     */
    public function addQtyFieldAction()
    {
        $quoteProductId = (int)$this->getRequest()->getParam('quote_product_id');
        $quoteProduct = Mage::getModel('qquoteadv/qqadvproduct')->load($this->getRequest()->getParam('quote_product_id'));
        $product = Mage::getModel('catalog/product')->load($quoteProduct->getData('product_id'));

        Mage::dispatchEvent('ophirah_qquoteadv_admin_addQtyField_before', [$quoteProductId]);

        // For configurable product, use the simple product
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $attribute = $quoteProduct->getAttribute();
            if (!is_array($attribute)) {
                $attribute = unserialize($attribute);
            }
            $prod_simple = Mage::getModel('catalog/product_type_configurable')->getProductByAttributes($attribute['super_attribute'], $product);
            $check_prod = $prod_simple;
        } else {
            $check_prod = $product;
        }

        $requestQty = $this->getRequest()->getParam('request_qty');
        $c2qId = $this->getRequest()->getParam('quoteadv_id');

        if(is_array($requestQty)){
            //bad way of getting the first value of the array
            foreach($requestQty as $qtyValue){
                $requestQty = $qtyValue;
                break;
            }
        }

        //echo 'add qty field: ';
        $check = Mage::helper('qquoteadv')->isQuoteable($check_prod, $requestQty);
        if ($check->getHasErrors()) {
            $errors = $check->getErrors();
            $this->_redirectErr($errors);
            return null;
        }

        $productId = null;

        if (empty($quoteProductId) || empty($requestQty)) {
            $errorMsg = Mage::helper('checkout')->__("Invalid data.");
            Mage::getSingleton('adminhtml/session')->addError($errorMsg);

            if (!empty($c2qId)) {
                return $this->_redirect('*/*/edit', ['id' => $c2qId]);
            } else {
                return $this->_redirect('*/*/');
            }
        }

        //#SEARCH ORIGINAL PRICE
        $_quote = Mage::getSingleton('qquoteadv/qqadvcustomer')->load($c2qId);

        $_collection = Mage::getModel('qquoteadv/requestitem')->getCollection()->setQuote($_quote)
            ->addFieldToFilter('quoteadv_product_id', $quoteProductId);

        //#trying to find duplicate of requested quantity value
        foreach ($_collection as $item) {
            $c2qId = $item->getData('quote_id');

            $productId = $item->getData('product_id');
            $check = Mage::helper('qquoteadv')->isQuoteable($productId, $requestQty);
            if ($check->getHasErrors()) {
                $errors = $check->getErrors();
                $this->_redirectErr($errors);
                return null;
            }

            if ($requestQty == $item->getData('request_qty')) {
                $errorMsg = $this->__('Duplicate value entered');
                Mage::getSingleton('adminhtml/session')->addError($errorMsg);
                return $this->_redirect('*/*/edit', ['id' => $c2qId]);
            }
        }

        //last true is for getting the original price instead of the special price.
        $ownerPrice = Mage::helper('qquoteadv')->_applyPrice($quoteProductId, $requestQty, false, false);
        $originalPrice = Mage::helper('qquoteadv')->_applyPrice($quoteProductId, 1, false, true);

        $_quoteadv = $this->getQuotationQuote($c2qId);

        $rate = $_quoteadv->getBase2QuoteRate();

        $basePrice = Mage::helper('qquoteadv')->_applyPrice($quoteProductId, $requestQty, false, true);
        //$basePrice = $ownerPrice;

        if ($c2qId && $productId && isset($originalPrice) && $requestQty) {
            $requestData = [
                'quote_id' => $c2qId,
                'product_id' => $productId,
                'request_qty' => $requestQty,
                'owner_base_price' => $basePrice,
                'owner_cur_price' => $ownerPrice * $rate,
                'original_price' => $originalPrice,
                'quoteadv_product_id' => $quoteProductId,
                'original_cur_price' => $basePrice * $rate
            ];

            if ($requestQty) {
                try {
                    Mage::getModel('qquoteadv/requestitem')->setData($requestData)->save();
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                }
            }
        }
        if (!empty($c2qId)) {
            $this->_redirect('*/*/edit', ['id' => $c2qId]);
        } else {
            $this->_redirect('*/*/');
        }

        Mage::dispatchEvent('ophirah_qquoteadv_admin_addQtyField_after', [$quoteProductId]);
        return null;
    }

    /**
     * Function to get the admin name by the admin id
     *
     * @param $id
     * @return mixed
     */
    public function getAdminName($id)
    {
        return Mage::helper('qquoteadv')->getAdminName($id);
    }


    /**
     * Set redirect into response with anchor
     *
     * @param $path
     * @param array $arguments
     * @param string $anchor
     * @return $this
     */
    protected function _redirectAnhcor($path, $arguments = [], $anchor = '')
    {
        $this->_getSession()->setIsUrlNotice($this->getFlag('', self::FLAG_IS_URLS_CHECKED));
        $this->getResponse()->setRedirect($this->getUrl($path, $arguments) . $anchor);
        return $this;
    }

    /**
     * Action that adds a costprice on the edit quote page
     */
    public function addCostPriceAction() {
        $c2qId = (int)$this->getRequest()->getParam('quoteadv_id');
        $requestId = $this->getRequest()->getParam('request_id');
        $newCostPrice = $this->getRequest()->getParam('new_cost_price');
        $newCostPrice = str_replace(",", ".", $newCostPrice);

        Mage::dispatchEvent('ophirah_qquoteadv_admin_addCostPrice_before', [$c2qId, $requestId, $newCostPrice]);

        if((isset($requestId) && !empty($requestId)) && (isset($c2qId) && !empty($c2qId))){
            $request_item = Mage::getModel('qquoteadv/requestitem')->load($requestId);
            $request_item->setCostPrice($newCostPrice);
            $request_item->save();
        }

        if (!empty($c2qId)) {
            $this->_redirect('*/*/edit', ['id' => $c2qId]);
        } else {
            $this->_redirect('*/*/');
        }

        Mage::dispatchEvent('ophirah_qquoteadv_admin_addCostPrice_after', [$c2qId, $requestId, $newCostPrice]);
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $aclResource = 'sales/qquoteadv';
        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }

    /**
     * Function that gets the quote object
     *
     * @param null $quoteId
     * @return Ophirah_Qquoteadv_Model_Qqadvcustomer
     */
    protected function getQuotationQuote($quoteId = null)
    {
        //if no quote id is given
        if($quoteId == null){
            return $this->_quoteadv;
        }

        //if quote id is given
        if ($this->_quoteadv) {
            //check the id
            if ($this->_quoteadv->getId() != $quoteId) {
                $this->_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
            }
        } else {
            //$this->_quoteadv is not set
            $this->_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
        }

        return $this->_quoteadv;
    }

    /**
     * Function that resets the core_resource cart2quote version to the last installed script version.
     */
    public function fixdatabaseAction() {
        $last_update_version = Mage::getStoreConfig('qquoteadv_general/quotations/last_update_version');
        if($last_update_version){
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $table = $resource->getTableName('core/resource');

            $versionSql = 'UPDATE ' . $table . ' SET version = "' . $last_update_version . '" WHERE code = "qquoteadv_setup";';
            $writeConnection->query($versionSql);

            $dataVersionSql = 'UPDATE ' . $table . ' SET data_version = "' . $last_update_version . '" WHERE code = "qquoteadv_setup";';
            $writeConnection->query($dataVersionSql);
        }

        $url = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/qquoteadv_support");
        $this->_redirectUrl($url);
    }

    /**
     * Function to test Magento mail functionality
     *
     * @throws Zend_Validate_Exception
     */
    public function testMagentoMailAction()
    {
        $testEmailAddress = Mage::getStoreConfig('trans_email/ident_general/email');
        $senderName = Mage::getStoreConfig('trans_email/ident_general/name');
        $url = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/qquoteadv_support");

        if (!Zend_Validate::is($testEmailAddress, 'EmailAddress')) {
            Mage::getSingleton('core/session')->addError($this->__('%s is not a valid e-mail address', $testEmailAddress));
            return $this->_redirectUrl($url);
        }

        try {
            $template = Mage::helper('qquoteadv/email')->getEmailTemplateModel();
            $disabledEmail = Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;
            if ($template != $disabledEmail) {
                $template->setSenderName($senderName);
                $template->setSenderEmail($testEmailAddress);
                $template->setTemplateText($this->__('If you received this e-mail the Cart2Quote Magento e-mail test succeeded.'));
                $template->setTemplateSubject($this->__('Cart2Quote Magento Mail Test'));
            }

            $template->setData('c2qParams', ['email' => $testEmailAddress, 'name' => $senderName]);
            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_before', ['template' => $template]);
            $result = $template->send($testEmailAddress, $senderName, []);
            Mage::dispatchEvent('ophirah_qquoteadv_addSendMail_after', ['template' => $template, 'result' => $result]);
            if ($result) {
                Mage::getSingleton('core/session')->addSuccess($this->__('Test succesfull. please check the %s inbox to make sure you received the test e-mail', $testEmailAddress));
                Mage::getSingleton('core/session')->addNotice($this->__('If you did not receive the test e-mail please check your e-mail server'));
            } else {
                Mage::getSingleton('core/session')->addError($this->__('Could not send e-mail please check your log files. Make sure you enabled logging System > Configuration > Developer > Log Settings'));
            }
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($this->__('Error sending e-mail: %s', $e->__toString()));
        }

        return $this->_redirectUrl($url);
    }

    /**
     * Function to test PHP mail functionality
     *
     * @throws Zend_Validate_Exception
     */
    public function testPHPMailAction()
    {
        $testEmailAddress = Mage::getStoreConfig('trans_email/ident_general/email');
        $url = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/qquoteadv_support");

        if (!Zend_Validate::is($testEmailAddress, 'EmailAddress')) {
            Mage::getSingleton('core/session')->addError($this->__('%s is not a valid e-mail address', $testEmailAddress));
            return $this->_redirectUrl($url);
        }

        try {
            $to = $testEmailAddress;
            $from = $testEmailAddress;
            $subject = $this->__('Cart2Quote PHP Mail Test');
            $message = $this->__('If you received this email the Cart2Quote PHP Mail test succeeded.');
            $headers = sprintf('From: %s', $from);
            $result = mail($to, $subject, $message, $headers);
            if ($result) {
                Mage::getSingleton('core/session')->addSuccess($this->__('Test succesfull. please check the %s inbox to make sure you received the test e-mail', $testEmailAddress));
                Mage::getSingleton('core/session')->addNotice($this->__('If you did not receive the test e-mail please check your e-mail server'));
            } else {
                Mage::getSingleton('core/session')->addError($this->__('Could not send e-mail please check your log files. Make sure you enabled logging System > Configuration > Developer > Log Settings'));
            }
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($this->__('Error sending e-mail: %s', $e->__toString()));
        }

        $url = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/qquoteadv_support");
        $this->_redirectUrl($url);
    }

    /**
     * Ajax sorting action
     */
    public function ajaxsortAction()
    {
        $postData = $this->getRequest()->getPost();
        if ($data = $postData['data']) {
            $ids = [];
            foreach ($data as $value) {
                substr($value, 6);
                $ids[] = substr($value, 6);
            }
            $this->sortProducts($ids, $postData['quote_id']);
        }
    }

    /**
     * Sort items
     *
     * @param $ids
     * @param $quoteId
     */
    public function sortProducts($ids, $quoteId)
    {
        $i = 1;
        $quoteItems = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        foreach ($ids as $id) {
            foreach ($quoteItems as $item) {
                if ($item->getId() == $id) {
                    $item->setSortOrder($i);
                    $item->save();
                    break;
                }
            }
            $i++;
        }
    }
}
