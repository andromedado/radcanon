<?php

class AuthNetCim extends AuthNetXmlAble {
	
	public function getPublicError ($code, $type = 'cim') {
		return parent::getPublicError($code, $type);
	}
	
}

