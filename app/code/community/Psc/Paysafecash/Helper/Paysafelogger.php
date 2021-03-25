<?php
class Psc_Paysafecash_Helper_PaysafeLogger extends Mage_Core_Helper_Abstract
{

    private $filename = "";

    public function __construct($filename = "")
    {
        $this->filename = Mage::getBaseDir() . '/var/log/paysafecash.log';
        setlocale(LC_TIME, "de_DE");
    }

    /**
     * log request to file
     * @param mixed $request
     * @param mixed $http
     * @param mixed $response
     * @return null
     */
    public function log($request, $http, $response)
    {
		file_put_contents($this->filename, strftime("Requested at: %A, %d. %B %Y %H:%M:%S\n"), FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, "\nRequest: ", FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, print_r($request, true), FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, "\nHTTP: ", FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, print_r($http, true), FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, "\nResponse: ", FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, print_r($response, true), FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, "--------------------------------------------\n", FILE_APPEND | LOCK_EX);
    }
}