<?php

/**
 * OpenMage
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available at https://opensource.org/license/osl-3-0-php
 *
 * @category   Mage
 * @package    Mage_Centinel
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://www.magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://www.openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Centinel validation frame
 *
 * @category   Mage
 * @package    Mage_Centinel
 */
class Mage_Centinel_Block_Authentication extends Mage_Core_Block_Template
{
    /**
     * Strage for identifiers of related blocks
     *
     * @var array
     */
    protected $_relatedBlocks = [];

    /**
     * Flag - authentication start mode
     * @see self::setAuthenticationStartMode
     *
     * @var bool
     */
    protected $_authenticationStartMode = false;

    /**
     * Add identifier of related block
     *
     * @param string $blockId
     * @return $this
     */
    public function addRelatedBlock($blockId)
    {
        $this->_relatedBlocks[] = $blockId;
        return $this;
    }

    /**
     * Return identifiers of related blocks
     *
     * @return array
     */
    public function getRelatedBlocks()
    {
        return $this->_relatedBlocks;
    }

    /**
     * Check whether authentication is required and prepare some template data
     *
     * @return string
     */
    protected function _toHtml()
    {
        $method = Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethodInstance();
        if ($method->getIsCentinelValidationEnabled()) {
            $centinel = $method->getCentinelValidator();
            if ($centinel && $centinel->shouldAuthenticate()) {
                $this->setAuthenticationStart(true);
                $this->setFrameUrl($centinel->getAuthenticationStartUrl());
                return parent::_toHtml();
            }
        }
        return parent::_toHtml();
    }
}
