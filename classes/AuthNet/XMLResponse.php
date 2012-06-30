<?php

class AuthNetXMLResponse extends AuthNetResponse {
	/** @var SimpleXMLElement $XML */
	public $XML;
	
	public function __construct (SimpleXMLElement $xml) {
		$this->XML = $xml;
		$this->isGood = strtolower(strval($this->XML->messages->resultCode)) === 'ok';
		$this->code = strval($this->XML->messages->message->code);
		$this->text = strval($this->XML->messages->message->text);
	}
	
}

?>