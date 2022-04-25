<?php
/**
 * Copyright (c) 2011-2020  arvato Finance B.V.
 *
 * AfterPay reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of AfterPay.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    AfterPay
 * @package     Afterpay_Afterpay
 * @copyright   Copyright (c) 2011-2020 arvato Finance B.V.
 */

class Afterpay_Afterpay_Model_Request_Validate_Bankaccount extends Afterpay_Afterpay_Model_Request_Ordermanagement
{
    /**
     * @param Mage_Payment_Model_Info $paymentInfo
     * @return mixed
     * @throws Exception
     */
    public function sendValidateRequest($paymentInfo)
    {
        $responseParser = $this->getResponseParser();
        $api = $this->getApi();

        $additionalFields = $paymentInfo->getAdditionalInformation();
        $variables = $this->shopVariables();

        $api->setVars($variables)->setTestMode($this->getTestMode());
        $data = array(
            'bankCode' => $additionalFields['bankcode'],
            'bankAccount' => $additionalFields['bankaccount']
        );

        try {
            $response = $api->execute($data);
            $responseParser
                ->setResponse($response)
                ->setRequest($this)
                ->processResponse();
        } catch (Exception $exception) {
            // first log then throw to stop order creation
            $this->logException($exception);
            throw $exception;
        }
    }

    /**
     * @return false|Afterpay_Afterpay_Model_Api_Validate_Bankaccount
     */
    private function getApi()
    {
        return Mage::getModel('afterpay/api_validate_bankaccount');
    }

    /**
     * @return false|Afterpay_Afterpay_Model_Response_Validate_Bankaccount
     */
    private function getResponseParser()
    {
        return Mage::getModel('afterpay/response_validate_bankaccount');
    }
}
