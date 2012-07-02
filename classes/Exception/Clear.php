<?php

class ExceptionClear extends ExceptionBase {
	protected $addLogId = true;
	
	public function __construct ($msg = '', $code = 0, $previous = NULL) {
		$this->code = $code;
		$this->message = $msg;
		$lid = ModelLog::mkLog("Exception!,\nError: " . $msg, get_class($this), $this->code, $this->file, $this->line);
		if ($this->addLogId) $this->message .= ' (EC-' . $lid . ')';
	}
	
	public function __toString(){
		return $this->message;
	}
	
}

?>