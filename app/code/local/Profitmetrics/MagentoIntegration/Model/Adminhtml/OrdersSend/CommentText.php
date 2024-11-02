<?php

class Profitmetrics_MagentoIntegration_Model_Adminhtml_OrdersSend_CommentText
{
    /**
     * @return string
     */
    public function getCommentText()
    {
        /** @var Profitmetrics_MagentoIntegration_Helper_Data $profitmetricsHelper */
        $profitmetricsHelper = Mage::helper('profitmetrics');
        $ordersSendUrl = Mage::helper("adminhtml")->getUrl('adminhtml/profitmetrics/sendOrders');
        $feedGenerateMessage = $profitmetricsHelper->__('Send Orders Now');

        $ordersSendButtonHtml = sprintf(
            '<br><a type="button" href="%s"><button  id="profitmetrics-send-data" title="%s" type="button" class="scalable save"><span><span><span>%s</span></span></span></button></a>',
            $ordersSendUrl,
            $feedGenerateMessage,
            $feedGenerateMessage
        );

        return $profitmetricsHelper->__('You can force orders data sending by clicking on this button') . $ordersSendButtonHtml;
    }
}
