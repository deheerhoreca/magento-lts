<?php

namespace Paynl\Api;

use Curl\Curl;
use Paynl\Config;
use Paynl\Error;
use Paynl\Helper;

/**
 * Description of Api
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Api
{
    /**
     * @var int the version of the api
     */
    protected $version = 1;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var bool Is the ApiToken required for this API
     */
    protected $apiTokenRequired = false;
    /**
     * @var bool Is the serviceId required for this API
     */
    protected $serviceIdRequired = false;

    /**
     * @param $endpoint
     * @param null|int $version
     *
     * @return array
     *
     * @throws Error\Api
     * @throws Error\Error
     * @throws Error\Required\ApiToken
     */
    public function doRequest($endpoint, $version = null)
    {
        if ($version === null) {
            $version = $this->version;
        }
      
        $auth = $this->getAuth();
        $data = $this->getData();
        $uri = Config::getApiUrl($endpoint, (int) $version);

        /** @var Curl $curl */
        $curl = Config::getCurl();

        if (Config::getCAInfoLocation()) {
            // set a custom CAInfo file
            $curl->setOpt(CURLOPT_CAINFO, Config::getCAInfoLocation());
        }

        if (!empty($auth)) {
            $curl->setBasicAuthentication($auth['username'], $auth['password']);
        }      
        
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, Config::getVerifyPeer());

        $result = $curl->post($uri, $data);
        
        // DHH CORE HACK
        if(isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], ["185.127.111.252", "62.250.253.55"])) {
            $msg = str_repeat("=", 150).PHP_EOL.PHP_EOL
                ."DATE: ".date("c").PHP_EOL.PHP_EOL
                ."ENDPOINT: ".var_export($endpoint, true).PHP_EOL.PHP_EOL
                ."VERSION: ".var_export($version, true).PHP_EOL.PHP_EOL
                // ."AUTH:".PHP_EOL.var_export($auth, true).PHP_EOL.PHP_EOL
                ."DATA:".PHP_EOL.var_export($data, true).PHP_EOL.PHP_EOL
                ."URI: ".var_export($uri, true).PHP_EOL.PHP_EOL
                ."CURL: ".var_export($curl, true).PHP_EOL.PHP_EOL
                ."RESULT:".PHP_EOL.var_export($result, true).PHP_EOL;
            file_put_contents("./var/log/verbose-paynl.txt", $msg, FILE_APPEND | LOCK_EX);
        }

        if (isset($result->status) && $result->status === 'FALSE') {
            throw new Error\Api($result->error);
        }      

        if ($curl->error) {
            throw new Error\Error($curl->errorMessage);
        }
        
        return $this->processResult($result);
    }

    /**
     * @return array
     * @throws Error\Required
     */
    protected function getData()
    {
        if ($this->isServiceIdRequired()) {
            Helper::requireServiceId();

            $this->data['serviceId'] = Config::getServiceId();
        }
        return $this->data;
    }

    /**
     * @return array|null
     * @throws Error\Required\ApiToken
     */
    protected function getAuth()
    {
        if (!$this->isApiTokenRequired()) {
            return null;
        }

        Helper::requireApiToken();
        $tokenCode = Config::getTokenCode();
        $apiToken = Config::getApiToken();
        if (!$tokenCode) {
            $this->data['token'] = $apiToken;
            return null;
        }
        return array('username' => $tokenCode, 'password' => $apiToken);
    }

    /**
     * @return bool
     */
    public function isApiTokenRequired()
    {
        return $this->apiTokenRequired;
    }

    /**
     * @return bool
     */
    public function isServiceIdRequired()
    {
        return $this->serviceIdRequired;
    }

    /**
     * @param object|array $result
     *
     * @return array
     * @throws Error\Api
     */
    protected function processResult($result)
    {
        $output = Helper::objectToArray($result);

        if (! is_array($output)) {
            throw new Error\Api($output);
        }

        if (isset($output['result'])) {
            return $output;
        }

        if (
            isset($output['request']) &&
            $output['request']['result'] != 1 &&
            $output['request']['result'] !== 'TRUE') {
            throw new Error\Api($output['request']['errorId'] . ' - ' . $output['request']['errorMessage']);
        }

        return $output;
    }
}
