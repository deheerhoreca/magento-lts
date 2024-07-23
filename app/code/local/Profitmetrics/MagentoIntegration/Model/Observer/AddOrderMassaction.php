<?php

class Profitmetrics_MagentoIntegration_Model_Observer_AddOrderMassaction
{
    /**
     * @param $observer
     * @return void
     * @throws Exception
     */
    public function addOrdersResendMassAction($observer)
    {
        $block = $observer->getEvent()->getBlock();

        if (
            $block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction
            && $block->getRequest()->getControllerName() == 'sales_order'
        ) {
            $block->addItem('send_to_profitmetrics', array(
                'label' => Mage::helper('sales')->__('Resend to ProfitMetrics'),
                'url' => $block->getUrl('*/profitmetrics/massOrdersResend'),
            ));
        }
    }
}