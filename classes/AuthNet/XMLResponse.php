<?php

class AuthNetXMLResponse extends AuthNetResponse {
	/** @var SimpleXMLElement $XML */
	public $XML;
	protected $additionalInfo;
	
	public function __construct (SimpleXMLElement $xml, $additionalInfo = NULL) {
		$this->XML = $xml;
		$this->additionalInfo = $additionalInfo;
		$this->isGood = strtolower(strval($this->XML->messages->resultCode)) === 'ok';
		$this->code = strval($this->XML->messages->message->code);
		$this->text = strval($this->XML->messages->message->text);
	}
	
}
