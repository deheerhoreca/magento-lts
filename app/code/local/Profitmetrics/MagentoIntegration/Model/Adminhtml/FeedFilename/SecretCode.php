<?php

class Profitmetrics_MagentoIntegration_Model_Adminhtml_FeedFilename_SecretCode
{
    /**
     * @return string
     */
    public function getCommentText()
    {
        /** @var Profitmetrics_MagentoIntegration_Helper_Data $profitmetricsHelper */
        $profitmetricsHelper = Mage::helper('profitmetrics');
        $messageTranslated = $profitmetricsHelper->__('This code is required to secure the access to feed data. Please generate the random value, save it and provide the generated value to Profitmetrics support team.');
        $regenerateButtonLabel = $profitmetricsHelper->__('GenerateRandom');
        $buttonRegenerate = sprintf(
            '<button  id="profitmetrics-feed-generate" title="%s" type="button" class="scalable save" onclick="$(\'profitmetrics_settings_secret_code\').value=(Math.random().toString(36)+Math.random().toString(36)+Math.random().toString(36)).replace(/[^a-z0-9]+/g, \'\').substr(0,20)"><span><span><span>%s</span></span></span></button>',
            $regenerateButtonLabel,
            $regenerateButtonLabel
        );

        return '<p>' . $messageTranslated . '</p>' . $buttonRegenerate;
    }
}
