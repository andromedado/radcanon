<?php

class AuthNetResponse extends AuthNet {
	/** @var Boolean $isGood */
	public $isGood;
	public $code;
	public $text;
	
	public function __get ($var) {
		if (defined('DEBUG') && DEBUG) return $var;
		return NULL;
	}
	
	public function __set ($var, $val) {
		if (defined('DEBUG') && DEBUT) $this->$var = $val;
		return $val;
	}
	
}

?>