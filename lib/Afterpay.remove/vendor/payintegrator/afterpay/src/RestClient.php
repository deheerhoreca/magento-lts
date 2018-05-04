<?php

namespace Afterpay;

 /**
 * Copyright (c) 2017  arvato Finance B.V.
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
 * @author      AfterPay (support@afterpay.nl)
 * @description PHP Library to connect with AfterPay Post Payment services
 * @copyright   Copyright (c) 2017 arvato Finance B.V.
 */


use GuzzleHttp;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class RestClient extends Client
{
    /**
     * Used for number_format() function as property
     */
    const THOUSANDS_SEP = '';

    /**
     * Used for number_format() function as property
     */
    const DEC_POINT = '.';

    /**
     * Used for number_format() function as property
     */
    const DECIMALS = 2;

    /**
     * @var GuzzleHttp\Client $restClient
     */
    private $restClient;

    /**
     * @var array $vatMapping
     */
    private $vatMapping = [
        1 => 'HighCategory',
        2 => 'LowCategory',
        3 => 'NullCategory',
        4 => 'NoCategory',
        5 => 'MiddleCategory',
        'fallback' => 'OtherCategory'
    ];

    /**
     * @var array $vatMappingDE
     */
    private $vatMappingDE = [
        1 => '19',
        2 => '7',
        3 => '0',
        4 => '0',
        5 => '0',
        'fallback' => '0'
    ];

    /**
     * @var string $requestUrl
     */
    private $requestUrl;

    /**
     * @var string $requestMethod
     */
    private $requestMethod;

    /**
     * @var \GuzzleHttp\Client $response
     */
    private $apiResponse;

    /**
     * @var array|\stdClass $orderResultTmp
     */
    private $orderResultTmp;

    /**
     * @var array $statusCodes
     */
    private $statusCodes = [
        'Accepted' => 'A',
        'Pending' => 'P',
        'Rejected' => 'W'];

    /**
     * @var array $resultId
     */
    private $resultId = [
        'Accepted' => '0',
        'Pending' => '4',
        'Rejected' => '3'];

    /**
     * @var array $additional
     */
    private $additional;

    /**
     * Function to create order line
     *
     * @param int $productId
     * @param string $description
     * @param int $quantity
     * @param int $unitPrice
     * @param int $vatCategory
     */
    public function createOrderLine(
        $productId,
        $description,
        $quantity,
        $unitPrice,
        $vatCategory = null,
        $vatAmount = null,
        $googleProductCategoryId = null,
        $googleProductCategory = null,
        $productUrl = null
    )
    {
        $unitPriceTemp = ((int)$unitPrice / 100);
        if ($this->orderAction === 'refund_partial') {
            $orderLine['refundType'] = 'Refund';
            $unitPriceTemp = $unitPriceTemp * -1;
        }
        $orderLine = [
            'productId' => substr($productId, 0, 49),
            'description' => $description,
            'quantity' => $quantity,
            'grossUnitPrice' => $unitPriceTemp
        ];
        if (isset($vatCategory)) {
            $orderLine['vatCategory'] = $this->setAfterpayVATMapping($vatCategory);
        }
        if ($vatAmount !== null) {
            $orderLine['vatAmount'] = $vatAmount;
            $orderLine['vatPercent'] = \Afterpay\calculateVatPercentage(abs($unitPriceTemp), $vatAmount);
        }
        if (isset($googleProductCategoryId)) {
            $orderLine['googleProductCategoryId'] = $googleProductCategoryId;
        }
        if (isset($googleProductCategory)) {
            $orderLine['googleProductCategory'] = $googleProductCategory;
        }
        if (isset($productUrl)) {
            $orderLine['productUrl'] = $productUrl;
        }

        $this->totalOrderAmount = ($this->totalOrderAmount + ($quantity * ((int)$unitPrice / 100)));
        $this->orderLines[] = $orderLine;
    }

    /**
     * @param int $vatCategory
     *
     * @return string
     */
    private function setAfterpayVATMapping($vatCategory)
    {
        if (array_key_exists($vatCategory, $this->vatMapping)) {
            return $this->vatMapping[$vatCategory];
        }
        return $this->vatMapping['fallback'];
    }

    /**
     * @param int $vatCategory
     *
     * @return string
     */
    private function setAfterpayVATMappingDE($vatCategory)
    {
        if (array_key_exists($vatCategory, $this->vatMappingDE)) {
            return $this->vatMappingDE[$vatCategory];
        }
        return $this->vatMappingDE['fallback'];
    }

    /**
     * If order management is used set action to true and update orderAction property;
     *
     * @param string $action
     */
    public function setOrderManagement($action)
    {
        $this->orderManagement = true;
        $this->orderAction = $action;
        $this->createDebugLine('Set order management to: ' . $this->orderAction);
    }

    /**
     * Create order information
     *
     * @param array $order
     * @param string $orderType
     */
    public function setOrder($order, $orderType)
    {
        $orderId = (array_key_exists('ordernumber', $order) ? $order['ordernumber'] : '');
        $this->setOrderType($orderType, $orderId);
        if ($this->orderType == 'OM') {
            switch ($this->orderAction) {
                case 'capture_full':
                    // Divide by 100 because default was sent in eurocents
                    $totalAmount = $order['totalamount']  / 100;
                    $this->order = [
                        'orderDetails' => [
                            'totalGrossAmount' => \Afterpay\convertPrice($totalAmount),
                        ],
                        'invoiceNumber' => $order['invoicenumber'],
                    ];
                    break;
                case 'capture_partial':
                    // Not divided by 100 because this is already done while creating orderlines
                    $totalGrossAmount = $this->totalOrderAmount;
                    $this->order = [
                        'orderDetails' => [
                            'totalGrossAmount' => \Afterpay\convertPrice($totalGrossAmount),
                            'items' => $this->orderLines,
                        ],
                        'invoiceNumber' => $order['invoicenumber'],
                    ];
                    break;
                case 'refund_full':
                    $this->order = '';
                    break;
                case 'refund_partial':
                    $this->order = [
                        'captureNumber' => $order['invoicenumber'],
                        'orderItems' => $this->orderLines,
                        'refundType' => 'Refund'
                    ];
                    break;
                case 'void':
                    // Divide by 100 because default was sent in eurocents
                    $totalGrossAmount = $this->totalOrderAmount / 100;
                    $this->order = [
                        'cancellationDetails' => [
                            'totalGrossAmount' => \Afterpay\convertPrice($totalGrossAmount),
                            'items' => $this->orderLines

                        ]
                    ];
                    break;
                default:
                    break;
            }
            // Check if there is a subcontract merchant id set, if so merge it with the order data
            if (array_key_exists('apiMerchantId', $order) && !(empty($order['apiMerchantId']))) {
                $this->order['references']['merchantId'] = $order['apiMerchantId'];
            }
            return;
        }

        $this->country = 'NL';

        if ($order['billtoaddress']['isocountrycode'] == 'BE') {
            $this->country = 'BE';
        } elseif ($order['billtoaddress']['isocountrycode'] == 'DE') {
            $this->country = 'DE';
        }

        // Check generic salutatation for billtoaddress
        if (isset($order['billtoaddress']['referenceperson']['gender'])) {
            switch($order['billtoaddress']['referenceperson']['gender']) {
                case 'M' :
                    $order['billtoaddress']['referenceperson']['gender'] = 'Mr';
                    break;
                case 'V' :
                    $order['billtoaddress']['referenceperson']['gender'] = 'Mrs';
                    break;
                case 'Herr' :
                    $order['billtoaddress']['referenceperson']['gender'] = 'Mr';
                    break;
                case 'Frau' :
                    $order['billtoaddress']['referenceperson']['gender'] = 'Mrs';
                    break;
            }
        }

        $this->order = [
            'customer' => [
                'customerCategory' => 'Person',
                'address' => [
                    'postalPlace' => $order['billtoaddress']['city'],
                    'streetNumber' => $order['billtoaddress']['housenumber'],
                    'countryCode' => $order['billtoaddress']['isocountrycode'],
                    'postalCode' => $order['billtoaddress']['postalcode'],
                    'street' => $order['billtoaddress']['streetname'],
                    'careOf' => (isset($order['billtoaddress']['careof']) ? $order['shiptoaddress']['careof'] : ''),
                ],
                'birthDate' => (isset($order['billtoaddress']['referenceperson']['dob'])
                    ? $order['billtoaddress']['referenceperson']['dob'] : ''),
                'email' => $order['billtoaddress']['referenceperson']['email'],
                'salutation' => (
                    isset(
                        $order['billtoaddress']['referenceperson']['gender']
                    ) ? $order['billtoaddress']['referenceperson']['gender'] : ''),
                'firstName' => (
                    isset(
                        $order['billtoaddress']['referenceperson']['firstname']
                    ) ? $order['billtoaddress']['referenceperson']['firstname'] : ''),
                'conversationLanguage' => $order['billtoaddress']['referenceperson']['isolanguage'],
                'lastName' => $order['billtoaddress']['referenceperson']['lastname'],
                'riskData' => [
                    'ipAddress' => $order['ipaddress'],
                    'existingCustomer' => (isset($order['existingcustomer']) ? $order['existingcustomer'] : ''),
                ],
            ]
        ];

        // Check if the phonenumber is set and not empty, then merge it with the data
        if (array_key_exists('phonenumber', $order['billtoaddress']['referenceperson']) 
        && $order['billtoaddress']['referenceperson']['phonenumber'] != '') {
            $this->order = array_merge_recursive($this->order,
                [
                    'customer' => [
                        'mobilePhone' => \Afterpay\cleanphone(
                            $order['billtoaddress']['referenceperson']['phonenumber'],
                            $order['billtoaddress']['isocountrycode']),
                            ],
                ]);
        }
        // Check if there is an additional information array, if so merge it with the order data
        if (array_key_exists('additional', $order)) {
            $this->order = array_merge_recursive($this->order,
                [
                    'additionalData' => [
                        'pluginProvider' => (
                            isset($order['additional']['pluginProvider']) ? $order['additional']['pluginProvider'] : ''
                        ),
                        'pluginVersion' => (
                            isset($order['additional']['pluginVersion']) ?$order['additional']['pluginVersion'] : ''
                        ),
                        'shopUrl' => (
                            isset($order['additional']['shopUrl']) ? $order['additional']['shopUrl'] : ''
                        ),
                        'shopPlatform' => (
                            isset($order['additional']['shopPlatform']) ? $order['additional']['shopPlatform'] : ''
                        ),
                        'shopPlatformVersion' => (
                            isset($order['additional']['shopPlatformVersion']) ?
                                $order['additional']['shopPlatformVersion'] : ''
                        ),
                    ],
                ]);
        }
        // Check if there is an housenumber addition, if so merge it with the order data
        if (array_key_exists('housenumberaddition', $order['billtoaddress'])
            && !(empty($order['billtoaddress']['housenumberaddition']))) {
            $this->order = array_merge_recursive($this->order,
                [
                    'customer' => [
                        'address' => [
                            'streetNumberAdditional' => $order['billtoaddress']['housenumberaddition']
                        ]
                    ]
                ]);
        }

        // Check generic salutatation for billtoaddress
        if (isset($order['shiptoaddress']['referenceperson']['gender'])) {
            switch($order['shiptoaddress']['referenceperson']['gender']) {
                case 'M' :
                    $order['shiptoaddress']['referenceperson']['gender'] = 'Mr';
                    break;
                case 'V' :
                    $order['shiptoaddress']['referenceperson']['gender'] = 'Mrs';
                    break;
                case 'Herr' :
                    $order['shiptoaddress']['referenceperson']['gender'] = 'Mr';
                    break;
                case 'Frau' :
                    $order['shiptoaddress']['referenceperson']['gender'] = 'Mrs';
                    break;
            }
        }

        // Check if the shiptoaddress differs from the billtoaddres, if so merge it to the order data
        if (!empty(\Afterpay\arrayRecursiveDiff($order['billtoaddress'], $order['shiptoaddress']))) {
            $this->order += [
                'deliveryCustomer' => [
                    'customerCategory' => 'Person',
                    'address' => [
                        'postalPlace' => $order['shiptoaddress']['city'],
                        'streetNumber' => $order['shiptoaddress']['housenumber'],
                        'countryCode' => $order['shiptoaddress']['isocountrycode'],
                        'postalCode' => $order['shiptoaddress']['postalcode'],
                        'street' => $order['shiptoaddress']['streetname'],
                        'careOf' => (isset($order['shiptoaddress']['careof']) ? $order['shiptoaddress']['careof'] : ''),
                    ],
                    'birthDate' => (isset($order['shiptoaddress']['referenceperson']['dob'])
                        ? $order['shiptoaddress']['referenceperson']['dob'] : ''),
                    'email' => $order['shiptoaddress']['referenceperson']['email'],
                    'salutation' => (
                        isset(
                            $order['shiptoaddress']['referenceperson']['gender']
                        ) ? $order['shiptoaddress']['referenceperson']['gender'] : ''),
                    'firstName' => (
                        isset(
                            $order['shiptoaddress']['referenceperson']['firstname']
                        ) ? $order['shiptoaddress']['referenceperson']['firstname'] : ''),
                    'conversationLanguage' => $order['shiptoaddress']['referenceperson']['isolanguage'],
                    'lastName' => $order['shiptoaddress']['referenceperson']['lastname'],
                ]
            ];
            if (array_key_exists('housenumberaddition', $order['shiptoaddress'])
                && !(empty($order['shiptoaddress']['housenumberaddition']))) {
                $this->order = array_merge_recursive($this->order,
                    [
                        'deliveryCustomer' => [
                            'address' => [
                                'streetNumberAdditional' => $order['shiptoaddress']['housenumberaddition']
                            ]
                        ]
                    ]);
            }
            if (array_key_exists('phonenumber', $order['shiptoaddress']['referenceperson'])
            && !(empty($order['shiptoaddress']['referenceperson']['phonenumber']))) {
                $this->order = array_merge_recursive($this->order,
                    [
                        'deliveryCustomer' => [
                            'mobilePhone' => \Afterpay\cleanphone(
                                $order['shiptoaddress']['referenceperson']['phonenumber'],
                                $order['shiptoaddress']['isocountrycode']),
                        ]
                    ]);
            }
        }

        $this->order += [
            'payment' => [
                'type' => 'Invoice'
            ],
            'order' => [
                'number' => $order['ordernumber'],
                'currency' => $order['currency'],
                'items' => $this->orderLines,
                'totalGrossAmount' => \Afterpay\convertPrice($this->totalOrderAmount)
            ]
        ];

        // Check if there is google analytics user id set, if so merge it with the order data
        if (array_key_exists('googleAnalyticsUserId', $order) && !(empty($order['googleAnalyticsUserId']))) {
            $this->order = array_merge_recursive($this->order,
                [
                    'order' => [
                        'googleAnalyticsUserId' => $order['googleAnalyticsUserId'],
                    ],
                ]);
        }
        // Check if there is google analytics client id set, if so merge it with the order data
        if (array_key_exists('googleAnalyticsClientId', $order) && !(empty($order['googleAnalyticsClientId']))) {
            $this->order = array_merge_recursive($this->order,
                [
                    'order' => [
                        'googleAnalyticsClientId' => $order['googleAnalyticsClientId'],
                    ],
                ]);
        }

        // Check if there is a subcontract merchant id set, if so merge it with the order data
        if (array_key_exists('apiMerchantId', $order) && !(empty($order['apiMerchantId']))) {
            $this->order['merchantId'] = $order['apiMerchantId'];
        }

        // Remove fields that are not filled
        if (empty($this->order['customer']['address']['careOf'])) unset($this->order['customer']['address']['careOf']);
        if (empty($this->order['customer']['birthDate'])) unset($this->order['customer']['birthDate']);
        if (empty($this->order['customer']['email'])) unset($this->order['customer']['email']);
        if (empty($this->order['customer']['salutation'])) unset($this->order['customer']['salutation']);
        if (empty($this->order['customer']['riskData']['ipAddress'])) unset(
            $this->order['customer']['riskData']['ipAddress']
        );
        if (empty($this->order['customer']['riskData']['existingCustomer'])) unset(
            $this->order['customer']['riskData']['existingCustomer']
        );
        if (empty($this->order['customer']['mobilePhone'])) unset($this->order['customer']['mobilePhone']);

        /* Country specific actions */
        // DE does not need vatCategory 
        if($this->country == 'DE') {
            if(isset($this->order['order']['items']) && is_array($this->order['order']['items'])) {
                foreach($this->order['order']['items'] as $orderline_key => $orderline_value) {
                    unset($this->order['order']['items'][$orderline_key]['vatCategory']);
                }
            }
        }
        // NL does not need vatPercent
        if($this->country == 'NL') {
            if(isset($this->order['order']['items']) && is_array($this->order['order']['items'])) {
                foreach($this->order['order']['items'] as $orderline_key => $orderline_value) {
                    unset($this->order['order']['items'][$orderline_key]['vatPercent']);
                }
            }
        }
    }

    /**
     * Set order types to correct webservice calls and function names
     *
     * @param string $orderType
     * @param string $orderNumber
     */
    private function setOrderType($orderType, $orderNumber = '')
    {
        $this->orderType = $orderType;
        switch ($this->orderAction) {
            case 'capture_full':
            case 'capture_partial':
                $this->requestUrl = sprintf('orders/%s/captures', $orderNumber);
                $this->requestMethod = 'POST';
                break;
            case 'refund_full':
            case 'refund_partial':
                $this->requestUrl = sprintf('orders/%s/refunds', $orderNumber);
                $this->requestMethod = 'POST';
                break;
            case 'void':
                $this->requestUrl = sprintf('orders/%s/voids', $orderNumber);
                $this->requestMethod = 'POST';
                break;
            default:
                $this->requestUrl = '';
                $this->requestUrl = 'checkout/authorize';
                $this->requestMethod = 'POST';
                break;
        }
    }

    /**
     * Process request to REST webservice
     *
     * @param array $authorization
     * @param string $mode
     */
    public function doRequest($authorization, $mode)
    {
        $this->setMode($mode);
        $this->setAuthorization($authorization);
        $this->setRestClient();
        try {
            $this->apiResponse = $this->restClient->request(
                $this->requestMethod,
                $this->requestUrl,
                [
                    // Use the 'body' method instead of 'json' method because of the JSON_UNESCAPED_UNICODE setting
                    'body' => json_encode($this->order, JSON_UNESCAPED_UNICODE),
                    'headers' => ['Content-Type' => 'application/json']
                ]
            );
            $this->createDebugLine('Request', $this->order);
            $this->orderResultTmp = json_decode($this->apiResponse->getBody());
            $this->orderResult = [
                'return' => (array)$this->orderResultTmp
            ];
            $this->createDebugLine('Response', $this->orderResultTmp);
            if ($this->orderManagement) {
                $this->additional = [
                    'return' => [
                        'resultId' => 0
                    ]
                ];
                if ($this->orderAction === 'capture_full') {
                    $captureAdditional = [
                        'return' => [
                            'transactionId' => $this->orderResultTmp->captureNumber,
                            'totalInvoicedAmount' => $this->orderResultTmp->capturedAmount,
                            'totalReservedAmount' => $this->orderResultTmp->authorizedAmount,
                        ]
                    ];
                    $this->additional = array_merge_recursive($this->additional, $captureAdditional);
                }
            } else {
                $this->additional = [
                    'return' => [
                        'statusCode' => $this->statusCodes[$this->orderResultTmp->outcome],
                        'resultId' => $this->resultId[$this->orderResultTmp->outcome]
                    ]
                ];
            }
            $this->orderResult = array_merge_recursive($this->orderResult, $this->additional);
        } catch (ClientException $e) {
            $this->createDebugLine('Error', null, null, $e);
            $this->orderResultTmp = json_decode($e->getResponse()->getBody());
            $this->orderResult = [
                'return' => (array)$this->orderResultTmp
            ];
            $this->additional = $this->getAdditional($e, $this->orderResultTmp);
            $this->orderResult = array_merge_recursive((array)$this->orderResult, $this->additional);
        } catch (ServerException $e) {
            $this->createDebugLine('Error', null, null, $e);
            $this->orderResultTmp = json_decode($e->getResponse()->getBody());
            $this->orderResult = [
                'return' => (array)$this->orderResult
            ];
            $this->additional = $this->getAdditional($e, $this->orderResultTmp);
            $this->orderResult = array_merge_recursive($this->orderResult, $this->additional);
        } catch (\Exception $e) {
            $this->createDebugLine('Error', null, null, $e);
            $this->orderResult = [
                'return' => [
                    'resultId' => 1,
                    'failures' => [
                        'failure' => $e->getMessage(),
                        'description' => \Afterpay\check_technical_error('default.message'),
                    ],
                    'messages' => [
                        [
                            'description' => \Afterpay\check_technical_error('default.message'),
                            'message' => $e->getMessage()
                        ]
                    ]
                ]
            ];
        }
    }

    /**
     * Sets mode, options are test or live
     *
     * @param string $mode
     *
     */
    protected function setMode($mode)
    {
        $this->mode = $mode;
        $this->createDebugLine('Set mode to: ' . $this->mode);
        $this->webServiceUrl = $this->getWebserviceUrl($this->country, $mode);
        $this->createDebugLine('Set WebServiceUrl to: ' . $this->webServiceUrl);
    }

    /**
     * Get correct API endpoint for the client
     *
     * @param string $country
     * @param string $mode
     *
     * @return null|string
     */
    protected function getWebserviceUrl($country, $mode)
    {
        $webServiceUrl = null;
        if ($mode === 'test') {
            $webServiceUrl = 'https://sandboxapi.horizonafs.com/eCommerceServicesWebApi/api/v3/';
        } elseif ($mode === 'live') {
            $webServiceUrl = 'https://api.afterpay.io/api/v3/';
        }
        return $webServiceUrl;
    }

    /**
     * Set authorization for Rest client connection
     *
     * @param array $authorization
     */
    protected function setAuthorization($authorization)
    {
        $this->authorization = [
            'apiKey' => $authorization['apiKey']
        ];
    }

    /**
     * Set correct soap client, differs per country
     *
     */
    private function setRestClient()
    {
        $this->restClient = new GuzzleHttp\Client([
            'headers' => [
                'X-Auth-Key' => $this->authorization['apiKey']
            ],
            'base_uri' => $this->webServiceUrl
        ]);
    }

    /**
     * @param \GuzzleHttp\Exception\ClientException | \GuzzleHttp\Exception\ServerException $exception
     * @param \stdClass $message
     *
     * @return array
     */
    private function getAdditional($exception, $message)
    {
        $additional = [];
        $statusCode = $exception->getResponse()->getStatusCode();

        if ($statusCode === 400) {
            if (is_array($message)) {
                $message = $message[0];
            }
            if (!property_exists($message, 'customerFacingMessage')) {
                $message->customerFacingMessage = $message->message;
            }
            $errorType = $message->type . '.' . $message->actionCode;
            $additional = [
                'return' => [
                    'resultId' => 1,
                    'failures' => [
                        'failure' => $errorType,
                        'description' => $message->message
                    ],
                    'messages' => [
                        [
                            'message' => $message->customerFacingMessage,
                            'description' => $message->customerFacingMessage,
                        ]
                    ]
                ]
            ];
        } elseif ($statusCode === 401) {
            $additional = [
                'return' => [
                    'resultId' => 1,
                    'failures' => [
                        'failure' => 'Unauthorized'
                    ]
                ]
            ];
        } elseif ($statusCode === 404) {
            $additional = [
                'return' => [
                    'resultId' => 1,
                    'failures' => [
                        'failure' => 'NotFound'
                    ]
                ]
            ];
        } elseif ($statusCode === 422) {
            $additional = [
                'return' => [
                    'resultId' => 2,
                    'failures' => [
                        'failure' => 'BadInput'
                    ]
                ]
            ];
        } elseif ($statusCode === 429) {
            $additional = [
                'return' => [
                    'resultId' => 1,
                    'failures' => [
                        'failure' => 'TooManyRequests'
                    ]
                ]
            ];
        } elseif ($statusCode === 500) {
            $additional = [
                'return' => [
                    'resultId' => 1,
                    'failures' => [
                        'failure' => 'InternalServerError'
                    ],
                ]
            ];
        }
        return $additional;
    }

    /**
     * Getter for order
     *
     * @return array
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Getter for authorization
     *
     * @return array
     */
    public function getAuthorization()
    {
        return $this->authorization;
    }

    /**
     * Getter for mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Getter for order result
     *
     * @return object
     */
    public function getOrderResult()
    {
        if (is_array($this->orderResult)) {
            return json_decode(json_encode($this->orderResult));
        }
        return $this->orderResult;
    }
}