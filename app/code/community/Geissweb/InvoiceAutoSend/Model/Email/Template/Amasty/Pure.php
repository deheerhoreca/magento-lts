<?php
// DHH: this is not used anymore since reverting /var/www/vhosts/chefstore.nl/httpdocs/deheerhoreca-magento/app/code/community/Geissweb/InvoiceAutoSend/Model/Email/Template.php
/* added automatically by conflict fixing tool */
if(Mage::getConfig()->getNode('modules/Ebizmarts_MailChimp/active') && Mage::helper('core')->isModuleEnabled('Ebizmarts_MailChimp')) {
  // class Geissweb_InvoiceAutoSend_Model_Email_Template_Amasty_Pure extends Ebizmarts_MailChimp_Model_Email_Template {}
  class Geissweb_InvoiceAutoSend_Model_Email_Template_Amasty_Pure extends Geissweb_InvoiceAutoSend_Model_Email_TemplateRewrite {}
} else {
  class Geissweb_InvoiceAutoSend_Model_Email_Template_Amasty_Pure extends Geissweb_InvoiceAutoSend_Model_Email_TemplateRewrite {}
}
