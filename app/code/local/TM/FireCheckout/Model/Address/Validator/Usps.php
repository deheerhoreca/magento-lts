<?php

class TM_FireCheckout_Model_Address_Validator_Usps extends Mage_Usa_Model_Shipping_Carrier_Usps
{
    protected $_result = null;

    protected $_addresses = array();

    protected $_isVerified = array();

    protected $_unverifiedFields = array();

    public function isValid()
    {
        if (!$this->getAddresses()) {
            return true;
        }

        $userId  = $this->getConfigData('userid');
        if (empty($userId)) {
            $this->_result['error'] = Mage::helper('firecheckout')->__(
                'USPS user id is not correct. See the configuration at System > Configuration > Shipping Methods > USPS'
            );
            return false;
        }
        $request = "<AddressValidateRequest USERID='{$userId}'>";
        foreach ($this->getAddresses() as $type => $address) {
            $regionCode = Mage::getModel('directory/region')
                ->load($address['region_id'])
                ->getCode();

            $address1 = '';
            if (strpos($address['postcode'], '-') !== false) {
                list($zip5, $zip4) = explode('-', $address['postcode']);
            } else {
                $zip5 = $address['postcode'];
                $zip4 = '';
            }
            $request .= '<Address ID="' . $type . '">';
            if (isset($address['street'][1])) {
                // Address Line 1 is used to provide an apartment or suite number, if applicable.
                // https://www.usps.com/business/web-tools-apis/address-information-api.htm
                $address1 = $address['street'][1];
            }
            $request .= '<Address1>' . $address1 . '</Address1>';
            $request .= '<Address2>' . $address['street'][0] . '</Address2>';
            $request .= '<City>' . $address['city'] . '</City>';
            $request .= '<State>' . $regionCode . '</State>';
            $request .= '<Zip5>' . $zip5 . '</Zip5>';
            $request .= '<Zip4>' . $zip4 . '</Zip4>';
            $request .= '</Address>';
        }
        $request .= "</AddressValidateRequest>";

        $responseBody = $this->_getCachedQuotes($request);
        if ($responseBody === null) {
            $debugData = array('request' => $request);
            try {
                $url = $this->getConfigData('gateway_url');
                if (!$url) {
                    $url = $this->_defaultGatewayUrl;
                }
                $client = new Zend_Http_Client();
                $client->setUri($url);
                $client->setConfig(array('maxredirects'=>0, 'timeout'=>30));
                $client->setParameterGet('API', 'Verify');
                $client->setParameterGet('XML', $request);
                $response = $client->request();
                $responseBody = $response->getBody();

                $debugData['result'] = $responseBody;
                $this->_setCachedQuotes($request, $responseBody);
            } catch (Exception $e) {
                $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
                $responseBody = '';
            }
            $this->_debug($debugData);
        }
        $this->_result = $this->_parseXmlResponse($responseBody);

        return !$this->getError() && $this->isVerified();
    }

    public function getError()
    {
        if (isset($this->_result['error'])) {
            return $this->_result['error'];
        }
        return false;
    }

    /**
     * Retrieve errors for specified address type
     *
     * @param  string $type billing|shipping
     * @return mixed string|false
     */
    public function getAddressError($type)
    {
        foreach ($this->_result[$type] as $verifiedAddress) {
            if (isset($verifiedAddress['error'])) {
                return $verifiedAddress['error'];
            }
        }
        return false;
    }

    /**
     * Returns address verification result. true, when address matches
     * usps verified address.
     *
     * @param  string  $type billing|shipping
     * @return boolean
     */
    public function isVerified($type = null)
    {
        if (!count($this->_isVerified)) {
            $mapping = array(
                'Address1' => 'street',
                'Address2' => 'street',
                'City'     => 'city',
                'State'    => 'region_id',
                'Zip5'     => 'postcode'
            );

            foreach ($this->_result as $addressType => $verifiedAddresses) {
                $this->_isVerified[$addressType] = 0;
                $this->_unverifiedFields[$addressType] = array();

                if (!is_array($verifiedAddresses)) {
                    continue;
                }

                foreach ($verifiedAddresses as $verifiedAddress) {
                    if (isset($verifiedAddress['error'])) {
                        break;
                    }

                    $verified = true;
                    foreach ($verifiedAddress as $name => $value) {
                        if (!isset($mapping[$name])) {
                            continue;
                        }

                        $originalValue = $this->_addresses[$addressType][$mapping[$name]];
                        if ('Address1' === $name) {
                            $originalValue = isset($originalValue[1]) ? $originalValue[1] : '';
                        } elseif ('Address2' === $name) {
                            $originalValue = isset($originalValue[0]) ? $originalValue[0] : '';
                        } elseif ('State' === $name) {
                            // load region_code to compare it with usps returned value
                            $originalValue = Mage::getModel('directory/region')
                                ->load($originalValue)
                                ->getCode();
                        } elseif ('Zip5' === $name && !empty($verifiedAddress['Zip4'])) {
                            // usps returns zip4, so compare postcode against zip5-zip4
                            $value .= '-' . $verifiedAddress['Zip4'];
                        }

                        if (0 !== strcasecmp($value, $originalValue)) {
                            $this->_unverifiedFields[$addressType][$mapping[$name]] = $mapping[$name];
                            $verified = false;
                        }
                    }

                    if ($verified) {
                        // if address ruturned by usps is same as user entered,
                        // then we should not show verification window for this address
                        $this->_isVerified[$addressType] = 1;
                        break;
                    }
                }
            }
        }

        if ($type && isset($this->_isVerified[$type])) {
            return $this->_isVerified[$type];
        }
        return min($this->_isVerified);
    }

    /**
     * Retrieve list of fields, that does not match usps verified address
     *
     * @param  string $addressType
     * @return array
     */
    public function getUnverifiedFields($addressType)
    {
        if (!isset($this->_unverifiedFields[$addressType])) {
            return array();
        }
        return $this->_unverifiedFields[$addressType];
    }

    /**
     * Add address to validate
     *
     * @param array $address
     * @param string $type    billing|shipping
     */
    public function addAddress($address, $type)
    {
        $this->_addresses[$type] = $address;
    }

    /**
     * Retrieve addresses to validate
     *
     * @return array
     */
    public function getAddresses($type = null)
    {
        if (null === $type) {
            return $this->_addresses;
        }
        return $this->_addresses[$type];
    }

    /**
     * Retrieve verified address
     *
     * @param  string $type billing|shipping
     * @return mixed array|null
     */
    public function getVerifiedAddresses($type)
    {
        if (isset($this->_result[$type])) {
            return $this->_result[$type];
        }
        return null;
    }

    /**
     * Parse calculated rates
     *
     * @link http://www.usps.com/webtools/htm/Rate-Calculators-v2-3.htm
     * @param string $response
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function _parseXmlResponse($response)
    {
        $result = array();
        $xml    = simplexml_load_string($response);
        if (!is_object($xml)) {
            return array(
                'error' => 'Unable to parse usps response'
            );
        }

        if (is_object($xml->Number) && is_object($xml->Description) && (string)$xml->Description!='') {
            return array(
                'error' => (string)$xml->Description
            );
        }

        $result = array();
        if (is_object($xml->Address)) {
            $i = 0;
            foreach ($xml->Address as $address) {
                $addressType = (string)$address->attributes()->ID;

                if (is_object($address->Error) && (string)$address->Error->Description!='') {
                    $result[(string)$addressType][$i] = array(
                        'error' => (string)$address->Error->Description
                    );
                    $i++;
                    continue;
                }

                $result[$addressType][$i] = array(
                    'Address1' => is_object($address->Address1) ? (string)$address->Address1 : '',
                    'Address2' => is_object($address->Address2) ? (string)$address->Address2 : '',
                    'City'     => is_object($address->City)     ? (string)$address->City     : '',
                    'State'    => is_object($address->State)    ? (string)$address->State    : '',
                    'Zip5'     => is_object($address->Zip5)     ? (string)$address->Zip5     : '',
                    'Zip4'     => is_object($address->Zip4)     ? (string)$address->Zip4     : ''
                );
                $i++;
            }
        }

        return $result;
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    protected function _debug($debugData)
    {
        if ($this->getDebugFlag()) {
            Mage::getModel('core/log_adapter', 'firecheckout_' . $this->getCarrierCode() . '.log')
               ->setFilterDataKeys($this->_debugReplacePrivateDataKeys)
               ->log($debugData);
        }
    }
}
