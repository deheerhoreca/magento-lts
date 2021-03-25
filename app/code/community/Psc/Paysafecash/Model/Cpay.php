<?php
class Psc_Paysafecash_Model_Cpay extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'cpay';
	protected $_canCapture = true; 
	protected $_canCapturePartial = false; 
	protected $_canUseForMultishipping  = false;
	protected $_isGateway               = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = false;
	protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
	
    protected $_formBlockType = 'paysafecash/cpay_form';
	protected $_pcokBlockType = 'paysafecash/cpay_pcok';
	protected $_pcnokBlockType = 'paysafecash/cpay_pcnok';
	protected $_pcnotifyBlockType = 'paysafecash/cpay_pcnotify';

	protected $_isInitializeNeeded = true;
	
	public function getLogPath()
	{
		return Mage::getBaseDir() . '/var/log/paysafecash.log';
	}
	
	public function getDebug(){
		return $this->getConfigData('logging');
	}
	
	public function getAllowedCurrency()
    {
        $this->moduleAllowedCurrency = explode(",", $this->getConfigData('Currency'));
		return $this->moduleAllowedCurrency;
    }
	
	public function getOrderStatus()
    {
        return $this->getConfigData('order_status');
    }
	
	public function getInvoice()
    {
        return $this->getConfigData('invoice');
    }
	
    public function getApikey()
    {
        return trim($this->getConfigData('apikey'));
    }
	
	public function getSubid()
    {
        return trim($this->getConfigData('subid'));
    }
	
	public function getTimeout()
    {
        return intval($this->getConfigData('timeout'));
    }
	
	public function getTransactionMode()
    {
        return $this->getConfigData('paymode');
    }
	
	public function getDatatakeover()
    {
        return $this->getConfigData('datatakeover');
    }
	
	public function getCert()
    {
        return trim($this->getConfigData('cert'));
    }
	
	public function getApiUrl()
    {
        if($this->getTransactionMode() != '1'){ //production
		$trurl = 'https://api.paysafecard.com/v1/'; 
		} else {
		$trurl = 'https://apitest.paysafecard.com/v1/';
		}
		return $trurl;
    }
	
	public function getUrl()
    {
		$dbpaysafecash = Mage::getSingleton('core/resource')->getConnection('core_write');
		$result = $dbpaysafecash->query("select id, paysafecashurl from paysafecash_data order by id desc limit 1");
		if(!$result){
			return false;
		}
		$row = $result->fetch(PDO::FETCH_ASSOC);
		if(!$row){
			return false;
		}
		
		$trurl = trim($row['paysafecashurl']);
		return $trurl;
    }
	
    public function getSession()
    {
        return Mage::getSingleton('paysafecash/cpay_session');
    }
	
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
	
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
	
	public function getCustomer()
    {
        if (empty($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }	
	
    public function getEmail()
    {
    	return (string)Mage::getSingleton('customer/session')->getCustomer()->getEmail();
    }
	
	public function getCid()
    {
    	return (string)Mage::getSingleton('customer/session')->getCustomer()->getId();
    }
	
	public function getRealIpAddr()
	{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    { $ip=$_SERVER['HTTP_CLIENT_IP']; }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    { $ip=$_SERVER['HTTP_X_FORWARDED_FOR']; } 
	else { $ip=$_SERVER['REMOTE_ADDR']; }
	
	if(preg_match("/,/i", $ip)) {
	 $iparray = explode(",", $ip);
	 $ip = $iparray[0];
	}
	
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {    
    $ip = $ip;
	} else {
	$ip = '';
	}
	
    return $ip;
	}
	
	public function getSuccessurl()
    {
        $successurl = Mage::getUrl('paysafecash/cpay/pcok/', array('_secure' => true));
		$successurl .= '?payment_id={payment_id}';
        return $successurl;
    }

    public function getFailurl()
    {
        $failurl = Mage::getUrl('paysafecash/cpay/pcnok/', array('_secure' => true));
		$failurl .= '?payment_id={payment_id}';
        return $failurl;
    }
	
	public function getNotifyurl()
    {
        $notifyurl = Mage::getUrl('paysafecash/cpay/pcnotify/', array('_secure' => true));
        return $notifyurl;
    }
	
	public function getCancelurl()
    {
        $cancelurl = Mage::getUrl('paysafecash/cpay/pccancel/', array('_secure' => true));
        return $cancelurl;
    }
	
	
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
	
	//no invoice 06/2013
	public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);	
    }	
	
	public function addPaysafecashFields($form){

	$lastIncrementId = $this->_getCheckout()->getLastRealOrderId(); 
	$order = Mage::getModel('sales/order');
    $order->loadByIncrementId($lastIncrementId);
			
	$order_id = $lastIncrementId;
	$order_id_long = $lastIncrementId;
	$currency_code = $order->getBaseCurrencyCode(); 
	$locale_code = Mage::app()->getLocale()->getLocaleCode();
	
	$amount = $order->getBaseGrandTotal();
	$amount =  preg_replace('/,/', '.', $amount);
	$charge = number_format((float)$amount, 2, '.', '');
	
	$orderID = $order_id_long;
	$tm_ref_id = 'mref'.substr(time().mt_rand(), 0, 25);

	$dpcustomermail = $order->getCustomerEmail();
	$dpcustomerid = $order->getCustomerId();
	$dpcustomerip = $this->getRealIpAddr();
	
	if($this->getDatatakeover()){
		$firstname = html_entity_decode($order->getCustomerFirstname());
		$lastname = html_entity_decode($order->getCustomerLastname());
		$billing_address = $order->getBillingAddress();
		$CustomerCountry = $billing_address->getCountryModel()->getIso2Code();
		$CustomerRegion = html_entity_decode($billing_address->getRegion());
		$CustomerZIPCode = $billing_address->getPostcode();
		$CustomerCity = html_entity_decode($billing_address->getCity());
    //DHH CORE HACK
		$CustomerAddress = html_entity_decode(implode(" ",$billing_address->getStreet()));
		$payerPhone = preg_replace('/^00?/', '+', $billing_address->getTelephone());
		
		$dob = $order->getCustomerDob();
		if(isset($dob) && $dob!=''){
		 $dobDate = date("Y-m-d", strtotime($dob));
		} else {
		 $dobDate = '1990-12-31';
		}

		$customerdata = ["first_name" => $firstname, "last_name" => $lastname, "date_of_birth" => $dobDate, "address1" => $CustomerAddress, "postcode" => $CustomerZIPCode, "city" => $CustomerCity, "country_iso2" => $CustomerCountry, "phone_number" => $payerPhone, "email" => $dpcustomermail];
	} else{
        $customerdata = array();
    }

    $dpcustomerid = md5($dpcustomermail);
	
	$correlation_id = "OID" . $order_id_long . "_" . uniqid();
	$timeout = $this->getTimeout();
	
	$helper = Mage::helper('paysafecash/Paymentclass');
	$response = $helper->initiatePayment($charge, $currency_code, $dpcustomerid, $dpcustomerip, $this->getSuccessurl(), $this->getFailurl(), $this->getNotifyurl(), $customerdata, $timeout, $correlation_id, '', '', '', '', $this->getSubid());
	
	$error_no = '';
	$error_txt = '';
	$http_status = '';

	if ($response == false) {
	 $error = $helper->getError();
	 $curlerror = $helper->getCurl();
	 $error_no = intval($error['number']);
	 $error_txt = addslashes($error['message']);
	 $http_status = intval($curlerror['http_status']);
	}
	
	if($this->getDebug()){
	 $psclogger = Mage::helper('paysafecash/Paysafelogger');
	 $logresponse = $psclogger->log($helper->getRequest(), $helper->getCurl(), $helper->getResponse());
	}
	
	if ($response == false) {
	 Mage::getSingleton('core/session')->setPscErrorNo($error_no);
	 Mage::getSingleton('core/session')->setPscErrorTxt($error_txt);
	 die(__('Unfortunately, a technical error has occurred. Please try again later or contact the Support Team.').'<br/><br/><a href="'. Mage::getUrl('paysafecash/cpay/pcnok/', array('_secure' => true)) .'&pscoid='. $order_id .'" target="_self">'.__('Proceed to Checkout.').'</a>');
	}
	
	$mtid = addslashes($response["id"]);
	$mid_raw = explode("_",$mtid);
	$mid = $mid_raw[1];
	$status = addslashes($response["status"]);
	$paysafecashurl = addslashes($response["redirect"]["auth_url"]);
	$hash_raw = explode("customerHash=",$paysafecashurl);
	$hash = $hash_raw[1];
	
	$dbpaysafecash = Mage::getSingleton('core/resource')->getConnection('core_write');
	$result = $dbpaysafecash->query("insert into paysafecash_data (oid, order_id, cid, email, amount, currency, error_no, error_txt, http_status, mid, mtid, created, updated, status, paysafecashurl) values ('".$order_id."', '".$order_id."', '".$dpcustomerid."', '".$dpcustomermail."', '".$charge."', '".$currency_code."', '".$error_no."', '".$error_txt."', '".$http_status."', '".$mid."', '".$mtid."', now(), now(), '".$status."', '".$paysafecashurl."')");
	
	$form->addField("mid", 'hidden', array('name' => 'mid', 'value' => $mid));
	$form->addField("mtid", 'hidden', array('name' => 'mtid', 'value' => $mtid));
	$form->addField("amount", 'hidden', array('name' => 'amount', 'value' => $charge));
	$form->addField("currency", 'hidden', array('name' => 'currency', 'value' => $currency_code));
	$form->addField("customerHash", 'hidden', array('name' => 'customerHash', 'value' => $hash));

	return $form;
	} 
	
    public function refund(Varien_Object $payment, $amount)
    {
        
		$mtid = $this->_getParentTransactionId($payment);

		if ($mtid) {
		$dbpaysafecash = Mage::getSingleton('core/resource')->getConnection('core_write');
		$result = $dbpaysafecash->query("select * from paysafecash_data where mtid = '".$mtid."'");
		if(!$result){
			return false;
		}
		$row = $result->fetch(PDO::FETCH_ASSOC);
		if(!$row){
			return false;
		}
		
		$amount = number_format((float)$amount, 2, '.', '');
		$amount =  preg_replace('/,/', '.', $amount);
		$amount = number_format($amount, 2, '.', '');
		
		$order = $payment->getOrder();
		$correlation_id = "OID" . $order->increment_id . "_" . uniqid();
		
		//test data
		//$row["email"] = "psc.test+mypins_webittester_JFkDuUab@paysafecard.com";
		//$row["cid"] = md5("test123");
		//$row["mtid"]="pay_1000056634_dZpct3MSXxRB1xHkyNrma8z1FBu2YVKf_EUR";

        $helper = Mage::helper('paysafecash/Refundclass');
		$paymentDetail = $helper->getPaymentDetail($row["mtid"]);
        $refunded      = $helper->getRefundedAmount();

        if ($paymentDetail == false || isset($paymentDetail['number'])) {
			$exceptionMessage = 'Refund failed (error getting payment details)';
			Mage::throwException($exceptionMessage);
        } else if (isset($paymentDetail["object"])) {
		
			if($this->getDebug()){
			 $psclogger = Mage::helper('paysafecash/Paysafelogger');
			 $logresponse = $psclogger->log($helper->getRequest(), $helper->getCurl(), $helper->getResponse());
			}
			
            //die(print_r($paymentDetail));
			
			if ($paymentDetail["status"] == "SUCCESS") {
			
                if ($paymentDetail["status"] == "SUCCESS" && ($amount - $refunded) > 0) {
				 $refund_amount = $amount - $refunded;
				 
				 $response = $helper->captureRefund($row["mtid"], $refund_amount, $row["currency"], $paymentDetail["customer"]["id"], $paymentDetail["customer"]["psc_id"], $correlation_id, $this->getSubid());
		
					$error_no = '';
					$error_txt = '';
					$http_status = '';
				
					if ($response == false) {
					 $error = $helper->getError();
					 $curlerror = $helper->getCurl();
					 $error_no = intval($error['number']);
					 $error_txt = addslashes($error['message']);
					 $http_status = intval($curlerror['http_status']);
					}
					
					if($this->getDebug()){
					 $psclogger = Mage::helper('paysafecash/Paysafelogger');
					 $logresponse = $psclogger->log($helper->getRequest(), $helper->getCurl(), $helper->getResponse());
					}
					
					//response handling
					if ($response == false || isset($response['number'])) {
						if($this->getDebug()){
						 $psclogger = Mage::helper('paysafecash/Paysafelogger');
						 $logresponse = $psclogger->log("Refund error " . $response['number'] . ":" . $response['message'], "", "");
						}
						$exceptionMessage = $response['message'];
						Mage::throwException($exceptionMessage);
					} else if (isset($response["object"])) {
						if ($response["status"] == "SUCCESS") {
							$result = $dbpaysafecash->query("insert into paysafecash_data (oid, order_id, cid, email, amount, currency, error_no, error_txt, http_status, mid, mtid, created, updated, status) values ('".$row["oid"]."', '".$row["order_id"]."', '".$row["cid"]."', '".$row["email"]."', '".$refund_amount."', '".$row["currency"]."', '".$error_no."', '".$error_txt."', '".$http_status."', '".$row["mid"]."', '".$response["id"]."', now(), now(), '".$response["status"]."')");
						} else {
							$exceptionMessage = 'Refund Failed: ' . $response["status"];
							Mage::throwException($exceptionMessage);
						}
					}
			
				$payment->setSkipTransactionCreation(true);
                return $this;
				
				} else {
				  $exceptionMessage = 'Invalid Refund amount: ' . ($amount - $refunded);
				  Mage::throwException($exceptionMessage);
				}
            
			
			
			} elseif ($paymentDetail["status"] == "REDIRECTED") {
                // successful got details, but is in invalid state -> no refund can be processed
				$exceptionMessage = 'Refund failed - Invalid Status: ' . $paymentDetail["status"];
				Mage::throwException($exceptionMessage);
            } else {
                $exceptionMessage = 'Refund failed: ' . $paymentDetail["status"];
				Mage::throwException($exceptionMessage);
            }
			
			
        }
	
        } else {
            Mage::throwException(Mage::helper('paysafecash')->__('Impossible to issue a refund transaction because the capture transaction does not exist.'));
        }
        
    }	
 
	public function processInvoice($invoice, $payment)
    {
        $payment->setForcedState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);
        return $this;
    }
	
    public function createFormBlock($name)
    {
        
		$block = $this->getLayout()->createBlock('paysafecash/cpay_form', $name)
            ->setMethod('cpay')
            ->setPayment($this->getPayment())
            ->setTemplate('paysafecash/cpay/form.phtml');

        return $block;
    }

    public function validate()
    {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
		
        if (isset($currency_code) && $currency_code!='' && !in_array($currency_code,$this->getAllowedCurrency())) {
            Mage::throwException(Mage::helper('paysafecash')->__('Selected currency code ('.$currency_code.') is not compatible'));
        }
	
        return $this;
    }
		

    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
       return $this;
    }

    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {

    }

    public function canCapture()
    {
        return true;
    }

    public function getOrderPlaceRedirectUrl()
    {
		  return Mage::getUrl('paysafecash/cpay/redirect', array('_secure' => true)); //ssl
    }
    
    public function isAvailable($quote = null)
	{
		if( $this->getConfigData('active') == 1){
		return true;
		}
		
		// Default, restrict access
		return false;
	}

	protected function _getParentTransactionId(Varien_Object $payment)
    {
        return $payment->getParentTransactionId() ? $payment->getParentTransactionId() : $payment->getLastTransId();
    }
}