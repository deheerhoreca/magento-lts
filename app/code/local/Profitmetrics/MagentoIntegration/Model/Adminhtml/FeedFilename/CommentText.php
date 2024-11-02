<?php

class Profitmetrics_MagentoIntegration_Model_Adminhtml_FeedFilename_CommentText
{
    /**
     * @return string
     */
    public function getCommentText()
    {
        $currentStoreCode = Mage::app()->getRequest()->getParam('store') ?: Mage::app()->getDefaultStoreView()->getCode();
        /** @var Profitmetrics_MagentoIntegration_Helper_Data $profitmetricsHelper */
        $profitmetricsHelper = Mage::helper('profitmetrics');
        $secretCode = $profitmetricsHelper->getSecretCode();
        $feedUrl = Mage::getUrl(
            'profitmetrics/feed/index',
            array(
                'store' => $currentStoreCode,
                'code' => $secretCode
            )
        );
        $feedGenerateUrl = Mage::helper("adminhtml")->getUrl('adminhtml/profitmetrics/generatefeed');
        $feedGenerateMessage = $profitmetricsHelper->__('Generate Feed Now');

        $generateButtonHtml = sprintf(
            '<br><a type="button" href="%s"><button  id="profitmetrics-feed-generate" title="%s" type="button" class="scalable save"><span><span><span>%s</span></span></span></button></a>',
            $feedGenerateUrl,
            $feedGenerateMessage,
            $feedGenerateMessage
        );

        return $profitmetricsHelper->__(
            'Feeds are stored in the server in the path var/profitmetrics/products_{{store}}.xml, where {{store}} is the store code and is accessible by the URL <a href="%s" target="_blank">%s</a>. Please select the specific file name in your store scope in order to modify the feed name for specific store.',
            $feedUrl,
            $feedUrl
        ) . $generateButtonHtml;
    }
}
