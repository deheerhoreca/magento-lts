<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Searchautocomplete
 * @version    3.4.8
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Searchautocomplete_Block_View extends Mage_Catalog_Block_Product_List
{
    public function __construct()
    {
        parent::__construct();
        $this->addPriceBlockType('bundle', 'bundle/catalog_product_price', 'bundle/catalog/product/price.phtml');
        $this->addPriceBlockType(
            'giftcard', 'enterprise_giftcard/catalog_product_price', 'giftcard/catalog/product/price.phtml'
        );
        $this->addPriceBlockType('msrp', 'catalog/product_price', 'catalog/product/price_msrp.phtml');
        $this->addPriceBlockType('msrp_item', 'catalog/product_price', 'catalog/product/price_msrp_item.phtml');
        $this->addPriceBlockType('msrp_noform', 'catalog/product_price', 'catalog/product/price_msrp_noform.phtml');
    }

    public function isCanShowAllResultsButton()
    {
        return Mage::helper('searchautocomplete/config')->getInterfaceShowAllResultsButton();
    }

    public function getItems()
    {
        $searchedProductCollection = Mage::getModel('searchautocomplete/search')->search(
            Mage::helper('searchautocomplete')->getSearchedQuery()
        );

        $result = array();
        if (!is_null($searchedProductCollection)) {
            if (!$thumbnailSize = (int)Mage::helper('searchautocomplete/config')->getInterfaceThumbnailWidth()) {
                $thumbnailSize = 75;
            }

            $searchedWords = Mage::helper('searchautocomplete')->getSearchedWords();
            $itemPattern = Mage::helper('searchautocomplete/config')->getInterfaceItemTemplate();
            $thumbnailUrlPresents = (false !== strpos($itemPattern, '{thumbnail_url}'));


            foreach ($searchedProductCollection as $_product)
            {
                $productUrl = $_product->getProductUrl();
                $priceHTML = $this->getPriceHtml($_product, true);
                $info = $itemPattern;
                foreach (Mage::helper('searchautocomplete')->getUsedAttributes() as $code) {
                    $data = $_product->getData($code);
                    if (is_null($data)) {
                        $data = '';
                    }
                    if (!is_string($data)) {
                        continue;
                    }
                    $data = Mage::helper('catalog/output')->productAttribute($_product, $data, $code);
                    $data = $this->decorateWords($searchedWords, $data, '<strong class="searched-words">', '</strong>');
                    $info = str_replace('{' . $code . '}', $data, $info);
                }
                // adding special fields
                $info = str_replace('{price}', $priceHTML, $info);
                $info = str_replace('{product_url}', $productUrl, $info);

                if ($thumbnailUrlPresents) {
                    $info = str_replace(
                        '{thumbnail_url}',
                        $_product->getThumbnailUrl($thumbnailSize, $thumbnailSize),
                        $info
                    );
                }

                array_push($result, array(
                    'content' => str_replace('"', '\'', $info),
                    'url' => $productUrl,
                ));
            }
        }

        return $result;
    }

    public function findNearest($haystack, $needles, $offset)
    {
        $haystackL = strtolower($haystack);
        $nearestWord = '';
        $nearestPos = 999999;
        foreach ($needles as $needle)
            if ($needle
                && false !== ($pos = strpos($haystackL, strtolower($needle), $offset))
                && $nearestPos > $pos
            ) {
                $nearestPos = $pos;
                $nearestWord = substr($haystack, $pos, strlen($needle));
            }
        if ($nearestWord) return array('pos' => $nearestPos, 'word' => $nearestWord);
        else return false;
    }

    public function decorateWords($words, $subject, $before, $after)
    {
        $replace = array();
        $lenSubject = strlen($subject);
        for ($pos = 0; $pos < $lenSubject && (false !== $nearest = $this->findNearest($subject, $words, $pos));) {
            $replace[$nearest['pos']] = $nearest['word'];
            $pos = $nearest['pos'] + strlen($nearest['word']);
        }

        $res = '';
        $pos = 0;
        foreach ($replace as $start => $word)
        {
            $res .= substr($subject, $pos, $start - $pos) . $before . $word . $after;
            $pos = $start + strlen($word);
        }
        $res .= substr($subject, $pos);

        return $res;
    }

    /**
     * @param string $query
     * @return string
     */
    public function getResultUrl($query)
    {
        if (Mage::helper('searchautocomplete')->canUseADVSearch()) {
            return Mage::helper('awadvancedsearch/catalogsearch')->getResultUrl($query);
        }
        return Mage::helper('catalogsearch')->getResultUrl($query);
    }
}