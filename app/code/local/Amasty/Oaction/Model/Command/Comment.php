<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Model_Command_Comment extends Amasty_Oaction_Model_Command_Abstract
{
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label = 'Add Comment';
        $this->_fieldLabel = 'Message';
    }

    /**
     * Executes the command
     *
     * @param array $ids product ids
     * @param string $val field value
     * @return string success message if any
     */
    public function execute($ids, $val)
    {
        $success = parent::execute($ids, $val);

        $hlp = Mage::helper('amoaction');

        $message = trim($val);
        if (!$message) {
            $this->_errors[] = $hlp->__('Message can not be empty');
            return $success;
        }

        $numAffectedOrders = 0;

        foreach ($ids as $id) {
            $order = Mage::getModel('sales/order')->load($id);
            $orderCode = $order->getIncrementId();

            try {
                $historyItem = $order->addStatusHistoryComment($message);

                //notify customer
                $notify = Mage::getStoreConfig('amoaction/comment/notify', $order->getStoreId());
                if ($notify) {
                    $historyItem->setIsCustomerNotified(1)->save();
                    $order->sendOrderUpdateEmail(true, $message);
                }
                $order->save();

                ++$numAffectedOrders;
            } catch (Exception $e) {
                $err = $e->getMessage();
                $this->_errors[] = $hlp->__('Can not add a comment to order #%s: %s', $orderCode, $err);
            }
            $order = null;
            unset($order);
        }

        if ($numAffectedOrders) {
            $success = $hlp->__('Total of %d order(s) have been updated with new comment.',
                $numAffectedOrders);
        }

        return $success;
    }

    protected function _getValueField($title)
    {
        $field = array('amoaction_value' => array(
            'name'   => 'amoaction_value',
            'type'   => 'text',
            'class'  => 'required-entry',
            'label'  => $title,
        ));
        return $field;
    }
}