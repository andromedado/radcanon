<?php

class AuthNetResponse extends AuthNet
{
	/** @var Boolean $isGood */
	public $isGood;
	public $code;
	public $text;
	
	public function __get ($var)
	{
		if (DEBUG) return $var;
		return NULL;
	}
	
	public function __set ($var, $val)
	{
		if (DEBUG) $this->$var = $val;
		return $val;
	}
	
}

