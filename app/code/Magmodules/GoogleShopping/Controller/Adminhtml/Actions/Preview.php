<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Controller\Adminhtml\Actions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magmodules\GoogleShopping\Model\Feed as FeedModel;
use Magmodules\GoogleShopping\Helper\General as GeneralHelper;

class Preview extends Action
{

    /**
     * @var FeedModel
     */
    private $feedModel;

    /**
     * @var GeneralHelper
     */
    private $generalHelper;

    /**
     * Preview constructor.
     * @param Context $context
     * @param GeneralHelper $generalHelper
     * @param FeedModel $feedModel
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        FeedModel $feedModel
    ) {
        $this->feedModel = $feedModel;
        $this->generalHelper = $generalHelper;
        parent::__construct($context);
    }

    /**
     * Execute function for preview of the GoogleShopping feed in admin.
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        if (!$this->generalHelper->getEnabled($storeId)) {
            $errorMsg = __('Please enable the extension before generating the feed.');
            $this->messageManager->addError($errorMsg);
            $this->_redirect('adminhtml/system_config/edit/section/magmodules_googleshopping');
        } else {
            $page = $this->getRequest()->getParam('page', 1);
            $productId = $this->getRequest()->getParam('pid', []);
            if ($result = $this->feedModel->generateByStore($storeId, 'preview', $productId, $page)) {
                $this->getResponse()->setHeader('Content-type', 'text/xml');
                $this->getResponse()->setBody(file_get_contents($result['path']));
            } else {
                $errorMsg = __('Unkown error.');
                $this->messageManager->addError($errorMsg);
                $this->_redirect('adminhtml/system_config/edit/section/magmodules_googleshopping');
            }
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magmodules_GoogleShopping::config');
    }
}
