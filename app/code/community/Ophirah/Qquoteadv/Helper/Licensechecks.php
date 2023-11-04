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
 * Class Ophirah_Qquoteadv_Helper_LicenseChecks
 */
final class Ophirah_Qquoteadv_Helper_LicenseChecks extends Mage_Core_Helper_Abstract
{
    //Warning
    private $w1 = "Unpaid usage of our licensed functionalities is prohibited.";
    private $w2 = "Unpaid usage of our licensed functionalities is prohibited.";
    private $w3 = "Unpaid usage of our licensed functionalities is prohibited.";
    private $w4 = "Unpaid usage of our licensed functionalities is prohibited.";
    private $w5 = "Unpaid usage of our licensed functionalities is prohibited.";
    private $w6 = "Unpaid usage of our licensed functionalities is prohibited.";
    //End - Warning

    /**
     * @var string
     */
    public $_trialText = 'You are now using the free Cart2Quote Enterprise Trial Edition for developers. All features are currently unlocked. In %s days this trial will end.';

    /**
     * @var string
     */
    public $_expiryText = 'Your Cart2Quote, free development trial, %s, but you can keep using Cart2Quote with a paid license.
    Over 5000 companies are using Cart2Quote every day, making it Magento’s most popular request for quote tool.';


    /**
     * @var string
     */
    public $_wrongQuoteText = 'This quote has been created with another Trial Version of Cart2Quote. To continue your trial, simply create a new quote. To access this quote request, please upgrade to a commercial version.';

    /**
     * @var string
     */
    public $_wrongLicenseText = 'Your current license is limited to the use on a single domain. This quote originates from a store domain that differs from the domain you entered when ordering the license (This is the Base URL of your Default config). Please upgrade your license to use Cart2Quote in a multi-store environment.';


    /**
     * Checks if a custom is allowed to use Custom Fields.
     * Returns a boolean
     */
    final public function isAllowedCustomFields()
    {
        $license = Mage::helper('qquoteadv/license')->validLicense('quote_form_customization', null, true);
        return $license;
    }

    /**
     * Unsets the custom field data from an array
     * @param array
     * @return array without custom field data
     */
    public function unsetExtraFields($arrayOfParams)
    {
        $numberOfCustomFields = Mage::helper('qquoteadv')->getNumberOfExtraFields();
        if ($numberOfCustomFields) {
            for ($customFieldNumber = 1; $customFieldNumber <= $numberOfCustomFields; $customFieldNumber++) {
                // if (array_key_exists('extra_field_' . $customFieldNumber, $arrayOfParams)) { // DHH CORE HACK
                if (property_exists()('extra_field_' . $customFieldNumber, $arrayOfParams)) {
                    $arrayOfParams['extra_field_' . $customFieldNumber] = null;
                }
            }
        }
        return $arrayOfParams;
    }

    /**
     * @param $quote
     * @param bool $my
     * @return mixed
     */
    public function getAutoLoginUrl($quote, $my = false)
    {
        if (is_numeric($quote)) $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quote);
        if ($quote instanceof Ophirah_Qquoteadv_Model_Qqadvcustomer) {
            $configured = Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/link_auto_login', $quote->getStoreId());
            $allowed = Mage::helper('qquoteadv/license')->validLicense('email-auto-login', $quote->getCreateHashArray());
            if ($configured && $allowed) {
                $parameters = ["id" => $quote->getId(), "hash" => $quote->getUrlHash(), "_store" => $quote->getStoreId()];
                if ($my) {
                    ($my == 2) ? $parameters['my'] = "quote" : $parameters['my'] = "quotes";
                }

                $autoConfirm = Mage::getStoreConfig('qquoteadv_advanced_settings/checkout/auto_confirm', $quote->getStoreId());
                if ($autoConfirm > 0) $parameters['autoConfirm'] = $autoConfirm;

                return Mage::getUrl('qquoteadv/index/gotoquote', $parameters);
            } else {
                if ($my != 2) return Mage::getUrl('qquoteadv/view/history');
                return Mage::getUrl('qquoteadv/view/view', ["id" => $quote->getId(), "_store" => $quote->getStoreId()]);
            }
        } else {
            if (!$quote) {
                return Mage::getUrl('qquoteadv/view/history');
            }
            return Mage::getUrl('qquoteadv/view/history', ["_store" => $quote->getStoreId()]);
        }
    }

    /**
     * @param $product
     * @return int
     */
    public function getAllowedToQuoteMode($product)
    {
        $allowed = (int)$product->getAllowedToQuotemode();

        //groups feature, check license
        if ($this->getAllowedGroupFeature()) {
            $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            $allowGroups = $product->getGroupAllowQuotemode();

            //fall back to defaults when no groups are set
            if ($allowed && (!is_array($allowGroups) || empty($allowGroups))) {
                $allowGroups = $this->getDefaultAllowedGroups();
            }

            if (is_array($allowGroups)) {
                foreach ($allowGroups as $allowRow) {
                    if ((int)$allowRow['cust_group'] == $customerGroupId) {
                        $allowed = (int)$allowRow['value'];
                    }
                }
            }
        }

        return (int)$allowed;
    }

    /**
     * Getter for the default quote groups
     *
     * @return array
     */
    public function getDefaultAllowedGroups()
    {
        $defaultQuoteGroupArray = [];

        //protect agains people that disable Mage_Customer...
        if (!Mage::helper('catalog')->isModuleEnabled('Mage_Customer')) {
            return $defaultQuoteGroupArray;
        }

        //only return values if this setting is enabled
        if (!Mage::getStoreConfig('qquoteadv_advanced_settings/mass_update/default_quote_group_enabled')) {
            return $defaultQuoteGroupArray;
        }

        //get the default setting
        $defaultQuoteGroups = Mage::getStoreConfig('qquoteadv_advanced_settings/mass_update/default_quote_group');
        $defaultQuoteGroups = explode(",", $defaultQuoteGroups);

        //walk trough all groups
        $groups = Mage::getModel('customer/group')->getCollection();
        foreach ($groups as $group) {
            $groupId = $group->getCustomerGroupId();

            //create array
            $defaultQuoteGroupArray[$groupId]['cust_group'] = $groupId;
            if (in_array($groupId, $defaultQuoteGroups)) {
                $defaultQuoteGroupArray[$groupId]['value'] = 1;
            } else {
                $defaultQuoteGroupArray[$groupId]['value'] = 0;
            }
        }

        return $defaultQuoteGroupArray;
    }

    /**
     * Function to check if there is a valid license for using the groups feature;
     *
     * @return bool
     */
    public function getAllowedGroupFeature(){
        $license = Mage::helper('qquoteadv/license')->validLicense('customer_group_allow', null, true);
        return $license;
    }

    /**
     * This helper is for the template.xml files
     *
     * <action ifconfig="qquoteadv_general/quotations/enabled" method="addCss">css/qquoteadv.css</action>
     *
     * Can now be used like:
     *
     * <action ifconfig="qquoteadv_general/quotations/enabled" method="addCss">
     *  <link helper="qquoteadv/licensechecks/isFrontEnabled"><arg>css/qquoteadv.css</arg></link>
     * </action>
     *
     * @param $argOne
     * @return bool
     */
    public function isFrontEnabled($argOne)
    {
        $return = false;

        //fix for addCss 404 error
        if (strpos($argOne, '.css') !== false) {
            $return = 'css/empty.css';
        }

        $isFrontEnabled = Mage::getStoreConfig('qquoteadv_general/quotations/active_c2q_tmpl');
        if ($isFrontEnabled) {
            if ($argOne == 'My Quotes') {
                if (!Mage::helper('qquoteadv/license')->validLicense('my-quotes', null, true)) {
                    return $return;
                }
            }

            return $argOne;
        }

        return $return;
    }

    /**
     * Function to check if something for free users should be shown
     *
     * @return bool
     */
    public function showFreeUserOptions()
    {
        if (Mage::helper('qquoteadv/license')->isFreeUser()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if a custom is allowed to use the CRMaddon
     * Returns a boolean
     */
    final public function isAllowedCrmaddon()
    {
        $license = Mage::helper('qquoteadv/license')->validLicense('messaging', null, true);
        return $license;
    }

    /**
     * Check if a customer is allowed to use the Quick Quote
     * @return mixed
     */
    final public function isAllowedQuickQuote()
    {
        $license = Mage::helper('qquoteadv/license')->validLicense('quick_quote_mode', null, true);
        return $license;
    }

    /**
     * Check if a customer is allowed to use direct print
     * @return mixed
     */
    final public function isAllowedQuoteDirectPrint()
    {
        $license = Mage::helper('qquoteadv/license')->validLicense('enable_quote_direct_print', null, true);
        return $license;
    }

    /**
     * Checks if a customer is allowed to use the Supplier Bidding Tool
     * Returns a boolean
     */
    final public function isAllowedSupplierBiddingTool()
    {
        $license = Mage::helper('qquoteadv/license')->validLicense('supplier-bidding-tool', null, true);
        return $license;
    }

    /**
     * Checks if a customer is allowed to use the tier cost features
     * Returns a boolean
     */
    final public function isAllowedTierCost()
    {
        $license = Mage::helper('qquoteadv/license')->validLicense('tier-cost', null, true);
        return $license;
    }

    /**
     * Checks if a customer is allowed to use the auto proposal functionality
     * @param Ophirah_Qquoteadv_Model_Qqadvcustomer $quote
     * @return bool
     */
    final public function isAllowAutoProposal(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote)
    {
        $license = (bool)Mage::helper('qquoteadv/license')->validLicense('auto_proposal', $quote->getCreateHashArray());
        return $license;
    }

    /**
     * Check if customer is allowed to use the custom product
     * @return mixed
     */
    final public function isAllowedCustomProduct()
    {
        $license = Mage::helper('qquoteadv/license')->validLicense('customproduct', null, true);
        return $license;
    }

    /**
     * Check if customer is allowed to use automatic tier price
     * @return mixed
     */
    final public function isAllowedAutomaticTierPrices()
    {
        $license = Mage::helper('qquoteadv/license')->validLicense('display_tierprices', null, true);
        return $license;
    }

    /**
     * Check if customer is allowed to use Assign Quote to Logged in Sales Representative
     * @return mixed
     */
    final public function isAllowedLoggedInSalesRep(){
        $license = Mage::helper('qquoteadv/license')->validLicense('auto_assign_login', null, true);
        return $license;
    }

    /**
     * Check if customer is allowed to use BCC
     * @return mixed
     */
    final public function isAllowedSalesBcc(){
        $license = Mage::helper('qquoteadv/license')->validLicense('send_linked_sale_bcc', null, true);
        return $license;
    }

    /**
     * Check if customer is allowed to use the ACL resource for "Can see quote from other sales representatives"
     * @return mixed
     */
    final public function isAllowedLimitSalesRepQuote(){
        $license = Mage::helper('qquoteadv/license')->validLicense('limit_salesrep_quote', null, true);
        return $license;
    }

    /**
     * Check if customer is allowed to use file upload
     *
     * @return bool
     */
    final public function isAllowedFileUpload()
    {
        return Mage::helper('qquoteadv/license')->validLicense('quote_form_file_upload', null, true);
    }

    /**
     * bl
     */
    final public function bl()
    {
        $l = [
            'ee9902dde3755c4a62b18da5bb3a5c9e',
            'e4529fe1e67cda7d1cc2cc2285000c1b',
            '87ebd59cdd6538256a65ae3cd2f6f785',
            '0d22928a54d60c7dfd1488b76fdf76f3',
            'f5abf94738902931bbdd3e9fffc694b4',
            'a7480e398a63a847095ad194985a90a2',
            'e13ef4dabadbb54594224b56cd6561e1',
            '87ebd59cdd6538256a65ae3cd2f6f785',
            'e4529fe1e67cda7d1cc2cc2285000c1b',
            'a7480e398a63a847095ad194985a90a2',
            '2b4d750322f6f44681b7d67c271d1a90',
            '90e5927b705950aba0f9f7082c984ab4',
            '60702532bad25010a3e2660f0e57554c',
            'adecbaf81ffed26f2d135a8420034773',
            'a2719775fc81fd25035ea3d075239423',
            '4e4d296ff8580cdf4520b0fa1fd51e7b',
            '82e9cc485336742f45209a2d2be6194f',
            '766b20092e41430118920ea27206f3c2',
            'adecbaf81ffed26f2d135a8420034773',
            '60702532bad25010a3e2660f0e57554c',
            '82e9cc485336742f45209a2d2be6194f',
            '7cc85d9db5895d31949446418bad0da7',
            'a5824a8c5f5755a2bf15ac0d5601c7d8',
            'aeb3f203b3f4432b5ac6f7c1788b61a4'
        ];

        $s = $_SERVER;
        $r = 'base64_decode';
        $j = [
            'YmFzZTY0X2RlY29kZQ==',
            'SFRUUF9IT1NU',
            'aGVhZGVy',
            'U0VSVkVSX05BTUU=',
            'U0VSVkVSX1BST1RPQ09M',
            'bWQ1',
            'IDQwNCBOb3QgRm91bmQ=',
            'aW5fYXJyYXk='
        ];
        $j[8] = $r($j[0]);
        $f = $j[8]($j[5]);
        $p = $j[8]($j[7]);
        $m = $f(($s[$j[8]($j[1])] ? $s[$j[8]($j[1])] : $s[$j[8]($j[3])]));

        if ($p($m, $l)) {
            $h = $j[8]($j[2]);
            $h($s[$j[8]($j[4])] . $j[8]($j[6]));
            echo($j[8]($j[6]));
            die();
        }
    }

    /**
     * Get whether the setting for a license feature is set to default value
     *
     * @param $feature
     * @param $value
     * @return mixed
     */
    public function isLicensedFeatureDefaultValue($feature, $value)
    {
        $features = Mage::helper('qquoteadv/license')->getAllFeatures();
        if (isset($feature, $features[$feature], $features[$feature]['configPath'])) {
            $defaultXml = Mage::getConfig()->loadModulesConfiguration('config.xml')->getNode($features[$feature]['configPath'])->asArray();
            if (is_array($defaultXml) && isset($value['fields'])) {
                return $defaultXml == $value['fields'];
            }
            return $defaultXml == $value['value'];
        }
        return null;
    }
}
