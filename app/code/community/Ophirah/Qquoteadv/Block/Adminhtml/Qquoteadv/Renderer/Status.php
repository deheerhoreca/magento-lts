<?php
/**
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
 * @category  Ophirah
 * @package   Qquoteadv
 * @copyright Copyright (c) 2020 Cart2Quote B.V. (https://www.cart2quote.com)
 * @license   https://www.cart2quote.com/ordering-licenses(https://www.cart2quote.com)
 */

/**
 * Class Ophirah_Qquoteadv_Block_Adminhtml_Qquoteadv_Renderer_Status
 */
class Ophirah_Qquoteadv_Block_Adminhtml_Qquoteadv_Renderer_Status
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Show status and substatus in Grid
		 * 
		 * DHH CORE HACK
     *
     * @param  Varien_Object $row
     * @return array
     */
    public function render(Varien_Object $row)
    {
        $quoteStatusNL = $this->vendorRender($row);
                
        // Add a colored background to the status
        // Cover all options defined in Ophirah_Qquoteadv_Model_Status::getGridOptionArray():
        //
        // const STATUS_BEGIN = 1;
        // const STATUS_BEGIN_ACTION_OWNER = 2;
        // const STATUS_BEGIN_ACTION_CUSTOMER = 3;
        // const STATUS_PROPOSAL_BEGIN = 10;
        // const STATUS_PROPOSAL_BEGIN_ACTION_OWNER = 11;
        // const STATUS_PROPOSAL_BEGIN_ACTION_CUSTOMER = 12;
        // const STATUS_REQUEST = 20;
        // const STATUS_REQUEST_EXPIRED = 21;
        // const STATUS_REQUEST_ACTION_OWNER = 22;
        // const STATUS_REQUEST_ACTION_CUSTOMER = 23;
        // const STATUS_REJECTED = 30;
        // const STATUS_CANCELED = 40;
        // const STATUS_PROPOSAL = 50;
        // const STATUS_PROPOSAL_EXPIRED = 51;
        // const STATUS_PROPOSAL_SAVED = 52;
        // const STATUS_AUTO_PROPOSAL = 53;
        // const STATUS_CONFIRMED_ALTERNATE = 54;
        // const STATUS_PROPOSAL_ACTION_OWNER = 56;
        // const STATUS_PROPOSAL_ACTION_CUSTOMER = 57;
        // const STATUS_DENIED = 60;
        // const STATUS_CONFIRMED = 70;
        // const STATUS_ORDERED = 71;
        // const STATUS_PRINT_ONLY = 80;
        $statusColor = match($quoteStatusNL) {
            Mage::helper("qquoteadv")->__("STATUS_BEGIN") 									=> "#F9EECA", // Light Yellow
            Mage::helper("qquoteadv")->__("STATUS_BEGIN_ACTION_OWNER") 			=> "#F9EECA", // Light Yellow
            Mage::helper("qquoteadv")->__("STATUS_BEGIN_ACTION_CUSTOMER") 	=> "#F9EECA", // Light Yellow
            Mage::helper("qquoteadv")->__("STATUS_PROPOSAL_BEGIN") 					=> "#F9EECA", // Light Yellow
            Mage::helper("qquoteadv")->__("STATUS_PROPOSAL_SENT") 					=> "#D9EAD3", // Light Green
            Mage::helper("qquoteadv")->__("STATUS_PROPOSAL_DECLINED") 			=> "#F4CCCC", // Light Red
            Mage::helper("qquoteadv")->__("STATUS_PROPOSAL_ACCEPTED") 			=> "#C9DAF8", // Light Blue
            Mage::helper("qquoteadv")->__("STATUS_PROPOSAL_EXPIRED") 				=> "#EAD1DC", // Light Pink
            Mage::helper("qquoteadv")->__("STATUS_PROPOSAL_SAVED") 					=> "#D9EAD3", // Light Green
            Mage::helper("qquoteadv")->__("STATUS_AUTO_PROPOSAL") 					=> "#D9EAD3", // Light Green
            Mage::helper("qquoteadv")->__("STATUS_CONFIRMED_ALTERNATE") 		=> "#C9DAF8", // Light Blue
            Mage::helper("qquoteadv")->__("STATUS_CONFIRMED") 							=> "#C9DAF8", // Light Blue
            Mage::helper("qquoteadv")->__("STATUS_ORDERED") 								=> "#C9DAF8", // Light Blue
            Mage::helper("qquoteadv")->__("STATUS_REQUEST") 								=> "#F9EECA", // Light Yellow
            Mage::helper("qquoteadv")->__("STATUS_REQUEST_EXPIRED") 				=> "#EAD1DC", // Light Pink
            Mage::helper("qquoteadv")->__("STATUS_REQUEST_ACTION_OWNER") 		=> "#F9EECA", // Light Yellow
            Mage::helper("qquoteadv")->__("STATUS_REQUEST_ACTION_CUSTOMER") => "#F9EECA", // Light Yellow
            Mage::helper("qquoteadv")->__("STATUS_REJECTED") 								=> "#F4CCCC", // Light Red
            Mage::helper("qquoteadv")->__("STATUS_CANCELED") 								=> "#F4CCCC", // Light Red
            Mage::helper("qquoteadv")->__("STATUS_PROPOSAL") 								=> "#D9EAD3", // Light Green
            Mage::helper("qquoteadv")->__("STATUS_DENIED") 									=> "#F4CCCC", // Light Red
            Mage::helper("qquoteadv")->__("STATUS_PRINT_ONLY") 							=> "#C9DAF8", // Light Blue
            default => null,
        };
        if ($statusColor) {
            $quoteStatus = "<span class=\"custom-color\" style=\"background-color:{$statusColor}\">{$quoteStatusNL}</span>";
        }
                
        return $quoteStatus;
    }
        
    /**
     * Show status and substatus in Grid
     *
     * @param  Varien_Object $row
     * @return array
     */
    public function vendorRender(Varien_Object $row)
    {
        // Retrieve values
        $status = (int)$row->getData("status");
        $substatus = $row->getData("substatus");
        // Get array of all statuses incl. substatuses
        $gridOptionArray = Mage::getModel("qquoteadv/status")->getGridOptionArray(true);

        // Build combined array if substatuses exists
        if ($substatus && Ophirah_Qquoteadv_Model_Substatus::substatuses()) {
            if (Mage::getModel("qquoteadv/substatus")->getParentStatus($substatus) == $status) {
                return $gridOptionArray[$substatus];
            } else {
                if (isset($gridOptionArray[$status])) {
                    return $gridOptionArray[$status];
                } else {
                    // If status is not found
                    // set a default status
                    //return Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_BEGIN;
                    return Mage::helper("qquoteadv")->__("STATUS_PROPOSAL_BEGIN");
                }
            }
        }

        // Return only main statuses
        if (isset($gridOptionArray[$status])) {
            return $gridOptionArray[$status];
        } else {
            // If status is not found
            // set a default status
            //return Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_BEGIN;
            return Mage::helper("qquoteadv")->__("STATUS_PROPOSAL_BEGIN");
        }
    }
}
