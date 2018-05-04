<?php

class TM_RichSnippets_Model_System_Config_Source_Attribute
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $collection = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter();

            $this->_options[0] = '';
            foreach ($collection as $attribute) {
                $this->_options[$attribute->getAttributeCode()]
                    = $attribute->getFrontendLabel();
            }
        }
        return $this->_options;
    }
}
