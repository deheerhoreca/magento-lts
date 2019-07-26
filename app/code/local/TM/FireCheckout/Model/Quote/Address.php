<?php

class TM_FireCheckout_Model_Quote_Address extends TM_FireCheckout_Model_Quote_Address_Abstract
// Unirgy_DropshipSplit_Model_Quote_Address
{
    /**
     * List of errors
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Validate address attribute values
     *
     * @return bool
     */
    public function validate()
    {
        $this->_resetErrors();

        $this->implodeStreetAddress();

        $formConfig = Mage::getStoreConfig('firecheckout/address_form_status');

        if (!Zend_Validate::is($this->getFirstname(), 'NotEmpty')) {
            $this->addError(Mage::helper('customer')->__('Please enter the first name.'));
        }
        if (!Zend_Validate::is($this->getLastname(), 'NotEmpty')) {
            $this->addError(Mage::helper('customer')->__('Please enter the last name.'));
        }

        if ('required' === $formConfig['company']
            && !Zend_Validate::is($this->getCompany(), 'NotEmpty')) {

            $this->addError(Mage::helper('customer')->__('Please enter the company.'));
        }

        if ('required' === $formConfig['street1']
            && !Zend_Validate::is($this->getStreet(1), 'NotEmpty')) {

            $this->addError(Mage::helper('customer')->__('Please enter the street.'));
        }

        if ('required' === $formConfig['city']
            && !Zend_Validate::is($this->getCity(), 'NotEmpty')) {

            $this->addError(Mage::helper('customer')->__('Please enter the city.'));
        }

        if ('required' === $formConfig['telephone']
            && !Zend_Validate::is($this->getTelephone(), 'NotEmpty')) {

            $this->addError(Mage::helper('customer')->__('Please enter the telephone number.'));
        }

        if ('required' === $formConfig['fax']
            && !Zend_Validate::is($this->getFax(), 'NotEmpty')) {

            $this->addError(Mage::helper('customer')->__('Please enter the fax.'));
        }

        $_havingOptionalZip = Mage::helper('directory')->getCountriesWithOptionalZip();
        if ('required' === $formConfig['postcode']
            && !in_array($this->getCountryId(), $_havingOptionalZip)
            && !Zend_Validate::is($this->getPostcode(), 'NotEmpty')) {

            $this->addError(Mage::helper('customer')->__('Please enter the zip/postal code.'));
        }

        if ('required' === $formConfig['country_id']
            && !Zend_Validate::is($this->getCountryId(), 'NotEmpty')) {

            $this->addError(Mage::helper('customer')->__('Please enter the country.'));
        }

        if ('required' === $formConfig['region']
            && $this->getCountryModel()->getRegionCollection()->getSize()
            && !Zend_Validate::is($this->getRegionId(), 'NotEmpty')) {

            $this->addError(Mage::helper('customer')->__('Please enter the state/province.'));
        }

        Mage::dispatchEvent('customer_address_validation_after', array('address' => $this));

        $errors = $this->_getErrors();

        $this->_resetErrors();

        if (empty($errors) || $this->getShouldIgnoreValidation()) {
            return true;
        }
        return $errors;
    }

    /**
     * Add error
     *
     * @param $error
     * @return Mage_Customer_Model_Address_Abstract
     */
    public function addError($error)
    {
        $this->_errors[] = $error;
        return $this;
    }

    /**
     * Retreive errors
     *
     * @return array
     */
    protected function _getErrors()
    {
        return $this->_errors;
    }

    /**
     * Reset errors array
     *
     * @return Mage_Customer_Model_Address_Abstract
     */
    protected function _resetErrors()
    {
        $this->_errors = array();
        return $this;
    }
}
