<?php

class AuthNetCimPayProfile extends AuthNetCim {
	protected static $permissibleTransactionTypes = array(
		'profileTransAuthOnly',
		'profileTransAuthCapture',
		'profileTransCaptureOnly',
		'profileTransPriorAuthCapture',
		'profileTransRefund',
		'profileTransVoid',
	);
	
	public function buildProfileTransAuthOnlyRequestArray (array $raw) {
		$reqsAndMaxLength = array(
			'amount' => NULL,
			'customerProfileId' => NULL,
		);
		foreach ($reqsAndMaxLength as $field => $maxLength) {
			if (!isset($raw[$field])) {
				throw new ExceptionAuthNet('Missing Field: ' . $field);
			} elseif (!is_null($maxLength) && strlen($raw[$field]) > $maxLength) {
				$raw[$field] = substr($raw[$field], 0, $maxLength);
			}
		}
		$data = $raw;
		$Data = new UtilsArray($data, 0);
		
		$Arr = array('amount' => $data['amount']);
		if (isset($data['taxAmount'])) {
			$Arr['tax'] = array(
				'amount' => $data['taxAmount'],
				'name' => $Data->taxName,
				'description' => $Data->taxDescription,
			);
		}
		if (isset($data['shippingAmount'])) {
			$Arr['shipping'] = array(
				'amount' => $data['shippingAmount'],
				'name' => $Data->shippingName,
				'description' => $Data->shippingDescription,
			);
		}
		if (isset($data['dutyAmount'])) {
			$Arr['duty'] = array('amount' => $data['dutyAmount']);
			UtilsArray::ifKeyAddToThis('dutyName', $Data, $Arr['duty'], 'name');
			UtilsArray::ifKeyAddToThis('dutyDescription', $Data, $Arr['duty'], 'description');
		}
		if (isset($data['lineItems'])) {
			$Arr['lineItems'] = array();
			$c = 0;
			$lineItemFields = array(
				'itemId', 'name', 'description', 'quantity', 'unitPrice', 'taxable',
			);
			foreach ($data['lineItems'] as $ItemsArr) {
				if ($c === 30) break;
				$IA = new UtilsArray($ItemsArr);
				$buildTo = array();
				foreach ($lineItemFields as $f) {
					UtilsArray::ifKeyAddToThis($f, $ItemsArr, $buildTo);
				}
				$Arr['lineItems'][] = $buildTo;
				$c++;
			}
		}
		$Arr['customerProfileId'] = $data['customerProfileId'];
		$Arr['customerPaymentProfileId'] = $this->id;
		UtilsArray::ifKeyAddToThis('customerShippingAddressId', $data, $Arr);
		if (isset($data['invoiceNumber'])) {
			$Arr['order'] = array('invoiceNumber' => $data['invoiceNumber']);
			UtilsArray::ifKeyAddToThis('orderDescription', $data, $Arr['order'], 'description');
			UtilsArray::ifKeyAddToThis('purchaseOrderNumber', $data, $Arr['order']);
		}
		UtilsArray::ifKeyAddToThis('taxExempt', $data, $Arr);
		UtilsArray::ifKeyAddToThis('cardCode', $data, $Arr);
		UtilsArray::ifKeyAddToThis('splitTenderId', $data, $Arr);
		return $Arr;
	}

	/**
	 * Create a transaction for this pay profile
	 * @param Array $raw
	 * @return AuthNetAimResponse
	 */
	public function createTransaction (array $raw) {
		if (!$this->isValid()) {
			throw new ExceptionAuthNet('Invalid Pay Profile');
		}
		if (!in_array($raw['transactionType'], static::$permissibleTransactionTypes)) {
			throw new ExceptionAuthNet('transaction type not recognized');
		}
		$type = $raw['transactionType'];
		$buildFunc = 'build' . ucfirst($type) . 'RequestArray';
		if (!method_exists($this, $buildFunc)) {
			throw new ExceptionBase('Transaction type ' . $type . ' not implemented!');
		}
		$RequestData = array(
			'transaction' => array(
				$type => call_user_func(array($this, $buildFunc), $raw),
			),
		);
		$R = $this->getAuthNetXMLRequest()->getAuthNetXMLResponse('createCustomerProfileTransactionRequest', $RequestData);
		if (!$R->isGood) {
			throw new ExceptionAuthNet($this->getPublicError($R->code));
		}
		$Response = new AuthNetAimResponse(strval($R->XML->directResponse));
		if (!$Response->isGood) {
			ModelLog::mkLog($Response->getInfo(), 'cim_pp', 1);
			throw new ExceptionAuthNet(AuthNet::getPublicError($Response->code, 'aim'));
		}
		return $Response;
	}
	
	public function createPayProfile (array $raw) {
		$reqsAndMaxLength = array(
			'customerProfileId' => NULL,
			'firstName' => 50,
			'lastName' => 50,
			'address' => 60,
			'zip' => 20,
			'cardNumber' => 16,
			'expirationDate' => 7,
			'cardCode' => 4,
		);
		foreach ($reqsAndMaxLength as $field => $maxLength) {
			if (!isset($raw[$field])) {
				throw new ExceptionAuthNet('Missing Field: ' . $field);
			} elseif (!is_null($maxLength) && strlen($raw[$field]) > $maxLength) {
				$raw[$field] = substr($raw[$field], 0, $maxLength);
			}
		}
		$data = $raw;
		
		$PayProfileInfo = array(
			'customerProfileId' => $data['customerProfileId'],
			'paymentProfile' => array(
				'customerType' => isset($data['customerType']) ? $data['customerType'] : 'individual',
				'billTo' => array(
					'firstName' => $data['firstName'],
					'lastName' => $data['lastName'],
					'address' => $data['address'],
					'zip' => $data['zip'],
					'country' => isset($data['country']) ? $data['country'] : 'usa',
				),
				'payment' => array(
					'creditCard' => array(
						'cardNumber' => $data['cardNumber'],
						'expirationDate' => $data['expirationDate'],
						'cardCode' => $data['cardCode'],
					),
				),
			),
			'validationMode' => DEBUG ? 'testMode' : 'liveMode',
		);
		
		$R = $this->getAuthNetXMLRequest()->getAuthNetXMLResponse('createCustomerPaymentProfileRequest', $PayProfileInfo);
		if (!$R->isGood) throw new ExceptionAuthNet($this->getPublicError($R->code));
		return strval($R->XML->customerPaymentProfileId);
	}
	
}

?>