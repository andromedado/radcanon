<?php

class ExceptionPermission extends ExceptionBase
{

	public function __construct($msg = '', $code = 1, $previous = NULL)
	{
		$this->code=$code;
		$lid = ModelLog::mkLog("Permission Exception:,\nMessage: ".$msg,__CLASS__,$this->code,$this->file,$this->line);
		$this->message='You do not have permission to perform the requested action (EC-'.$lid.')';
	}

	public function __toString()
	{
		return $this->message;
	}

}

