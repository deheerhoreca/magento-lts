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
 * @package     Qquoteadv
 * @copyright   Copyright (c) 2020 Cart2Quote B.V. (https://www.cart2quote.com)
 * @license     https://www.cart2quote.com/ordering-licenses(https://www.cart2quote.com)
 */

/**
 * Class Ophirah_Qquoteadv_Helper_Logging
 */
final class Ophirah_Qquoteadv_Helper_Logging extends Mage_Core_Helper_Abstract
{

    /**
     * @param $action
     * @param $location
     * @param $quote_id
     */
    final public function sentAnonymousData($action, $location, $quote_id)
    {
        $domain = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : 'no-domain.com';
        $level = Mage::helper('qquoteadv/license')->getAccessLevel();
        if ($level == null) {
            $level = 0;
        }
        if (Mage::helper('qquoteadv/license')->isOpenSourceC2QVersion()) {
            $level = -1;
        }

        $createHash = Mage::registry('createHash');
        if (Mage::helper('qquoteadv/license')->isTrialVersion($createHash) || is_null($level)) {
            $is_trial = true;
        } else {
            $is_trial = false;
        }

        //get data
        $version        = Mage::helper('qquoteadv/license')->getMagentoVersion();
        $c2qVersion     = Mage::helper('qquoteadv/license')->getCart2QuoteVersion();
        $edition        = Mage::helper('qquoteadv/license')->getMagentoEdition();
        $phpVersion     = Mage::helper('qquoteadv/license')->getPHPVersion();
        $icVersion      = Mage::helper('qquoteadv/license')->getIonCubeVersion();
        $serverDomain   = Mage::helper('qquoteadv/license')->getCurrentDomain();
        $c2qEdition     = Mage::helper('qquoteadv/license')->getCart2QuoteEdition();
        $c2qLicense     = Mage::helper('qquoteadv/license')->getCart2QuoteLicense();
        $c2qExpireDate  = Mage::helper('qquoteadv/license')->getCart2QuoteExpiryDate();
        $c2qTrialStatus = Mage::helper('qquoteadv/license')->getCart2QuoteTrialExpired();
        $n2oVersion     = Mage::helper('qquoteadv/license')->getNot2OrderVersion();
        $crmVersion     = Mage::helper('qquoteadv/license')->getCRMaddonVersion();

        //prepare data
        if (!empty($c2qVersion[0]) && isset($c2qVersion[0])) {
            $c2qVersion = (string)$c2qVersion[0];
        }

        if (!empty($crmVersion[0]) && isset($crmVersion[0])) {
            $crmVersion = (string)$crmVersion[0];
        }

        if (!empty($n2oVersion[0]) && isset($n2oVersion[0])) {
            $n2oVersion = (string)$n2oVersion[0];
        } else {
            $n2oVersion = 'false';
        }

        $params = [
            "domain"            => $domain,
            "action"            => $action,
            "location"          => $location,
            "level"             => $level,
            "is_trial"          => $is_trial,
            "version"           => $version,
            "c2q_version"       => $c2qVersion,
            "edition"           => $edition,
            "quote_id"          => $quote_id,
            "php_version"       => $phpVersion,
            "ic_version"        => $icVersion,
            "server_domain"     => $serverDomain,
            "c2q_edition"       => $c2qEdition,
            "c2q_license"       => $c2qLicense,
            "c2q_expiredate"    => $c2qExpireDate,
            "c2q_trialstate"    => $c2qTrialStatus,
            "n2o_version"       => $n2oVersion,
            "crm_version"       => $crmVersion
        ];

        try {
            $client = Mage::getModel('qquoteadv/client')->sendRequest($params);

            if (isset($client['error'])) {
                Mage::log('Exception (logging): ' .$client['error']['errno'].': '.$client['error']['errormsg'], null, 'c2q_exception.log');
            }
        } catch (Exception $e) {
            Mage::log('Exception (logging): ' .$e->getMessage(), null, 'c2q_exception.log', true);
        }
    }
}
