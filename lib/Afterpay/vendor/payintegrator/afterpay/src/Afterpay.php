<?php
 /**
 * Copyright (c) 2021 arvato Finance B.V.
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
 * @name        AfterPay Class
 * @author      AfterPay (plugins@afterpay.nl)
 * @description PHP Library to connect with AfterPay Post Payment services
 * @copyright   Copyright (c) 2021 arvato Finance B.V.
 */

namespace Afterpay;

/**
 * AfterPay Class
 */
#[AllowDynamicProperties]
class Afterpay
{
    /**
     * @var bool $useRest
     */
    public $useRest = FALSE;

    /**
     * @var Client $client
     */
    public $client;

    /**
     * @var array $authorization
     */
    public $authorization;

    /**
     * @var array $order
     */
    public $order;

    /**
     * @var string $modus
     */
    public $modus;

    /**
     * @var \stdClass $order_result
     */
    public $order_result;

    /**
     * Afterpay constructor.
     */
    public function __construct()
    {
        if ($this->useRest === FALSE) {
            $this->client = new SoapClient();
        } else {
            $this->client = new RestClient();
        }
    }

    /**
     * Force to use Rest client.
     *
     */
    public function setRest()
    {
        if (!$this->client instanceof RestClient) {
            $this->client = null;
            $this->client = new RestClient();
            $this->useRest = TRUE;
        }
    }

    /**
     * Create order information
     *
     * @param array $order
     * @param string $order_type
     */
    public function set_order($order, $order_type)
    {
        $this->client->setOrder($order, $order_type);
        $this->order = $this->client->getOrder();
    }

    /**
     * Function to create order lines
     *
     * @param string $product_id
     * @param string $description
     * @param int $quantity
     * @param int $unit_price
     * @param int $vat_category
     * @param int $vat_amount
     * @param int $googleProductCategoryId
     * @param string $googleProductCategory
     * @param string $productUrl
     * @param string $imageUrl
     * @param null $groupId
     */
    public function create_order_line(
        $product_id,
        $description,
        $quantity,
        $unit_price,
        $vat_category = null,
        $vat_amount = null,
        $googleProductCategoryId = null,
        $googleProductCategory = null,
        $productUrl = null,
        $imageUrl = null,
        $groupId = null
    )
    {
        $this->client->createOrderLine(
            $product_id,
            $description,
            $quantity,
            $unit_price,
            $vat_category,
            $vat_amount,
            $googleProductCategoryId,
            $googleProductCategory,
            $productUrl,
            $imageUrl,
            $groupId
        );
    }

    /**
     * Process request to SOAP/REST webservice
     *
     * @param array $authorization
     * @param string $mode
     * @param string|null $language
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function do_request($authorization, $mode, $language = null)
    {
        $this->client->doRequest($authorization, $mode, $language);
        $this->authorization = $this->client->getAuthorization();
        $this->modus = $this->client->getMode();
        $this->order_result = $this->client->getOrderResult();
    }

    /**
     * If order management is used set action to true;
     *
     * @param string $action
     */
    public function set_ordermanagement($action)
    {
        $this->client->setOrderManagement($action);
    }

    /**
     * Check validation error and give back readable error message
     *
     * @param string $failureCode
     * @return array|string
     */
    public function check_validation_error($failureCode) {
        return \Afterpay\check_validation_error($failureCode);
    }

    /**
     * Check rejection error and give back readable error message
     *
     * @param int $rejectionCode
     * @return array|string
     */
    public function check_rejection_error($rejectionCode) {
        return \Afterpay\check_rejection_error($rejectionCode);
    }

    /**
     * Expose client method to manually set order country
     * @param $country
     */
    public function setOrderCountry($country)
    {
        $this->client->setCountry($country);
    }
}
