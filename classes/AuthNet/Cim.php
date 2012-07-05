<?php

class AuthNetCim extends AuthNet {
	public $id = 0;
	/** @var AuthNetXMLRequest $AuthNetXMLRequest */
	protected $AuthNetXMLRequest = NULL;
	
	public function __construct ($id = NULL) {
		$this->id = $id;
	}
	
	/**
	 * @param String $login_id
	 * @param String $transaction_key
	 * @return AuthNetXMLRequest
	 */
	public function getAuthNetXMLRequest ($login_id = NULL, $transaction_key = NULL) {
		if (!is_null($login_id) || is_null($this->AuthNetXMLRequest)) {
			$this->AuthNetXMLRequest = new AuthNetXMLRequest($login_id, $transaction_key);
		}
		return $this->AuthNetXMLRequest;
	}
	
	public function isValid() {
		return $this->id > 0;
	}
	
	public function getPublicError ($code, $type = 'cim') {
		return parent::getPublicError($code, $type);
	}
	
}

?>