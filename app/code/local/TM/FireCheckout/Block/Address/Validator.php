<?php

class TM_FireCheckout_Block_Address_Validator extends Mage_Core_Block_Template
{
    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->_template) {
            $this->_template = 'tm/firecheckout/address/validator.phtml';
        }
        return $this->_template;
    }

    public function getTitle($type)
    {
        if ('billing' === $type) {
            return $this->__('Billing Address');
        } else {
            return $this->__('Shipping Address');
        }
    }

    /**
     * [getVerifiedAddressFields description]
     * @param  array|null $address [description]
     * @param  string     $type    [description]
     * @return [type]              [description]
     */
    public function getVerifiedAddressFields(array $address, $type = 'billing')
    {
        $nameToId = array(
            'Address1' => 'street2',
            'Address2' => 'street1',
            'City'     => 'city',
            'State'    => 'region_id',
            'Zip5'     => 'postcode'
        );

        $result = array();
        foreach ($address as $key => $value) {
            if (!isset($nameToId[$key])) {
                continue;
            }
            if ('State' === $key) {
                $value = Mage::getModel('directory/region')
                    ->loadByCode($value, 'US')
                    ->getId();
            } elseif ('Zip5' === $key && !empty($address['Zip4'])) {
                $value .= '-' . $address['Zip4'];
            }
            $result[$type . ':' . $nameToId[$key]] = $value;
        }
        return $result;
    }

    public function getVerifiedAddressesJson()
    {
        $validator = $this->getValidator();
        $addresses = $validator->getAddresses();
        $result = array();
        foreach ($addresses as $type => $address) {
            if ($validator->isVerified($type)) {
                continue;
            }
            $verifiedAddresses = $validator->getVerifiedAddresses($type);
            $verifiedAddress = current($verifiedAddresses);
            $result[$type] = $this->getVerifiedAddressFields($verifiedAddress, $type);
        }
        return Mage::helper('core')->jsonEncode($result);
    }

    /**
     * Checks if autocorrection is possible
     *
     * Conditions:
     * 1. All address fields must be correct except postcode
     * 2. Usps must propose only one address per each customer addresses
     * 3. Postcode should be equal to zip5
     *
     * In other cases, autocorrection is not allowed.
     *
     * @return boolean
     */
    public function canUseZipAutocorrection()
    {
        if (!Mage::getStoreConfigFlag('firecheckout/address_verification/smart_zip_correction')) {
            return false;
        }

        $validator = $this->getValidator();
        foreach ($validator->getAddresses() as $addressType => $address) {
            // Address is correct - skip it
            if ($validator->isVerified($addressType)) {
                continue;
            }

            // 1. All fields must be correct except postcode
            $unverifiedFields = $validator->getUnverifiedFields($addressType);
            if (count($unverifiedFields) > 1 || !isset($unverifiedFields['postcode'])) {
                return false;
            }

            // 2. Usps must propose only one address per each customer addresses
            $verifiedAddresses = $validator->getVerifiedAddresses($addressType);
            if (!$verifiedAddresses || count($verifiedAddresses) > 1) {
                return false;
            }

            // 3. Postcode should be equal to zip5
            $verifiedAddress = current($verifiedAddresses);
            if (0 !== strcasecmp($address['postcode'], $verifiedAddress['Zip5'])) {
                return false;
            }
        }
        return true;
    }
}
