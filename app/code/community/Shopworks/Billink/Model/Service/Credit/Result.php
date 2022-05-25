<?php

/**
 * Class Shopworks_Billink_Model_Service_Credit_Result
 */
class Shopworks_Billink_Model_Service_Credit_Result
{
    private $_hasError;
    private $_code;
    private $_description;

    public function setSuccess($description)
    {
        $this->_hasError = false;
        $this->_description = $description;
    }

    /**
     * @param string $code
     * @param string $description
     */
    public function setError($code, $description)
    {
        $this->_hasError = true;
        $this->_code = $code;
        $this->_description = $description;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->_hasError;
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
}
