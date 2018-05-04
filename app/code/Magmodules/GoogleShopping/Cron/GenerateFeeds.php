<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Cron;

use Magmodules\GoogleShopping\Model\Feed as FeedModel;
use Magmodules\GoogleShopping\Helper\General as GeneralHelper;
use Psr\Log\LoggerInterface;

class GenerateFeeds
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
     * @var LoggerInterface
     */
    private $logger;


    public function __construct(
        FeedModel $feedModel,
        GeneralHelper $generalHelper,
        LoggerInterface $logger
    ) {
        $this->feedModel = $feedModel;
        $this->generalHelper = $generalHelper;
        $this->logger = $logger;
    }

    /**
     * Execute: Run all GoogleShopping Feed generation.
     */
    public function execute()
    {
        try {
            $cronEnabled = $this->generalHelper->getCronEnabled();
            if ($cronEnabled) {
                $this->feedModel->generateAll();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $this;
    }
}
