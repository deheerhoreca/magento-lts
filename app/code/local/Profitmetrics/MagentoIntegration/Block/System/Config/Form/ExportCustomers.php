<?php
class Profitmetrics_Magentointegration_Block_System_Config_Form_ExportCustomers extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $url = Mage::helper('adminhtml')->getUrl(
            'adminhtml/profitmetrics/generateCustomers'
        );

        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id'        => 'download_customers',
                'label'     => 'Download Customers',
                'onclick'   => 'setLocation(\'' . $url . '\');'
            ));

        return $button->toHtml();
    }

}