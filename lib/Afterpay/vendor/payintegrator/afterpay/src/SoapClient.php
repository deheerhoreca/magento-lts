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

#[AllowDynamicProperties]
class SoapClient extends Client
{
    /**
     * @var \SoapClient $soapClient
     */
    protected $soapClient;

    /**
     * @var array $orderReferencePerson
     */
    protected $orderReferencePerson = [];

    /**
     * Function to create order line
     *
     * @param int $productId
     * @param string $description
     * @param int $quantity
     * @param int $unitPrice
     * @param $vatCategory
     * @param $vatAmount
     * @param int $googleProductCategoryId
     * @param string $googleProductCategory
     * @param string $productUrl
     * @param string $imageUrl
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
        $productUrl = null,
        $imageUrl = null,
        $groupId = null
    )
    {
        $orderLine = [
            'articleId' => $productId,
            'articleDescription' => \Afterpay\cleanText($description),
            'quantity' => $quantity,
            'unitprice' => strval($unitPrice),
            'vatcategory' => $vatCategory
        ];
        $this->totalOrderAmount = strval($this->totalOrderAmount + ($quantity * $unitPrice));
        $this->orderLines[] = $orderLine;
    }

    /**
     * If order management is used set action to true;
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
     * @param string $order_type
     */
    public function setOrder($order, $order_type)
    {
        $this->resolveOrderCountry($order);

        // Set order_type, options are B2C, B2B, OM
        $this->setOrderType($order_type);
        if ($this->orderType == 'OM') {
            switch ($this->orderAction) {
                case 'capture_full':
                    $this->order = [
                        'invoicenumber' => $order['invoicenumber'],
                        'transactionkey' => [
                            'ordernumber' => $order['ordernumber']
                        ],
                        'capturedelaydays' => 0,
                        'shippingCompany' => ''
                    ];
                    break;
                case 'capture_partial':
                    $this->order = [
                        'invoicelines' => $this->orderLines,
                        'invoicenumber' => $order['invoicenumber'],
                        'transactionkey' => [
                            'ordernumber' => $order['ordernumber']
                        ],
                        'capturedelaydays' => 0,
                        'shippingCompany' => ''
                    ];
                    break;
                case 'cancel':
                    $this->order = [
                        'transactionkey' => [
                            'ordernumber' => $order['ordernumber']
                        ]
                    ];
                    break;
                case 'status':
                    $this->order = [
                        'transactionkey' => [
                            'ordernumber' => $order['ordernumber']
                        ]
                    ];
                    break;
                case 'refund_full':
                    $this->order = [
                        'invoicenumber' => $order['invoicenumber'],
                        'transactionkey' => [
                            'ordernumber' => $order['ordernumber']
                        ],
                        'creditInvoicenNumber' => $order['creditinvoicenumber']
                    ];
                    break;
                case 'refund_partial':
                    $this->order = [
                        'invoicelines' => $this->orderLines,
                        'invoicenumber' => $order['invoicenumber'],
                        'transactionkey' => [
                            'ordernumber' => $order['ordernumber']
                        ],
                        'creditInvoicenNumber' => $order['creditinvoicenumber']
                    ];
                    break;
                case 'void':
                    $this->order = [
                        'transactionkey' => [
                            'ordernumber' => $order['ordernumber']
                        ]
                    ];
                    break;
                case 'request_order_status':
                    $this->order = [
                        'transactionkey' => [
                            'ordernumber' => $order['ordernumber']
                        ]
                    ];
                    break;
                default:
                    break;
            }

            return;
        }

        if ($this->orderType == 'B2C') {
            $this->billToAddress = 'b2cbilltoAddress';
            $this->shipToAddress = 'b2cshiptoAddress';
        } elseif ($this->orderType == 'B2B') {
            $this->billToAddress = 'b2bbilltoAddress';
            $this->shipToAddress = 'b2bshiptoAddress';
        }

        $this->order = [
            $this->billToAddress => [
                'city' => $order['billtoaddress']['city'],
                'housenumber' => $order['billtoaddress']['housenumber'],
                'housenumberAddition' => '',
                'isoCountryCode' => $order['billtoaddress']['isocountrycode'],
                'postalcode' => $order['billtoaddress']['postalcode'],
                'streetname' => $order['billtoaddress']['streetname'],
            ],
            $this->shipToAddress => [
                'city' => $order['shiptoaddress']['city'],
                'housenumber' => $order['shiptoaddress']['housenumber'],
                'housenumberAddition' => '',
                'isoCountryCode' => $order['shiptoaddress']['isocountrycode'],
                'postalcode' => $order['shiptoaddress']['postalcode'],
                'streetname' => $order['shiptoaddress']['streetname'],
            ]
        ];

        if (array_key_exists('housenumberaddition', $order['billtoaddress'])) {
            $this->order[$this->billToAddress]['housenumberAddition'] = $order['billtoaddress']['housenumberaddition'];
        }

        if (array_key_exists('housenumberaddition', $order['shiptoaddress'])) {
            $this->order[$this->shipToAddress]['housenumberAddition'] = $order['shiptoaddress']['housenumberaddition'];
        }

        if ($this->orderType == 'B2C') {
            $this->orderReferencePerson = [
                $this->billToAddress => [
                    'referencePerson' => [
                        'dateofbirth' => $order['billtoaddress']['referenceperson']['dob'],
                        'emailaddress' => $order['billtoaddress']['referenceperson']['email'],
                        'gender' => $order['billtoaddress']['referenceperson']['gender'],
                        'initials' => \Afterpay\cleanText($order['billtoaddress']['referenceperson']['initials'], 1),
                        'isoLanguage' => $order['billtoaddress']['referenceperson']['isolanguage'],
                        'lastname' => $order['billtoaddress']['referenceperson']['lastname'],
                        'phonenumber1' => \Afterpay\cleanphone(
                            $order['billtoaddress']['referenceperson']['phonenumber'],
                            $order['billtoaddress']['isocountrycode']),
                    ]
                ],
                $this->shipToAddress => [
                    'referencePerson' => [
                        'dateofbirth' => $order['shiptoaddress']['referenceperson']['dob'],
                        'emailaddress' => $order['shiptoaddress']['referenceperson']['email'],
                        'gender' => $order['shiptoaddress']['referenceperson']['gender'],
                        'initials' => \Afterpay\cleanText($order['shiptoaddress']['referenceperson']['initials'], 1),
                        'isoLanguage' => $order['shiptoaddress']['referenceperson']['isolanguage'],
                        'lastname' => $order['shiptoaddress']['referenceperson']['lastname'],
                        'phonenumber1' => \Afterpay\cleanphone(
                            $order['shiptoaddress']['referenceperson']['phonenumber'],
                            $order['billtoaddress']['isocountrycode']),
                    ]
                ],
            ];
        }
        if ($this->orderType == 'B2B') {
            $this->order += [
                'company' => [
                    'cocnumber' => $order['company']['cocnumber'],
                    'companyname' => $order['company']['companyname'],
                    'vatnumber' => ''
                ],
                'person' => [
                    'dateofbirth' => $order['billtoaddress']['referenceperson']['dob'],
                    'emailaddress' => $order['billtoaddress']['referenceperson']['email'],
                    'gender' => '',
                    'initials' => \Afterpay\cleanText($order['billtoaddress']['referenceperson']['initials'], 1),
                    'isoLanguage' => $order['billtoaddress']['referenceperson']['isolanguage'],
                    'lastname' => $order['billtoaddress']['referenceperson']['lastname'],
                    'phonenumber1' => \Afterpay\cleanphone(
                        $order['billtoaddress']['referenceperson']['phonenumber'],
                        $order['billtoaddress']['isocountrycode']),
                ]
            ];
        }
        $this->order += [
            'ordernumber' => $order['ordernumber'],
            'shopdetails' => [
                'afterPayPluginSupplier' => (
                    isset($order['shopdetails']['afterPayPluginSupplier']) ?
                        substr($order['shopdetails']['afterPayPluginSupplier'],0,50) : 'Arvato Financial Solutions'
                ),
                'afterPayPluginVersion' => (
                    isset($order['shopdetails']['afterPayPluginVersion']) ?
                        substr($order['shopdetails']['afterPayPluginVersion'],0,50) : $this->getLibraryVersion()
                ),
                'shopURL' => (
                    isset($order['shopdetails']['shopURL']) ?
                        substr($order['shopdetails']['shopURL'],0,2048) : ''
                ),
                'webshopLanguage' => (
                    isset($order['shopdetails']['webshopLanguage']) ?
                        substr($order['shopdetails']['webshopLanguage'],0,50) : 'PHP ' . PHP_VERSION
                ),
                'webshopPlatform' => (
                    isset($order['shopdetails']['webshopPlatform']) ?
                        substr($order['shopdetails']['webshopPlatform'],0,50) : 'PHP'
                ),
                'webshopPlatformVersion' => (
                    isset($order['shopdetails']['webshopPlatformVersion']) ?
                        substr($order['shopdetails']['webshopPlatformVersion'],0,50) : PHP_VERSION
                ),
            ],
            'bankaccountNumber' => (isset($order['bankaccountnumber'])) ? $order['bankaccountnumber'] : '',
            'currency' => $order['currency'],
            'ipAddress' => $order['ipaddress'],
            'shopper' => [
                'profilecreated' => '2013-01-01T00:00:00'
            ],
            'parentTransactionreference' => false,
            'orderlines' => $this->orderLines,
            'totalOrderAmount' => $this->totalOrderAmount,
        ];
        if (!empty($this->orderReferencePerson)) {
            $this->order = array_merge_recursive($this->order, $this->orderReferencePerson);
        }
    }

    /**
     * Set order types to correct webservice calls and function names
     *
     * @param string $order_type
     *
     */
    private function setOrderType($order_type)
    {
        if (!$this->orderManagement) {
            switch ($order_type) {
                case 'B2C':
                    $this->orderType = 'B2C';
                    $this->orderTypeName = 'validateAndCheckB2COrder';
                    $this->orderTypeFunction = 'b2corder';
                    break;
                case 'B2B':
                    $this->orderType = 'B2B';
                    $this->orderTypeName = 'validateAndCheckB2BOrder';
                    $this->orderTypeFunction = 'b2border';
                    break;
                default:
                    break;
            }
        } else {
            switch ($this->orderAction) {
                case 'capture_full':
                    $this->orderType = 'OM';
                    $this->orderTypeName = 'captureFull';
                    $this->orderTypeFunction = 'captureobject';
                    break;
                case 'capture_partial':
                    $this->orderType = 'OM';
                    $this->orderTypeName = 'capturePartial';
                    $this->orderTypeFunction = 'captureobject';
                    break;
                case 'cancel':
                    $this->orderType = 'OM';
                    $this->orderTypeName = 'cancelOrder';
                    $this->orderTypeFunction = 'ordermanagementobject';
                    break;
                case 'status':
                    $this->orderType = 'OM';
                    $this->orderTypeName = 'requestOrderStatus';
                    $this->orderTypeFunction = 'ordermanagementobject';
                    break;
                case 'refund_full':
                    $this->orderType = 'OM';
                    $this->orderTypeName = 'refundFullInvoice';
                    $this->orderTypeFunction = 'refundobject';
                    break;
                case 'refund_partial':
                    $this->orderType = 'OM';
                    $this->orderTypeName = 'refundInvoice';
                    $this->orderTypeFunction = 'refundobject';
                    break;
                case 'void':
                    $this->orderType = 'OM';
                    $this->orderTypeName = 'doVoid';
                    $this->orderTypeFunction = 'ordermanagementobject';
                    break;
                case 'request_order_status':
                    $this->orderType = 'OM';
                    $this->orderTypeName = 'requestOrderStatus';
                    $this->orderTypeFunction = 'ordermanagementobject';
                    break;
            }

        }
    }

    /**
     * Process request to SOAP webservice
     *
     * @param array $authorization
     * @param string $mode
     * @param string|null $language
     * 
     */
    public function doRequest($authorization, $mode, $language = null)
    {
        $this->setMode($mode);
        $this->setSoapClient();
        $this->setAuthorization($authorization);
        try {
            $this->orderResult = $this->soapClient->__soapCall(
                $this->orderTypeName,
                [
                    $this->orderTypeName => [
                        'authorization' => $this->authorization,
                        $this->orderTypeFunction => $this->order
                    ]
                ]
            );

            $this->createDebugLine('Request', null, $this->soapClient->__getLastRequest());
            $this->createDebugLine('Response', null, $this->soapClient->__getLastResponse());

            if (isset($this->orderResult->return->resultId) && $this->orderResult->return->resultId == 2) {
                if (is_array($this->orderResult->return->failures)) {
                    foreach ($this->orderResult->return->failures as $failure) {
                        $validation_error = \Afterpay\check_validation_error(
                            $failure->failure,
                            $failure->fieldname,
                            $language
                        );
                        $return_message[] = ['message' => $validation_error, 'description' => $validation_error];
                    }
                } else {
                    $validation_error = \Afterpay\check_validation_error(
                        $this->orderResult->return->failures->failure,
                        $this->orderResult->return->failures->fieldname,
                        $language
                    );
                    $return_message[] = ['message' => $validation_error, 'description' => $validation_error];
                }

                $this->orderResult = array_merge_recursive(
                    (array)$this->orderResult,
                    [
                        'return' => [
                            'messages' => $return_message
                        ]
                    ]);

            } elseif (isset($this->orderResult->return->resultId) && $this->orderResult->return->resultId == 3) {
                if (isset($this->orderResult->return->rejectCode)) {
                    $rejection_error = \Afterpay\check_rejection_error(
                        $this->orderResult->return->rejectCode,
                        $language
                    );
                } else {
                    $rejection_error = \Afterpay\check_rejection_error(0, $language);
                }
                $this->orderResult = array_merge_recursive(
                    (array)$this->orderResult,
                    [
                        'return' => [
                            'messages' => $rejection_error
                        ]
                    ]);
            }
        } catch (\Exception $e) {
            $this->orderResult = [
                'return' => [
                    'failures' => [
                        'failure' => $e->faultstring,
                        'description' => 'A technical error occurred, please contact the webshop.',
                        'messages' => [\Afterpay\check_technical_error($e->faultstring, $language)],
                        'resultId' => '1',
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
     * Get correct WSDL for Soap client, differs per country and which mode it's setup
     *
     * @param string $country
     * @param string $mode
     * @return null|string
     */
    protected function getWebserviceUrl($country, $mode)
    {
        $webServiceUrl = null;
        if (!$this->orderManagement) {
            if ($country == 'NL') {
                if ($mode == 'test') {
                    $webServiceUrl = 'https://test.acceptgirodienst.nl/soapservices/rm/AfterPaycheck?wsdl';
                } elseif ($mode == 'live') {
                    $webServiceUrl = 'https://www.acceptgirodienst.nl/soapservices/rm/AfterPaycheck?wsdl';
                }
            } elseif ($country == 'BE') {
                if ($mode == 'test') {
                    $webServiceUrl = 'https://test.afterpay.be/soapservices/rm/AfterPaycheck?wsdl';
                } elseif ($mode == 'live') {
                    $webServiceUrl = 'https://api.afterpay.be/soapservices/rm/AfterPaycheck?wsdl';
                }
            }
        } else {
            if ($country == 'NL') {
                if ($mode == 'test') {
                    $webServiceUrl = 'https://test.acceptgirodienst.nl/soapservices/om/OrderManagement?wsdl';
                } elseif ($mode == 'live') {
                    $webServiceUrl = 'https://www.acceptgirodienst.nl/soapservices/om/OrderManagement?wsdl';
                }
            } elseif ($country == 'BE') {
                if ($mode == 'test') {
                    $webServiceUrl = 'https://test.afterpay.be/soapservices/om/OrderManagement?wsdl';
                } elseif ($mode == 'live') {
                    $webServiceUrl = 'https://api.afterpay.be/soapservices/om/OrderManagement?wsdl';
                }
            }
        }

        return $webServiceUrl;
    }

    /**
     * Set correct soap client, differs per country
     *
     */
    private function setSoapClient()
    {
        if ($this->country == 'NL') {
            /**
             * @var \SoapClient $this ->soapClient
             */
            $this->soapClient = new \SoapClient(
                $this->webServiceUrl,
                [
                    'trace' => 1,
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'exceptions' => true,
                    'encoding' => 'ISO-8859-1'
                ]
            );
        } elseif ($this->country == 'BE') {
            /**
             * @var \SoapClient $this ->soapClient
             */
            $this->soapClient = new \SoapClient(
                $this->webServiceUrl,
                [
                    'location' => $this->webServiceUrl,
                    'trace' => 1,
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'exceptions' => true,
                    'encoding' => 'ISO-8859-1'
                ]
            );
        }
    }

    /**
     * Set authorization for Soap client connection
     *
     * @param array $authorization
     */
    protected function setAuthorization($authorization)
    {
        $this->authorization = [
            'merchantId' => $authorization['merchantid'],
            'portfolioId' => $authorization['portfolioid'],
            'password' => $authorization['password']
        ];
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