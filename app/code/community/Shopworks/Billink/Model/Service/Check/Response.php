<?php

/**
 * Class Shopworks_Billink_Model_ServiceCheckResponse
 *
 * Response object that is created after calling the validate method on the Billink service wrapper
 */
class Shopworks_Billink_Model_Service_Check_Response
{
    //Status codes
    //Are used privately to store what the response status is
    const CHECK_RESULT_ERROR = -1;
    const CHECK_RESULT_CUSTOMER_REFUSED = 0;
    const CHECK_RESULT_CUSTOMER_ALLOWED = 1;

    /**
     * The status of the validation request. The value of this attribute should be on off the class
     * constant CHECK_RESULT values
     * @var bool
     */
    private $_result;
    /**
     * @var string
     */
    private $_code;
    /**
     * @var string
     */
    private $_description;
    /**
     * @var string
     */
    private $_uuid;

    /**
     * Used by the service to set the status for this object
     * @param $code
     * @param $description
     */
    public function setError($code, $description)
    {
        $this->_code = $code;
        $this->_description = $description;
        $this->_result = self::CHECK_RESULT_ERROR;
    }

    /**
     * Used by the servive to set the status for this object
     * @param bool $customerAccepted
     * @param string $code
     * @param string $uuid
     */
    public function setSuccess($customerAccepted, $code, $uuid='')
    {
        $this->_code = $code;

        if($customerAccepted)
        {
            $this->_result = self::CHECK_RESULT_CUSTOMER_ALLOWED;
            $this->_uuid = $uuid;
        }
        else
        {
            $this->_result = self::CHECK_RESULT_CUSTOMER_REFUSED;
        }
    }

    #region public getters methods

    /**
     * @return bool
     */
    public function isCustomerAllowed()
    {
        return $this->_result == self::CHECK_RESULT_CUSTOMER_ALLOWED;
    }

    /**
     * @return bool
     */
    public function isCustomerRefused()
    {
        return $this->_result == self::CHECK_RESULT_CUSTOMER_REFUSED;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->_result == self::CHECK_RESULT_ERROR;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->_uuid;
    }

    #endregion
}
