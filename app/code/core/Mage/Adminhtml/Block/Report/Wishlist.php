<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_Adminhtml
 */

/**
 * Adminhtml wishlist report page content block
 *
 * @package    Mage_Adminhtml
 * @SuppressWarnings("PHPMD.CamelCasePropertyName")
 */
class Mage_Adminhtml_Block_Report_Wishlist extends Mage_Adminhtml_Block_Template
{
    public $wishlists_count;
    public $items_bought;
    public $shared_count;
    public $referrals_count;
    public $conversions_count;
    public $customer_with_wishlist;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('report/wishlist.phtml');
    }

    public function _beforeToHtml()
    {
        $this->setChild('grid', $this->getLayout()->createBlock('adminhtml/report_wishlist_grid', 'report.grid'));

        $collection = Mage::getResourceModel('reports/wishlist_collection');

        [$customerWithWishlist, $wishlistsCount] = $collection->getWishlistCustomerCount();
        $this->setCustomerWithWishlist($customerWithWishlist);
        $this->setWishlistsCount($wishlistsCount);
        $this->setItemsBought(0);
        $this->setSharedCount($collection->getSharedCount());
        $this->setReferralsCount(0);
        $this->setConversionsCount(0);

        return $this;
    }
}
