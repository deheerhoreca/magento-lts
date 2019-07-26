<?php

class TM_FireCheckout_Model_Customer_Form extends Mage_Customer_Model_Form
{
    /**
     * Magento does not provide a method to return failed fields, so we
     * added our own
     *
     * @param  array  $data
     * @return array
     */
    public function getInvalidFields(array $data)
    {
        $fields = array();
        foreach ($this->getAttributes() as $attribute) {
            if ($this->_isAttributeOmitted($attribute)) {
                continue;
            }
            $dataModel = $this->_getAttributeDataModel($attribute);
            $dataModel->setExtractedData($data);
            if (!isset($data[$attribute->getAttributeCode()])) {
                $data[$attribute->getAttributeCode()] = null;
            }
            $result = $dataModel->validateValue($data[$attribute->getAttributeCode()]);
            if ($result !== true) {
                $fields[$attribute->getAttributeCode()] = $attribute->getAttributeCode();
            }
        }
        $regions = array('region', 'region_id');
        if (in_array('country_id', $fields) || array_intersect($fields, $regions)) {
            $fields['region'] = 'region';
            $fields['region_id'] = 'region_id';
        }
        return $fields;
    }
}
