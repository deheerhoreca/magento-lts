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
 * Class Ophirah_Crmaddon_Helper_Data
 */
class Ophirah_Crmaddon_Helper_Data extends Mage_Core_Helper_Abstract
{
    // Defining constants
    const CRMADDON_NOTICE = "The CRM module is part of the Cart2Quote Enterprise version. <a href='%s' target='blank'>Upgrade or change to a paid plan</a> to unlock more features";
    const CRMADDON_NOTICE_LINK = "https://www.cart2quote.com/magento-quotation-module-pricing.html?utm_source=clientwebsite&utm_medium=clientwebsite&utm_term=upgradeCRM&utm_content=upgradeCRM&utm_campaign=upgradeCRM";
    const CRMADDON_UPGRADE_LINK = "https://www.cart2quote.com/magento-quotation-module-pricing/magento-cart2quote-enterprise.html?utm_source=clientwebsite&utm_medium=clientwebsite&utm_term=upgradeCRM&utm_content=upgradeCRM&utm_campaign=upgradeCRM";
    const CRMADDON_UPGRADE_MESSAGE = "To use the CRM module of Cart2Quote and send messages to your customers <a href='%s' target='blank'>upgrade</a> to Cart2Quote Enterprise";

    /**
     *  Retrieve array with message templates
     *  from crmaddontemplates table
     * 
     *  @return array   // Database data
     */
    public function getTemplates()
    {
        $templates = [];
        $default = [];
        $DB_templates = Mage::getModel('crmaddon/crmaddontemplates')
            ->getCollection()
            ->addFieldToFilter('status', 1);

        foreach ($DB_templates as $DB_template) {
            if ($DB_template->getData('default') == 1) {
                $default['default'] = $DB_template->getData();
            }

            $templates[$DB_template->getTemplateId()] = $DB_template->getData();

        }

        $templates = $default + $templates;

        return $templates;
    }

    /**
     * This function makes an array of possible CRMaddon templates
     *
     * @param $templates
     * @return array
     */
    public Function createOptions($templates)
    {
        // create options from template array
        $options = [];
        foreach ($templates as $key => $value){
            if(isset($templates['default']['template_id']) && !empty($templates['default']['template_id'])){
                if ($key != $templates['default']['template_id']) {
                    $options[$value['template_id']] = $value['name'];
                }
            } else {
                $options[$value['template_id']] = $value['name'];
            }
        }

        return $options;
    }

    /**
     *  Retrieve array with messages
     *  from crmaddonmesages table
     *
     * @param $quote_id
     * @param bool $includeStateUpdates
     * @return array    //Database data
     * @internal param $decimal // Quote Id
     */
    public function getMessages($quote_id, $includeStateUpdates = true)
    {
        $messages = [];

        $DB_messages = Mage::getModel('crmaddon/crmaddonmessages')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quote_id)
            ->setOrder('message_id', 'DESC');

        foreach ($DB_messages as $DB_message) {
            // replace message tekst with Html stripped tekst
            $newMsg = $this->stripHtmlTags($DB_message->getData('message'));
            // get short message
            $shortMsg = $this->getShortMsg($newMsg);

            $DB_message->setData('message', $DB_message->getData('message'));

            if(!isset($shortMsg['message_1'])){
                $shortMsg['message_1'] = '';
            }
            $DB_message->setData('message_1', $shortMsg['message_1']);

            if(!isset($shortMsg['message_2'])){
                $shortMsg['message_2'] = null;
            }
            $DB_message->setData('message_2', $shortMsg['message_2']);

            $date = DateTime::createFromFormat('Y-m-d H:i:s', $DB_message->getData('created_at'));
            $aKey = $date->getTimestamp().'0'.(100000+(int)$DB_message->getMessageId());
            $messages[$aKey] = $DB_message->getData();

        }

        if($includeStateUpdates){
            //trail
            $trailMessages = Mage::getModel('qquoteadv/quotetrail')
                ->getCollection()
                ->addFieldToFilter('quote_id', $quote_id)
                ->setOrder('trail_id', 'DESC');

            foreach ($trailMessages as $DB_message) {
                $DB_message->setData('message_id', 'trail'.$DB_message->getTrailId());

                $DB_message->setData('subject', $this->__('Status update (system message)'));
                $date = DateTime::createFromFormat('Y-m-d H:i:s', $DB_message->getData('created_at'));
                $aKey = $date->getTimestamp().'1'.(100000+(int)$DB_message->getTrailId());
                $messages[$aKey] = $DB_message->getData();
            }
        }

        // create empty message if no messages are found
        if (empty($messages)) {
            $messages[] = ['message' => ' ',
                'subject' => ' ',
                'created_at' => now()
            ];
        }

        krsort($messages);
        return $messages;
    }

    /**
     *  Strip Html markup, and
     *  insert break tags
     *
     * @param  string // message text
     * @return string     // new string
     */
    public function stripHtmlTags($message)
    {
        // Defining Arrays for replacement
        // first    - array with target text
        // second   - temp replacement text
        // third    - final replacement text, if different from first
        $totalReplace = [];
        $totalReplace[] = [["<br>", "<br/>", "<br />"], "[[!!##BREAK##!!]]", "<br />"];
        $totalReplace[] = [["<p>"], "[[!!##POPEN##!!]]"];
        $totalReplace[] = [["</p>"], "[[!!##PCLOSE##!!]]"];
        $replaceMsg = '';
        $returnMsg = '';

        // trim and decode message
        $trimMessage = (trim(html_entity_decode($message)));
        // replace text with temp text and strip Html
        foreach ($totalReplace as $replacetext) {
            $replaceMsg = str_replace($replacetext[0], $replacetext[1], $trimMessage);
            $trimMessage = $replaceMsg;
        }
        // strip remaining tags
        $replaceMsg = strip_tags($replaceMsg);
        // replace temptext with html tag        
        foreach ($totalReplace as $replacetext) {
            if (empty($replacetext[2])) {
                $replacetext[2] = $replacetext[0][0];
            }
            $returnMsg = str_replace($replacetext[1], $replacetext[2], $replaceMsg);
            $replaceMsg = $returnMsg;
        }

        return $returnMsg;
    }

    /**
     *  Cut message in 2 pieces
     *  for shorttext display
     *
     * @param  string // message text
     * @return array()    // array with short message
     */
    public function getShortMsg($message)
    {
        $shortMsglength = Mage::getStoreConfig('qquoteadv_sales_representatives/messaging/crmaddon_shortmsg');

        $return = [];
        if (strlen($message) >= $shortMsglength) {
            $return['message_1'] = trim(substr($message, 0, $shortMsglength));
            $return['message_2'] = trim(substr($message, $shortMsglength));

        }

        return $return;
    }

    /**
     * Check if the CRMaddon tab on the edit quote page is selected
     *
     * @return bool
     */
    public function tabIsActive()
    {
        if (Mage::app()->getRequest()->getParam('crmbodytmpl')) {
            return true;
        }
        return false;
    }

    /**
     * CRMaddon Module is only available
     * fot Enterprise users of Cart2Quote
     *
     * @param   mixed      // Message to display, with or without link
     */
    final public function checkLicense($notice = NULL)
    {

        // Check for a valid Enterprise License
        if ($notice == NULL) {
            $notice['message'] = Ophirah_Crmaddon_Helper_Data::CRMADDON_NOTICE;
            $notice['link'] = Ophirah_Crmaddon_Helper_Data::CRMADDON_NOTICE_LINK;
        }

        if (!Mage::helper('qquoteadv/license')->validLicense('messaging', null, true)) {
            if (!is_array($notice)) {
                Mage::getSingleton('adminhtml/session')->addNotice($this->__($notice));
            } else {
                Mage::getSingleton('adminhtml/session')->addNotice($this->__($notice['message'], $notice['link']));
            }
        }

    }

    /**
     * @param $oldQuote
     * @param $newQuote
     */
    public function duplicateQuoteMessagesToNewQuote($oldQuote, $newQuote){
        $oldQuote_messages = Mage::getModel('crmaddon/crmaddonmessages')
            ->getCollection()
            ->addFieldToFilter('quote_id', $oldQuote->getData('quote_id'))
            ->setOrder('message_id', 'DESC');

        foreach ($oldQuote_messages as $message) {
            //duplicate message for new quote
            $cloneData = $message->getData();
            unset($cloneData['message_id']);
            unset($cloneData['quote_id']);
            $new_message = Mage::getModel("crmaddon/crmaddonmessages")->setData($cloneData);
            $new_message->setData("quote_id", $newQuote->getData("quote_id"));
            $new_message->save();
        }

        return;
    }

    /**
     * Function that adds support for SMTPPro and Amasty SMTP
     *
     * @param null $storeId
     * @return mixed
     */
    public function getEmailTemplateModel($storeId = null)
    {
        if ($storeId == null) {
            $storeId = Mage::app()->getStore()->getId();
        }

        if (Mage::helper('core')->isModuleEnabled('Aschroder_SMTPPro')) {
            return Mage::getModel('crmaddon/templatesmtppro');
        }

        if (Mage::helper('core')->isModuleEnabled('Amasty_Smtp')) {
            return Mage::getModel('crmaddon/templatesmtpamasty');
        }

        if (Mage::helper('core')->isModuleEnabled('Ebizmarts_Mandrill')) {
            $mandrillModel = Mage::getModel('crmaddon/templatemandrill');
            $mandrillModel->setStoreId($storeId);
            return $mandrillModel;
        }

        if (Mage::helper('core')->isModuleEnabled('Bronto_Email')) {
            return Mage::getModel('core/email_template');
        }

        return Mage::getModel('core/email_template');
    }

    /**
     * Get the allowed HTML tags for usage in the escapeHtml function
     *
     * @return array
     */
    public function getAllowedHtmlTags()
    {
        //list the HTML tags that don't need escaping
        $allowedTags = [
            'strong',
            'p',
            'br',
            'em',
            'a[a-zA-Z="-_:\s\;\“\"\”\.\']*?',
            'span[a-zA-Z="-_:\s\;\“\"\”\.\']*?',
        ];

        return $allowedTags;
    }

    /**
     * Escape html entities
     *
     * @param mixed $data
     * @param null $allowedTags
     * @param bool $allowInnerQuotes
     * @return array|mixed|string
     */
    public function escapeHtml($data, $allowedTags = null, $allowInnerQuotes = false)
    {
        if (!$allowInnerQuotes && !is_array($data)) {
            return Mage::helper('core')->escapeHtml($data, $allowedTags);
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->escapeHtml($item, $allowedTags, $allowInnerQuotes);
            }
        } else {
            // process single item
            if (strlen($data)) {
                if (is_array($allowedTags) and !empty($allowedTags)) {
                    $allowed = implode('|', $allowedTags);

                    //add tag to keep inner quotes
                    if ($allowInnerQuotes) {
                        preg_match_all(
                            '/<([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)>/si',
                            $data,
                            $matches,
                            PREG_SET_ORDER
                        );
                        foreach ($matches as $match) {
                            if (!empty($match) && is_array($match) && isset($match[0])) {
                                $innerTag = $match[0];
                                $innerTagAllowed = str_replace('"', '##quot##', $innerTag);

                                $data = str_replace($innerTag, $innerTagAllowed, $data);
                            }
                        }
                    }

                    $result = preg_replace('/<([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)>/si', '##$1$2$3##', $data);
                    $result = htmlspecialchars($result, ENT_COMPAT, 'UTF-8', false);

                    //replace tag to keep inner quotes
                    if ($allowInnerQuotes) {
                        $result = str_replace('##quot##', '"', $result);
                    }

                    $result = preg_replace('/##([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)##/si', '<$1$2$3>', $result);
                } else {
                    $result = htmlspecialchars($data, ENT_COMPAT, 'UTF-8', false);
                }
            } else {
                $result = $data;
            }
        }

        return $result;
    }
}
