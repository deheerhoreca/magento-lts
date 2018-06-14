<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magmodules\GoogleShopping\Helper\Feed as FeedHelper;

class Feeds extends Field
{

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var string
     */
    protected $_template = 'Magmodules_GoogleShopping::system/config/fieldset/feeds.phtml';

    /**
     * Feeds constructor.
     * @param Context $context
     * @param FeedHelper $feedHelper
     */
    public function __construct(
        Context $context,
        FeedHelper $feedHelper
    ) {
        $this->feedHelper = $feedHelper;
        parent::__construct($context);
    }

    /**
     * @return null
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('magmodules');

        return $this->toHtml();
    }

    /**
     * @return array
     */
    public function getFeedData()
    {
        return $this->feedHelper->getConfigData();
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getDownloadUrl($storeId)
    {
        return $this->getUrl('googleshopping/actions/download/store_id/' . $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getGenerateUrl($storeId)
    {
        return $this->getUrl('googleshopping/actions/generate/store_id/' . $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getPreviewUrl($storeId)
    {
        return $this->getUrl('googleshopping/actions/preview/store_id/' . $storeId);
    }
}
