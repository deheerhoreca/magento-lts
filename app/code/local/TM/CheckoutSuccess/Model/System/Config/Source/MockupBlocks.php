<?php

class TM_CheckoutSuccess_Model_System_Config_Source_MockupBlocks
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('checkoutsuccess');
        $array = array(
            array(
                'value' => 'sales.order.info',
                'label' => $helper->__('Shipping & Payment Info')
            ),
            array(
                'value' => 'sales.order.view',
                'label' => $helper->__('Order Details')
            ),
            array(
                'value' => 'sales.recurring.profile.schedule',
                'label' => $helper->__('Recurr. Profile schedule')
            ),
            array(
                'value' => 'checkoutsuccess.thank.you',
                'label' => $helper->__('"Thank you" note')
            ),
            array(
                'value' => 'checkoutsuccess.additional',
                'label' => $helper->__('Additional Info')
            ),
            array(
                'value' => 'checkoutsuccess.quick.register',
                'label' => $helper->__('Quick Registration')
            ),
            array(
                'value' => 'checkoutsuccess.cms.1',
                'label' => $helper->__('CMS Block #1')
            ),
            array(
                'value' => 'checkoutsuccess.cms.2',
                'label' => $helper->__('CMS Block #2')
            ),
            array(
                'value' => 'checkoutsuccess.cms.3',
                'label' => $helper->__('CMS Block #3')
            ),
            array(
                'value' => 'checkoutsuccess.cms.4',
                'label' => $helper->__('CMS Block #4')
            )
        );

        $blocks = new Varien_Object();
        $blocks->setValue($array);

        Mage::dispatchEvent(
            'checkoutsuccess_get_mockup_blocks_array_after',
            array('blocks' => $blocks)
        );

        return $blocks->getValue();
    }

}
