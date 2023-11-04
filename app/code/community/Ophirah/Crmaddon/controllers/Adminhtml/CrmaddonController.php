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
 * @package     Crmaddon
 * @copyright   Copyright (c) 2020 Cart2Quote B.V. (https://www.cart2quote.com)
 * @license     https://www.cart2quote.com/ordering-licenses(https://www.cart2quote.com)
 */

/**
 * Class Ophirah_Crmaddon_Adminhtml_CrmaddonController
 */
class Ophirah_Crmaddon_Adminhtml_CrmaddonController extends Mage_Adminhtml_Controller_Action
{
    // Defining constants
    const XML_PATH_CRMADDON_EMAIL_TEMPLATE = 'qquoteadv_sales_representatives_messaging_crmaddon_container';
    const CHECKBOX_ENABLED = "on";

    /**
     * required fields
     *
     * @param null $option
     * @return mixed
     */
    public function getRequired($option = NULL)
    {

        $return = [];
        $return['sendMail'] = ['crm_subject' => 'subject',
            'crm_message' => 'message'
        ];

        return $return[$option];
    }

    /**
     *  Process Form data from Cart2Quote module
     *  CRM message post action.
     */
    public function crmmessageAction()
    {
        // Get data from Post        
        $crmData = $this->getCrmdata();
        $quote_id = $crmData['crm_id'];

        Mage::dispatchEvent('ophirah_crmaddon_crmmessage_before', [$crmData]);

        // check empty fields
        $required = $this->getRequired('sendMail');
        foreach ($crmData as $key => $value) {
            if ($value == NULL || $value == '') {
                if (array_key_exists($key, $required)) {
                    $message = $this->__("Datafield %s is empty", $required[$key]);
                    Mage::getSingleton('adminhtml/session')->addError($message);
                }
            }
        }

        // set return path
        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmaddon/1';

        // Check for a valid Enterprise License
        if (!Mage::helper('qquoteadv/license')->validLicense('messaging', $crmData['createHash'])) {
            $errorMsg = Ophirah_Crmaddon_Helper_Data::CRMADDON_UPGRADE_MESSAGE;
            $errorLink = Ophirah_Crmaddon_Helper_Data::CRMADDON_UPGRADE_LINK;
            Mage::getSingleton('adminhtml/session')->addError($this->__($errorMsg, $errorLink));
            $this->_redirect($returnPath);
            return;
        }

        if (!isset($errorMsg) && !isset($message)) {
            try {
                $sendMail = $this->sendEmail($crmData);

                if (empty($sendMail)) {
                    $message = $this->__("CRM message couldn't be sent to the client");
                    Mage::getSingleton('adminhtml/session')->addError($message);
                } elseif (is_string($sendMail) && $sendMail == Ophirah_Crmaddon_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL) {
                    Mage::getSingleton('adminhtml/session')->addNotice($this->__('Sending CRM Email is disabled'));
                } else {
                    if (isset($crmData['crm_notifyCustomer']) && $crmData['crm_notifyCustomer'] == 1) {
                        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('CRM Email was sent'));
                    }
                }

            } catch (Exception $e) {
                $message = $this->__("CRM message couldn't be sent to the client");
                Mage::log('Exception: CRMAddon: ' .$e->getMessage(), null, 'c2q_exception.log', true);
                Mage::getSingleton('adminhtml/session')->addError($message);
            }
        }

        $this->_redirect($returnPath);
        Mage::dispatchEvent('ophirah_crmaddon_crmmessage_after', [$crmData]);
    }

    /**
     *  Select CRM_addon data only
     *  from the Form Post data
     *
     * @return array
     */
    public function getCrmdata()
    {
        $return['createHash'] = null;
        foreach ($this->getRequest()->getPost() as $key => $value) {
            if ($key == 'crm_notifyCustomer' && $value == self::CHECKBOX_ENABLED) {
                $return[$key] = 1;
            } else {
                if (substr($key, 0, 4) == "crm_" || $key == 'createHash') {
                    $return[$key] = $value;
                }
            }
        }

        return $return;
    }

    /**
     * Send email to client to informing about the quote proposition
     * @param   Array ()     // $params customer address
     * @return bool|mixed
     */
    public function sendEmail($crmData)
    {
        $res = null;

        //Create an array of variables to assign to template
        $vars = [];
        $storeId = $crmData['crm_storeId'];

        // Setting vars
        $vars['crmaddonBody'] = $crmData['crm_message'];
        $vars['message'] = $crmData['crm_message'];
        $vars['store'] = Mage::app()->getStore($storeId);

        // Prepare data for saving to database
        $saveData = $this->prepareSaveData($crmData);

        // Check if customer needs to be notified
        if (!isset($saveData['customer_notified'])){
            $res = true;
        } elseif ((int)$saveData['customer_notified'] == 1) {
            $template = Mage::helper('crmaddon')->getEmailTemplateModel($storeId);

            $default_template = Mage::getStoreConfig('qquoteadv_sales_representatives/messaging/crmaddon_container', $storeId);
            $disabledEmail = Ophirah_Crmaddon_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;

            //check for disabled template
            if($default_template != $disabledEmail) {
                $res = $this->sendEmailWithTemplate($crmData, $default_template, $storeId, $template, $vars, $saveData);
            }
        }

        $qquote = Mage::getModel('qquoteadv/qqadvcustomer')->load($saveData['quote_id']);
        $saveData['customer_id']    = (int)$qquote->getCustomerId();
        $saveData['user_id']        = (int)$qquote->getUserId();
        $crmaddonmessages = Mage::getModel('crmaddon/crmaddonmessages')->setData($saveData)->save();
        Mage::dispatchEvent('ophirah_crmaddon_send_after_save', ['crm_addon_messages_model' => $crmaddonmessages]);
        return $res;
    }


    /**
     *  Load selected message template
     *  for CRMaddon textarea
     */
    public function loadtemplateAction()
    {
        $crmData = $this->getCrmdata();
        Mage::dispatchEvent('ophirah_crmaddon_loadtemplate_before', [$crmData]);
        $msgtemplate = $crmData['crm_message_template'];
        $quote_id = $crmData['crm_id'];

        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmtmpl/' . $msgtemplate;

        $this->_redirect($returnPath);
        Mage::dispatchEvent('ophirah_crmaddon_loadtemplate_after', [$crmData]);
    }

    /**
     *  Load selected crm bodytemplate
     *  for CRMaddon textarea
     */
    public function loadcrmtemplateAction()
    {
        $crmData = $this->getCrmdata();
        Mage::dispatchEvent('ophirah_crmaddon_loadcrmtemplate_before', [$crmData]);
        $bodytemplate = $crmData['crm_bodyId'];
        $quote_id = $crmData['crm_id'];

        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmbodytmpl/' . $bodytemplate;

        $this->_redirect($returnPath);
        Mage::dispatchEvent('ophirah_crmaddon_loadcrmtemplate_after', [$crmData]);
    }

    /**
     *  Save crm bodytemplate
     *  from CRMaddon textarea
     */
    public function savecrmtemplateAction()
    {
        $crmData = $this->getCrmdata();
        $quote_id = $crmData['crm_id'];
        $bodyTmplId = $crmData['crm_bodytemplateid'];

        Mage::dispatchEvent('ophirah_crmaddon_savecrmtemplate_before', [$crmData]);

        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        if (!isset($crmData['crm_templatedefault'])) {
            $crmData['crm_templatedefault'] = 0;
        }

        //Check default setting
        if ((int)$crmData['crm_templatedefault'] == 1) {
            $this->resetDefault();
        }

        // set return path
        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmbodytmpl/' . $bodyTmplId;
        // get template from DB
        $template = Mage::getModel('crmaddon/crmaddontemplates')->load($bodyTmplId);
        // collect save data array
        $saveData = $this->prepareSavetemplateData($crmData);
        // set data
        $template->setData($saveData);
        // save and return
        $this->saveTemplate($template, $returnPath);

        Mage::dispatchEvent('ophirah_crmaddon_savecrmtemplate_after', [$crmData]);
    }

    /**
     *  Create new crm bodytemplate
     *  from CRMaddon textarea
     */
    public function newcrmtemplateAction()
    {
        $crmData = $this->getCrmdata();
        $quote_id = $crmData['crm_id'];
        $bodyTmplId = $crmData['crm_bodytemplateid'];

        Mage::dispatchEvent('ophirah_crmaddon_newcrmtemplate_before', [$crmData]);

        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        if (!isset($crmData['crm_templatedefault'])) {
            $crmData['crm_templatedefault'] = 0;
        }

        //Check default setting
        if ((int)$crmData['crm_templatedefault'] == 1) {
            $this->resetDefault();
        }

        // set return path
        $returnPath = $this->getBaseReturnPath($quote_id, $crmData);
        // Check unique template name
        $templateNames = Mage::helper('crmaddon')->getTemplates();
        foreach ($templateNames as $templateName) {
            if (trim($crmData['crm_templatename']) == trim($templateName['name'])) {
                $message = Mage::helper('crmaddon')->__("CRM template name allready exists");
            }
        }

        if (isset($message)) {
            Mage::getSingleton('adminhtml/session')->addError($message);
            $this->_redirect($returnPath);
        } else {
            // collect save data array
            $saveData = $this->prepareSavetemplateData($crmData);

            // template_id needs to be unset for creating new template
            unset($saveData['template_id']);
            $save = Mage::getModel('crmaddon/crmaddontemplates')->setData($saveData);

            $this->saveTemplate($save, $returnPath, true);
        }

        Mage::dispatchEvent('ophirah_crmaddon_newcrmtemplate_after', [$crmData]);
    }

    /**
     *  Delete crm bodytemplate
     *  from database
     */
    public function deletecrmtemplateAction()
    {
        $crmData = $this->getCrmdata();
        $quote_id = $crmData['crm_id'];
        Mage::dispatchEvent('ophirah_crmaddon_deletecrmtemplate_before', [$crmData]);

        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        // set return path
        $defaultTemplate = $this->getDefaultTemplate();
        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmbodytmpl/' . $defaultTemplate[0]['template_id'];

        $templateId = (int)$crmData['crm_bodyId'];

        try {
            if (!empty($templateId)) {
                $delete = Mage::getModel('crmaddon/crmaddontemplates')->load($templateId)->delete();
            } else {
                $delete = '';
            }

            if (empty($delete)) {
                $message = $this->__("CRM template couldn't be deleted from the database");
                Mage::getSingleton('adminhtml/session')->addError($message);
            } else {
                $message = $this->__("CRM template has been succesfully deleted from the database");
                Mage::getSingleton('adminhtml/session')->addSuccess($message);

            }

        } catch (Exception $e) {
            $message = $this->__("CRM template couldn't be deleted from the database");
            Mage::log('Exception: CRMAddon: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            Mage::getSingleton('adminhtml/session')->addError($message);
        }

        $this->_redirect($returnPath);
        Mage::dispatchEvent('ophirah_crmaddon_deletecrmtemplate_after', [$crmData]);
    }

    /**
     *  Saving data from Form
     *  to the database
     *
     * @param $saveData
     * @param $returnPath
     * @param bool $new
     * @internal param $Array ()     // $saveData   - Prepared data from form
     * @internal param $Array ()     // $returnPath - Path to redirect
     */
    public function saveTemplate($saveData, $returnPath, $new = false)
    {
        try {
            $save = $saveData->save();

            if (empty($save)) {
                $message = $this->__("CRM template couldn't be saved to the database");
                Mage::getSingleton('adminhtml/session')->addError($message);
            } else {
                $message = $this->__("CRM template has succesfully been saved to the database");
                Mage::getSingleton('adminhtml/session')->addSuccess($message);

            }

        } catch (Exception $e) {
            $message = $this->__("CRM template couldn't be saved to the database");
            Mage::log('Exception: CRMAddon: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            Mage::getSingleton('adminhtml/session')->addError($message);
        }

        if ($new === true) {
            $DB_templates = Mage::getModel('crmaddon/crmaddontemplates')
                ->getCollection()
                ->addFieldToFilter('name', $saveData['name']);

            $bodyTmplId = 0;
            foreach ($DB_templates as $DB_template) {
                $bodyTmplId = $DB_template->getData('template_id');
            }

            $returnPath = $returnPath . '/crmbodytmpl/' . $bodyTmplId;
        }

        $this->_redirect($returnPath);
    }

    /**
     *  Prepare data from Form to save to the database
     *
     * @param   array   // Data from form
     * @return  array   // Data with keyname as the database column names
     */
    public function prepareSaveData($crmData)
    {

        $returnData = [];
        $translateArray = ['quote_id' => 'crm_id',
            'email_address' => 'crm_customerEmail',
            'subject' => 'crm_subject',
            'template_id' => 'crm_message_template',
            'message' => 'crm_message',
            'customer_notified' => 'crm_notifyCustomer'
        ];

        foreach ($translateArray as $key => $value) {
            if (isset($crmData[$value])){
                $crmData[$value] = trim($crmData[$value]);
                if ($key == 'message') {
                    $crmData[$value] = htmlentities($crmData[$value], ENT_QUOTES, "UTF-8");
                }
                $returnData[$key] = $crmData[$value];
            }
        }

        $returnData['created_at'] = now();
        $returnData['updated_at'] = now();

        return $returnData;
    }


    /**
     *  Prepare data from Form to save to the database
     *
     * @param   array   //Data from form
     * @return  array   //Data with keyname as the database column names
     */
    public function prepareSavetemplateData($crmData)
    {
        $saveData = [];
        // at the moment unused variable
        $crmData['crm_status'] = 1;
        $translateArray = ['template_id' => 'crm_bodytemplateid',
            'name' => 'crm_templatename',
            'subject' => 'crm_templatesubject',
            'template' => 'crm_templatebody',
            'default' => 'crm_templatedefault',
            'status' => 'crm_status'
        ];

        foreach ($translateArray as $key => $value) {
            $crmData[$value] = trim($crmData[$value]);
            if ($key == 'template') {
                $crmData[$value] = htmlentities($crmData[$value], ENT_QUOTES, "UTF-8");
            }
            $saveData[$key] = $crmData[$value];
        }

        return $saveData;
    }

    /**
     *  Creates returnpath
     *
     * @param $quote_id
     * @param $crmData
     * @return string // Returnpath
     * @internal param $decimal // QuoteId
     */
    public function getBaseReturnPath($quote_id, $crmData)
    {
        if ($crmData['crm_moduleName'] == NULL || $crmData['crm_moduleName'] == 'admin') {
            $crmData['crm_moduleName'] = '*';
        }
        $return = $crmData['crm_moduleName'] . '/' . $crmData['crm_controllerName'] . '/' . $crmData['crm_actionName'] . '/id/' . $quote_id;

        return $return;
    }

    /**
     * Function that returns all default CRMaddon templates in an array
     *
     * @return array
     */
    public function getDefaultTemplate()
    {
        $defaultemplate = [];
        $DB_defaultTemplates = Mage::getModel('crmaddon/crmaddontemplates')
            ->getCollection()
            ->setOrder('template_id', 'ASC');

        foreach ($DB_defaultTemplates as $DB_default) {
            if ($DB_default->getData('default') == 1) {
                $defaultemplate[] = $DB_default->getData();
            }
        }

        return $defaultemplate;
    }

    /**
     * Function that resets all default templates to no default
     */
    public function resetDefault()
    {
        $defaultTemplate = $this->getDefaultTemplate();

        foreach ($defaultTemplate as $default) {
            Mage::getModel('crmaddon/crmaddontemplates')
                ->load($default['template_id'])
                ->setData('default', 0)
                ->save();
        }

    }

    /**
     * @param $crmData
     * @param $default_template
     * @param $storeId
     * @param $template
     * @param $vars
     * @param $saveData
     * @return mixed
     */
    public function sendEmailWithTemplate($crmData, $default_template, $storeId, $template, $vars, $saveData)
    {
        if ($default_template) {
            $templateId = $default_template;
        } else {
            $templateId = self::XML_PATH_CRMADDON_EMAIL_TEMPLATE;
        }

        // get locale of quote sent so we can sent email in that language
        $storeLocale = Mage::getStoreConfig('general/locale/code', $storeId);

        if (is_numeric($templateId)) {
            $template->load($templateId);
        } else {
            $template->loadDefault($templateId, $storeLocale);
        }

        if (isset($crmData['crm_subject'])) {
            $subject = Mage::helper('core')->escapeHtml($crmData['crm_subject']);
        } else {
            $subject = $template['template_subject'];
        }
        $vars['subject'] = $subject;

        $sender = Mage::getModel('qquoteadv/qqadvcustomer')->load($saveData['quote_id'])->getEmailSenderInfo();

        $template->setSenderName($sender['name']);
        $template->setSenderEmail($sender['email']);
        $template->setTemplateSubject($subject);
        $template->setDesignConfig(['store' => $storeId]);

        // getting vars
        $qquote = Mage::getModel('qquoteadv/qqadvcustomer')->load($saveData['quote_id']);
        $customer = Mage::getModel('customer/customer')->load($qquote->getCustomerId());

        //get vars for template
        $admin = Mage::getModel('admin/user')->load($qquote->getUserId());
        $adminName = $admin->getFirstname() . ' ' . $admin->getLastname();
        $remark = Mage::getStoreConfig('qquoteadv_quote_configuration/proposal/qquoteadv_remark', $qquote->getStoreId());
        $sender = Mage::getModel('qquoteadv/qqadvcustomer')->load($qquote->getId())->getEmailSenderInfo();

        //set vars for template
        $varsExtra = [
            'quote' => $qquote,
            'customer' => Mage::getModel('customer/customer')->load($qquote->getCustomerId()),
            'quoteId' => $qquote->getId(),
            'storeId' => $qquote->getStoreId(),
            'adminname' => $adminName,
            'adminphone' => $admin->getTelephone(),
            'remark' => $remark,
            'link' => Mage::getUrl("qquoteadv/view/view/", [
                'id' => $qquote->getId(),
                '_store' => $qquote->getStoreId()
            ]),
            'adminlink' => Mage::getModel('adminhtml/url')->turnOffSecretKey()->getUrl("*/qquoteadv/edit", [
                'id' => $qquote->getId(),
                '_store' => $qquote->getStoreId()
            ]),
            'sender' => $sender,
            'CRMcustomername' => $customer->getName(),
            'CRMsendername' => $sender['name']
        ];

        $vars = array_merge($vars, $varsExtra);

        /**
         * Opens the qquote_request.html, throws in the variable array
         * and returns the 'parsed' content that you can use as body of email
         */
        //$template->getProcessedTemplate($vars);

        /*
         * getProcessedTemplate is called inside send()
         */
        Mage::dispatchEvent('ophirah_crmaddon_addSendMail_before', ['template' => $template]);
        $res = $template->send($customer->getEmail(), $customer->getName(), $vars);
        Mage::dispatchEvent('ophirah_crmaddon_addSendMail_after', ['template' => $template, 'result' => $res]);
        return $res;
    }

    /**
     * Function that resets the core_resource crmaddon version to the last installed script version.
     */
    public function fixdatabaseAction() {
        $last_update_version = Mage::getStoreConfig('qquoteadv_sales_representatives/last_update_version');
        if($last_update_version){
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $table = $resource->getTableName('core/resource');

            $versionSql = 'UPDATE ' . $table . ' SET version = "' . $last_update_version . '" WHERE code = "crmaddon_setup";';
            $writeConnection->query($versionSql);

            $dataVersionSql = 'UPDATE ' . $table . ' SET data_version = "' . $last_update_version . '" WHERE code = "crmaddon_setup";';
            $writeConnection->query($dataVersionSql);
        }

        $url = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/qquoteadv_support");
        $this->_redirectUrl($url);
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $aclResource = 'sales/qquoteadv/crmaddon';
        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }
}
