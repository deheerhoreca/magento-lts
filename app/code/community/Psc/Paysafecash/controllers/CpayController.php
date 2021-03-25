<?php

class Psc_Paysafecash_CpayController extends Mage_Core_Controller_Front_Action
{
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }
	
	public function getPaysafecash()
    {
        return Mage::getSingleton('paysafecash/cpay');
    }
	
    public function psc_request_headers()
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))))] = $value;
            }
        }
        return $headers;
     }
	
	public function setPaysafecashResponse($response)
    {
    	if (count($response)) {
            $this->_paysafecashResponse = $response;
        } else {
			$this->_paysafecashResponse = null;
		}
        return $this;
    }

    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setCpayQuoteId($session->getQuoteId());
		
        $this->getResponse()->setBody($this->getLayout()->createBlock('paysafecash/cpay_redirect')->toHtml());
        $session->unsQuoteId();
		$session->unsRedirectUrl(); //unset 06/2013
    }

	public function PcokAction()
    {
		$session = Mage::getSingleton('checkout/session');
        $session->setCpayQuoteId($session->getQuoteId());
		
		if (isset($_GET["payment_id"]) && $_GET["payment_id"]!='') {
        $id = addslashes($_GET["payment_id"]);
		
		$helper = Mage::helper('paysafecash/Paymentclass');
	    $response = $helper->retrievePayment($id);
		
		if ($response == false) {
		 $error = $helper->getError();
		 $curlerror = $helper->getCurl();
		 $error_no = intval($error['number']);
		 $error_txt = addslashes($error['message']);
		 $http_status = intval($curlerror['http_status']);
		}
		
		if($this->getPaysafecash()->getDebug()){
		 $psclogger = Mage::helper('paysafecash/Paysafelogger');
		 $logresponse = $psclogger->log($helper->getRequest(), $helper->getCurl(), $helper->getResponse());
		}
		
		if ($response == false) {
		 Mage::getSingleton('core/session')->setPscErrorNo($error_no);
		 Mage::getSingleton('core/session')->setPscErrorTxt($error_txt);
		 $this->_redirect('checkout/onepage/failure', array('_secure'=>true));
		}
		
		$mtid = addslashes($response["id"]);
		$mid_raw = explode("_",$mtid);
		$mid = $mid_raw[1];
		$status = addslashes($response["status"]);
		
		$dbpaysafecash = Mage::getSingleton('core/resource')->getConnection('core_write');
		$result = $dbpaysafecash->query("select * from paysafecash_data where mtid = '".$id."'");
		if(!$result){
			return false;
		}
		$row = $result->fetch(PDO::FETCH_ASSOC);
		if(!$row){
			return false;
		}
		
		$dbinsert = $dbpaysafecash->query("update paysafecash_data set status = '".$status."', updated = now() where mtid='".$id."'");
		
		Mage::getSingleton('core/session')->setPscTxId($id);
		Mage::getSingleton('core/session')->setPscStatus($status);
		
		if(isset($status) && ($status=='CANCELED_CUSTOMER' || $status=='CANCELED_MERCHANT' || $status=='EXPIRED')){
		 $this->_redirect('checkout/onepage/failure', array('_secure'=>true));
		} else {
		 $this->_redirect('checkout/onepage/success', array('_secure'=>true));
		}
		
		} else {
		 $this->_redirect('checkout/onepage/failure', array('_secure'=>true));
		}
	}
	
	public function PcnokAction()
    {
		$session = Mage::getSingleton('checkout/session');
        $session->setCpayQuoteId($session->getQuoteId());
		
		if (isset($_GET["payment_id"]) && $_GET["payment_id"]!='') {
        $id = addslashes($_GET["payment_id"]);
		
		$helper = Mage::helper('paysafecash/Paymentclass');
	    $response = $helper->retrievePayment($id);
		
		if ($response == false) {
		 $error = $helper->getError();
		 $curlerror = $helper->getCurl();
		 $error_no = intval($error['number']);
		 $error_txt = addslashes($error['message']);
		 $http_status = intval($curlerror['http_status']);
		}
		
		if($this->getPaysafecash()->getDebug()){
		 $psclogger = Mage::helper('paysafecash/Paysafelogger');
		 $logresponse = $psclogger->log($helper->getRequest(), $helper->getCurl(), $helper->getResponse());
		}
		
		if ($response == false) {
		 Mage::getSingleton('core/session')->setPscErrorNo($error_no);
		 Mage::getSingleton('core/session')->setPscErrorTxt($error_txt);
		 $this->_redirect('checkout/onepage/failure', array('_secure'=>true));
		}
		
		$mtid = addslashes($response["id"]);
		$mid_raw = explode("_",$mtid);
		$mid = $mid_raw[1];
		$status = addslashes($response["status"]);
		
		$dbpaysafecash = Mage::getSingleton('core/resource')->getConnection('core_write');
		$result = $dbpaysafecash->query("select * from paysafecash_data where mtid = '".$id."'");
		if(!$result){
			return false;
		}
		$row = $result->fetch(PDO::FETCH_ASSOC);
		if(!$row){
			return false;
		}
		
		$dbinsert = $dbpaysafecash->query("update paysafecash_data set status = '".$status."', updated = now() where mtid='".$id."'");
		
		$orderID = $row['order_id'];
		$order = Mage::getModel('sales/order');
	    $order->loadByIncrementId($orderID);
		
		$orderComment = 'Paysafecash Failed Transaction<br />';
		$orderComment .= 'Status: '.$status.'<br />';
		$orderComment .= 'txId: '.$id.'<br />';
		
		Mage::getSingleton('core/session')->setPscTxId($id);
		Mage::getSingleton('core/session')->setPscStatus($status);
		
		$helper = Mage::helper('paysafecash/Checkout');
        $helper->cancelCurrentOrder($orderComment);
        if ($helper->restoreQuote()) {
            $this->_redirect('checkout/onepage/failure', array('_secure'=>true));
        } else {
			$this->_redirect('checkout/onepage/failure', array('_secure'=>true));
		}
		
		} else {
		
		  if(isset($_GET) && preg_match("/pscoid=/i", $_SERVER['REQUEST_URI'])){	  
		    $strip_oid = explode("pscoid=",$_SERVER['REQUEST_URI']);
		    $orderID = addslashes($strip_oid[1]);
		    $order = Mage::getModel('sales/order');
			$order->loadByIncrementId($orderID);
			
			$orderComment = 'Paysafecash connection problem, see paysafecash_data database table or debug log file for more information.<br />';
			
			$helper = Mage::helper('paysafecash/Checkout');
			$helper->cancelCurrentOrder($orderComment);
			
			$error_no = '0000';
			Mage::getSingleton('core/session')->setPscErrorNo($error_no);
			
			if ($helper->restoreQuote()) {
				$this->_redirect('checkout/onepage/failure', array('_secure'=>true));
			} else {
				$this->_redirect('checkout/onepage/failure', array('_secure'=>true));
			}
		  } else {
		   $this->_redirect('checkout/onepage/failure', array('_secure'=>true));
		  }
		}
	}
	
	public function PcnotifyAction()
    {
		
		$session = Mage::getSingleton('checkout/session');
        $session->setCpayQuoteId($session->getQuoteId());
		
		if ($this->getRequest()->isPost()) {
		
		$signature = str_replace('"', '', str_replace('signature="', '', explode(",", $this->psc_request_headers()["Authorization"])[2]));
		$payment_str = file_get_contents("php://input");
		$json_obj = json_decode($payment_str);
		
		$pubcert = $this->getPaysafecash()->getCert();
		$pubkey = openssl_pkey_get_public(file_get_contents($pubcert));
		
		if($this->getPaysafecash()->getDebug()){
		 $psclogger = Mage::helper('paysafecash/Paysafelogger');
		 $logresponse = $psclogger->log("REMOTE IP: " . $_SERVER["REMOTE_ADDR"], "", "");
		 $logresponse = $psclogger->log("WEBHOOK SIGNATURE KEY: " . $signature, "", "");
		 $logresponse = $psclogger->log("WEBHOOK SIGNATURE Body: " . $payment_str, "", "");
		}
		
		$signature_check = openssl_verify($payment_str, base64_decode($signature), $pubkey, OPENSSL_ALGO_SHA256);
		openssl_free_key($pubkey);
		
		if($this->getPaysafecash()->getDebug()){
		$psclogger = Mage::helper('paysafecash/Paysafelogger');
		if ($signature_check == 1) {
			$logresponse = $psclogger->log("WEBHOOK SIGNATURE: Signature is correct", "", "");
		} elseif ($signature_check == 0) {
			$logresponse = $psclogger->log("WEBHOOK SIGNATURE: Signature is not correct", "", "");
		} else {
			$logresponse = $psclogger->log("WEBHOOK SIGNATURE: ERROR: " . openssl_error_string(), "", "");
		}
		}
		
		$helper = Mage::helper('paysafecash/Paymentclass');
	    $response = $helper->retrievePayment($id);
		
		if (isset($json_obj->data->mtid)) {
			$response = $helper->retrievePayment($json_obj->data->mtid);
		
			if($this->getPaysafecash()->getDebug()){
		 	 $psclogger = Mage::helper('paysafecash/Paysafelogger');
			 $logresponse = $psclogger->log("WEBHOOK PAYMENT MTID: " . $json_obj->data->mtid, "", "");
			 $logresponse = $psclogger->log($helper->getRequest(), $helper->getCurl(), $helper->getResponse());
			} 
		
		}
		
		if ($response == false) {
		 $error = $helper->getError();
		 $curlerror = $helper->getCurl();
		 $error_no = intval($error['number']);
		 $error_txt = addslashes($error['message']);
		 $http_status = intval($curlerror['http_status']);
		}
		
		if (!isset($json_obj->data->mtid)) {
			if($this->getPaysafecash()->getDebug()){
		 	 $psclogger = Mage::helper('paysafecash/Paysafelogger');
			 $logresponse = $psclogger->log("WEBHOOK PAYMENT MTID MISSING", "", "");
			}
			die('Invalid JSON Response');
		}
		
        $mtid = addslashes($json_obj->data->mtid);
		$mid_raw = explode("_",$mtid);
		$mid = $mid_raw[1];
		$status = addslashes($response["status"]);
		
		$dbpaysafecash = Mage::getSingleton('core/resource')->getConnection('core_write');
		$result = $dbpaysafecash->query("select * from paysafecash_data where mtid = '".$mtid."'");
		if(!$result){
			return false;
		}
		$row = $result->fetch(PDO::FETCH_ASSOC);
		if(!$row){
			return false;
		}
		
		if ($signature_check == 1) {
		 $webhook_status = 'VERIFIED';
		} else {
		 $webhook_status = 'FAILED';
		}
		
		if((isset($status) && ($status=='CANCELED_CUSTOMER' || $status=='CANCELED_MERCHANT' || $status=='EXPIRED')) || $signature_check != 1){
		 $dbinsert = $dbpaysafecash->query("update paysafecash_data set status = '".$status."', webhook = '".$webhook_status."', updated = now() where mtid='".$mtid."'");
		 //fail routine
		 $orderID = $row['order_id'];
		 $order = Mage::getModel('sales/order');
	     $order->loadByIncrementId($orderID);
		
		 $orderComment = 'Paysafecash transaction problem, see paysafecash_data database table or debug log file for more information.<br />';
		 $orderComment .= 'Webhook Status: '.$webhook_status.'<br />';
		 $orderComment .= 'Status: '.$status.'<br />';
		 $orderComment .= 'txId: '.$mtid.'<br />';
		 
		 $helper = Mage::helper('paysafecash/Checkout');
         $helper->cancelCurrentOrder($orderComment);
		 if ($helper->restoreQuote()) {
            die('quote restored');
         } else {
			$order->cancel()->save();
            $order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED)
                    ->addStatusHistoryComment($orderComment)
                    ->save();
		 }
		} else {
		 $dbinsert = $dbpaysafecash->query("update paysafecash_data set status = '".$status."', webhook = '".$webhook_status."', updated = now() where mtid='".$mtid."'");

		 $orderID = $row['order_id'];
		 $order = Mage::getModel('sales/order');
	     $order->loadByIncrementId($orderID);
		 
		 $amount = number_format((float)$response["amount"], 2, '.', '');
		 $amount =  preg_replace('/,/', '.', $amount);
		 $charge = number_format($amount, 2, '.', '');
		 
		 $payment = $order->getPayment();
		 
		 if($response["status"]=='SUCCESS'){
			$payment->setTransactionId($mtid)
            ->setParentTransactionId(null)
             ->setIsTransactionClosed(1)
            ->registerCaptureNotification($charge);
		 } else {
			$payment->setTransactionId($mtid)
            ->setParentTransactionId(null)
             ->setIsTransactionClosed(1)
            ->registerAuthorizationNotification($charge);
		 }
		 
		 $orderComment = 'Paysafecash Confirmed Transaction<br />';
		 $orderComment .= 'TxID: '.$mtid.'<br />';
		
		 $newstatus='';
		 $newstatus=$this->getPaysafecash()->getOrderStatus();
		
		 if(!isset($newstatus) || $newstatus == ''){
		  $newstatus = 'pending';
		 }
		
		 if($newstatus =='complete'){
		  $order->setData('state', "complete");
		  $order->setStatus("complete");
		  $history = $order->addStatusHistoryComment($orderComment, false);
		  $history->setIsCustomerNotified(true);
 		 } else {
		  $newstate = $newstatus;
		 if ($newstatus == 'pending') {
		  $order->setData('state', 'new');
		 } else {
		  $order->setData('state', $newstate);
		 }
		 $order->setStatus($newstate);
		 $history = $order->addStatusHistoryComment($orderComment, false);
		 $history->setIsCustomerNotified(true);
		}
		
		if($this->getPaysafecash()->getInvoice()){
			if ($order->canInvoice()) {
					$order->getPayment()->setSkipTransactionCreation(false);
					$invoice = $order->prepareInvoice();
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->register();
					Mage::getModel('core/resource_transaction')
					   ->addObject($invoice)
					   ->addObject($order)
					   ->save();
			}
			
			if($order->hasInvoices()){
			$paid = (string)Mage_Sales_Model_Order_Invoice::STATE_PAID;
			
			foreach ($order->getInvoiceCollection() as $orderInvoice) {
			$orderInvoice->setState($paid)
					->setTransactionId($mtid)
					->setBaseGrandTotal($charge)
					->setGrandTotal($charge)
					->save();
			}
			}
		}
		
		$order->setBaseTotalPaid($charge); 
		$order->setTotalPaid($charge);
					
		$order->save();
		$order->sendNewOrderEmail()->setEmailSent(true)->save();
        $session->unsQuoteId();
		}
		
		} 
	
	}

    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getCpayQuoteId(true));
        $this->_redirect('checkout/cart');
     }


}