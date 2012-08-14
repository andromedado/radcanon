<?php

/**
 * Authorize.net XML Request Component
 *
 * @package RadCanon.AuthNet
 * @author Shad Downey
 * @version 1.1
 */
class AuthNetXMLRequest extends AuthNet {
	protected $MultiInstancesPermissible = array(
		'lineItems',
	);
	protected $NVPCdataElements = array(
		'extraOptions',
	);
	
	public function __construct ($login_id = NULL, $transaction_key = NULL) {
		$this->login_id = is_null($login_id) ? AuthNet::$default_login_id : $login_id;
		$this->transaction_key = is_null($transaction_key) ? AuthNet::$default_transaction_key : $transaction_key;
	}
	
	/**
	 * Get an stdClass wrapped XML Response
	 * The Wrapper has standardized attributes
	 * ->isGood bool Was the resultCode 'Ok'?
	 * ->code string The Response Code
	 * ->text string The Response Text
	 * ->XML SimpleXMLElement XML Response
	 * 
	 * @param string $type The Type of Request [e.g. createCustomerProfileRequest]
	 * @param array $info The info to put into the request
	 * @return AuthNetXMLResponse
	 */
	public function getAuthNetXMLResponse($requestType, array $information) {
		return new AuthNetXMLResponse($this->getResponse($requestType, $information, 'xml'), array($requestType, $information));
	}
	
	/**
	 * Get a response given the Request Type and Information
	 * @param string $type The Type of Request [e.g. createCustomerProfileRequest]
	 * @param array $info The info to put into the request
	 * @param string $returnType How the Respons should be returned
	 * @return string Response
	 */
	public function getResponse($requestType, array $information, $returnType = 'string') {
		$Return = $this->cURL($this->buildRequest($requestType, $information));
		switch ($returnType) {
			case "xml":
				$Return = @simplexml_load_string($Return);//->saveXML());
			break;
			case "string":
			default:
				
		}
		return $Return;
	}
	
	/**
	 * Execute a cURL Request
	 * and return the response
	 * 
	 * @param string $xml XML of the Request
	 * @return string XML Response
	 */
	protected function cURL($xml) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::REQUEST_URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
	
	/**
	 * Builds an Authorize.net XML Request of the given type,
	 * with the given information
	 * 
	 * @param string $type The Type of Request [e.g. createCustomerProfileRequest]
	 * @param array $info The info to put into the request
	 * @return string XML formatted request
	 */
	protected function buildRequest($type, array $info) {
		$info = array_merge(array('merchantAuthentication' => array('name' => $this->login_id, 'transactionKey' => $this->transaction_key)), $info);
		$xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
		$xml .= '<' . $type . ' xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">' . "\n";
		$xml .= $this->xmlIfiy($info, $type);
		$xml .= "\n" . '</' . $type . '>';
		if (DEBUG) ModelLog::mkLog($xml, 'anXML');
		return $xml;
	}
	
	/**
	 * Turns an array into XML [No Attributes]
	 * Calls itself, so sometimes input isn't an array
	 * 
	 * @param array|string $info Array or String
	 * @param String $parentNode
	 * @return string XML
	 */
	protected function xmlIfiy($info, $parentNode, $ignoreParent = false) {
		$xml = '';
		if (is_array($info) || is_object($info)) {
			foreach ($info as $key => $val) {
				if (!is_numeric($key) && in_array($key, $this->NVPCdataElements) && is_array($val)) {
					$xml .= '<' . $key . '><![CDATA[';
					$amp = '';
					foreach ($val as $K => $V) {
						$xml .= $amp . $K . '=' . urlencode($V);
						$amp = '&';
					}
					$xml .= ']]></' . $key . '>';
					continue;
				}
				$ign = false;
				if (!$ignoreParent && in_array($parentNode, $this->MultiInstancesPermissible) && is_numeric($key)) {
					$ign = true;
					$pre = '<' . $parentNode . '>';
					$post = '</' . $parentNode . '>';
					$key = $parentNode;
				} elseif (in_array($key, $this->MultiInstancesPermissible) && is_array($val) && is_array(current($val))) {
					$pre = $post = '';
				} else {
					$pre = '<' . $key . '>';
					$post = '</' . $key . '>';
				}
				$xml .= $pre;
				$xml .= $this->xmlIfiy($val, $key, $ign);
				$xml .= $post;
			}
		} else {
			$xml .= $info;
		}
		return $xml;
	}
	
}
