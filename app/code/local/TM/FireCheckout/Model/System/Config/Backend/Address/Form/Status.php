<?php

class TM_FireCheckout_Model_System_Config_Backend_Address_Form_Status
    extends Mage_Core_Model_Config_Data
{
    /**
     * Retrieve attribute code
     *
     * @return string
     */
    protected function _getAttributeCode()
    {
        $mapping = array(
            'street1' => 'street',
            'region'  => 'region_id'
        );
        if (array_key_exists($this->getField(), $mapping)) {
            return $mapping[$this->getField()];
        }
        return $this->getField();
    }

    /**
     * Retrieve attribute objects
     *
     * @return array
     */
    protected function _getAttribute()
    {
        return Mage::getSingleton('eav/config')
            ->getAttribute('customer_address', $this->_getAttributeCode());
    }

    /**
     * Get attribute values for requested field value
     *
     * @return array
     */
    protected function _getAttributeValues()
    {
        $valueConfig = array(
            'hidden'   => array('is_required' => 0, 'is_visible' => 0),
            'optional' => array('is_required' => 0, 'is_visible' => 1),
            'required' => array('is_required' => 1, 'is_visible' => 1),
        );
        $data = $valueConfig[$this->getValue()];

        switch ($this->_getAttributeCode()) {
            case 'country_id':
                // $data['is_required'] = 0;
                $data['is_visible'] = 1;
                break;

            case 'region_id':
                // $data['is_required'] = 0;
                $data['is_visible'] = 1;
                break;

            case 'postcode':
                // can't use optional to keep admin interface working
                // manage customers -> new address
                $data['is_required'] = 1;
                $data['is_visible'] = 1;
                break;
        }

        return $data;
    }

    /**
     * Change field eav attribute config after saving the value
     *
     * @return TM_FireCheckout_Model_System_Config_Backend_Address_Form_Status
     */
    protected function _afterSave()
    {
        $result = parent::_afterSave();

        if (!$this->isValueChanged()) {
            return $result;
        }

        $attribute = $this->_getAttribute();
        if (!$attribute->getId()) {
            return $this;
        }

        if ($this->getWebsiteCode()) {
            $website = Mage::app()->getWebsite($this->getWebsiteCode());
            $dataFieldPrefix = 'scope_';
        } else {
            $website = null;
            $dataFieldPrefix = '';
        }

        $data = $this->_getAttributeValues();
        if ($website) {
            $attribute->setWebsite($website);
            $attribute->load($attribute->getId());
        }
        $attribute->setValidateRules(null); // scope is not supported
        $attribute->setData($dataFieldPrefix . 'is_required', $data['is_required']);
        $attribute->setData($dataFieldPrefix . 'is_visible',  $data['is_visible']);
        $attribute->save();

        return $result;
    }

    /**
     * Processing object after delete data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterDelete()
    {
        $result = parent::_afterDelete();

        if ($this->getWebsiteCode()) {
            $website = Mage::app()->getWebsite($this->getWebsiteCode());
            $attribute = $this->_getAttribute();
            if ($attribute->getId()) {
                $attribute->setWebsite($website);
                $attribute->load($attribute->getId());
                $attribute->setData('scope_is_required', null);
                $attribute->setData('scope_is_visible',  null);
                $attribute->save();
            }
        }

        return $result;
    }
}
