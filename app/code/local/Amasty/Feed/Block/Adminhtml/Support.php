<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Feed
 */


class Amasty_Feed_Block_Adminhtml_Support extends Mage_Adminhtml_Block_Template
{
    protected $links = array(
        'new'   => "https://amasty.com/docs/doku.php?id=magento_1%3Aproduct-feed&utm_source=extension&utm_medium=link&utm_campaign=userguide_product_feed_2#general",
        'edit'  => "https://amasty.com/docs/doku.php?id=magento_1%3Aproduct-feed&utm_source=extension&utm_medium=link&utm_campaign=userguide_product_feed_2#general",
        'index' => "https://amasty.com/docs/doku.php?id=magento_1%3Aproduct-feed&utm_source=extension&utm_medium=link&utm_campaign=userguide_product_feed_1#how_to_set_up_a_feed_profile_for_google_merchant_and_facebook"
    );

    protected $text = array(
        'new'   =>  'Not sure what all these settings do? Please check <b>the guide</b> to learn what all this means.',
        'edit'  =>  'Not sure what all these settings do? Please check <b>the guide</b> to learn what all this means.',
        'index' =>  'Want to set up a feed profile for Google Merchant Center? Please check <b>the guide</b> to see how to do this fast and easy.'
    );

    /**
     * Reinitializing text array with locals
     * Amasty_Feed_Block_Adminhtml_Support constructor.
     * @param array $args
     */
    public function __construct(array $args = array())
    {
        foreach ($this->text as $key => $locText) {
            $this->text[$key] = Mage::helper('amfeed')->__($locText);
        }

        parent::__construct($args);
    }

    /**
     * @return array
     */
    public function getSupportLink()
    {
        return $this->links;
    }

    /**
     * @return array
     */
    public function getSupportText()
    {
        return $this->text;
    }
}
