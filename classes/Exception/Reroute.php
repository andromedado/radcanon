<?php

class ExceptionReroute extends ExceptionBase
{
	protected $uri;
	
	public function __construct($newUri, $msg = 'Invalid Request', $code = 0, $previous = NULL)
	{
		$this->uri = $newUri;
		parent::__construct($msg, $code, $previous);
	}
	
	public function getUri()
	{
		return $this->uri;
	}
	
}
