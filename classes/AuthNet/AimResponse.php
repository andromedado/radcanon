<?php

class AuthNetAimResponse extends AuthNetResponse {
	protected $raw;
	protected $rawArray;
	protected $aimArray;
	protected $aimFields = array(
		'Response Code',
		'Response Subcode',
		'Response Reason Code',
		'Response Reason Text',
		'Authorization Code',
		'AVS Response',
		'Transaction ID',
		'Invoice Number',
		'Description',
		'Amount',
		'Method',
		'Transaction Type',
		'Customer ID',
		'First Name',
		'Last Name',
		'Company',
		'Address',
		'City',
		'State',
		'ZIP Code',
		'Country',
		'Phone',
		'Fax',
		'Email Address',
		'Ship To First Name',
		'Ship To Last Name',
		'Ship To Company',
		'Ship To Address',
		'Ship To City',
		'Ship To State',
		'Ship To ZIP Code',
		'Ship To Country',
		'Tax',
		'Duty',
		'Freight',
		'Tax Exempty',
		'Purchase Order Number',
		'MD5 Hash',
		'Card Code Response',
		'Cardholder Authentication Verification Response',
		'Account Number',
		'Card Type',
		'Split Tender ID',
		'Requested Amount',
		'Balance On Card',
	);
	
	public function __construct ($csv, $delimiter = ',') {
		$this->raw = $csv;
		$this->rawArray = explode($delimiter, $csv);
		$this->aimArray = array();
		foreach ($this->rawArray as $k => $v) {
			if (!isset($this->aimFields[$k])) continue;
			$this->aimArray[$this->aimFields[$k]] = $v;
		}
		$this->isGood = $this->rawArray[0] === '1';
		$this->code = $this->rawArray[2];
		$this->text = $this->rawArray[3];
	}
	
	public function override (array $withInfo) {
		$this->aimArray = array_merge($this->aimArray, $withInfo);
		return $this;
	}
	
	public function getInfo () {
		return $this->aimArray;
	}
	
}

?>