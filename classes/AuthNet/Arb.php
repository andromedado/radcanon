<?php

class AuthNetArb extends AuthNetXmlAble {
	
	public function getPublicError ($code, $type = 'arb') {
		return parent::getPublicError($code, $type);
	}
	
	/**
	 * @throws ExceptionAuthNet
	 * @param Array $data
	 */
	public function create (array $data) {
		$mustNotBeEmpty = array(
			'length',
			'unit',
			'startDate',
			'totalOccurrences',
			'amount',
			'cardNumber',
			'expirationDate',
			'firstName',
			'lastName',
		);
		foreach ($mustNotBeEmpty as $field) {
			if (empty($data[$field])) {
				throw new ExceptionAuthNet('Invalid input for `' . $field . '`');
			}
			$$field = $data[$field];
		}
		$info = array('subscription' => array());
		UtilsArray::ifKeyAddToThis('name', $data, $info['subscription']);
		$info['subscription']['paymentSchedule'] = array(
			'interval' => array(
				'length' => $length,
				'unit' => $unit,
			),
			'startDate' => date('Y-m-d', strtotime($startDate)),
			'totalOccurrences' => $totalOccurrences,
		);
		UtilsArray::ifKeyAddToThis('trialOccurrences', $data, $info['subscription']['paymentSchedule']);
		$info['subscription']['amount'] = $amount;
		UtilsArray::ifKeyAddToThis('trialAmount', $data, $info['subscription']);
		$info['subscription']['payment'] = array(
			'creditCard' => array(
				'cardNumber' => $cardNumber,
				'expirationDate' => date('Y-m', strtotime($expirationDate)),
			),
		);
		UtilsArray::ifKeyAddToThis('cardCode', $data, $info['subscription']['payment']['creditCard']);
		if (isset($data['invoiceNumber']) || isset($data['description'])) {
			$info['subscription']['order'] = array();
			UitlsArray::ifKeyAddToThis('invoiceNumber', $data, $info['subscription']['order']);
			UitlsArray::ifKeyAddToThis('description', $data, $info['subscription']['order']);
		}
		if (
			isset($data['customerId']) ||
			isset($data['email']) ||
			isset($data['phoneNumber']) ||
			isset($data['faxNumber'])
		) {
			$info['subscription']['customer'] = array();
			UitlsArray::ifKeyAddToThis('customerId', $data, $info['subscription']['customer'], 'id');
			UitlsArray::ifKeyAddToThis('email', $data, $info['subscription']['customer']);
			UitlsArray::ifKeyAddToThis('phoneNumber', $data, $info['subscription']['customer']);
			UitlsArray::ifKeyAddToThis('faxNumber', $data, $info['subscription']['customer']);
		}
		$info['subscription']['billTo'] = array(
			'firstName' => $firstName,
			'lastName' => $lastName,
		);
		$optionalBillTo = array('company', 'address', 'city', 'state', 'zip', 'country');
		foreach ($optionalBillTo as $field) {
			UtilsArray::ifKeyAddToThis($field, $data, $info['subscription']['billTo']);
		}
		
		
		$R = $this->getAuthNetXMLRequest()->getAuthNetXMLResponse('ARBCreateSubscriptionRequest', $info);
		if (!$R->isGood) {
			if (DEBUG) ModelLog::mkLog(array($R, $info), 'arb', '0', __FILE__, __LINE__);
			throw new ExceptionAuthNet($this->getPublicError($R->code));
		}
		return strval($R->XML->customerProfileId);
	}
	
}

