<?php

/**
 * Class Shopworks_Billink_Model_Event_Observer_Shipment
 */
class Shopworks_Billink_Model_Event_Observer_Shipment
{
    /** @var Shopworks_Billink_Helper_Billink */
    private $_helper;

    /** @var Shopworks_Billink_Helper_Config */
    private $_billinkConfigHelper;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('billink/Billink');
        $this->_billinkConfigHelper = Mage::helper('billink/Config');
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function startBillinkWorkflow($observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = $observer->getEvent()->getData('shipment');
        $order = $shipment->getOrder();

        $isBillinkUsed = $order->getPayment()->getMethodInstance()->getCode() == Shopworks_Billink_Model_Payment_Method::PAYMENT_METHOD_BILLINK_CODE;

        //Only kickoff the workflow if billink is used for this order
        if($isBillinkUsed)
        {
            //Get workflow number from the order
            $workflowNumber = $order->getData('billink_workflow_number');
            //A long time ago, we did not store the workflow number with the order. So for orders without a workflow number
            //we just select a workflow to use
            if(!$workflowNumber)
            {
                $workflowNumber = $this->_billinkConfigHelper->getWorkflowNumber(true, $order->getStoreId());
            }

            $service = $this->_helper->getService($order->getStoreId());
            $result = $service->startWorkflow($order->getIncrementId(), $workflowNumber);

            /** @var Mage_Core_Model_Session $coreSession */
            $coreSession = Mage::getSingleton('core/session');

            if($result->hasError())
            {
                $coreSession->addError(
                    'Het starten van de Billink workflow is mislukt. Log in via de Billink portal om de workflow vanaf '
                    . 'daar te starten'
                );
            }
            else
            {
                $coreSession->addSuccess('De Billink workflow voor deze order is gestart');
            }
        }
    }
}