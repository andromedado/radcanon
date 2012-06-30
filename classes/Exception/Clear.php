<?php
defined('PaZsCA8p') or exit;

class ExceptionClear extends ExceptionBase {
	
	public function __construct($msg='',$code=0,$previous=NULL){
		$this->code=$code;
		$this->message=$msg;
		$lid = ModelLog::mkLog("Exception!,\nError: ".$msg,__CLASS__,$this->code,$this->file,$this->line);
		$this->message.=' (EC-'.$lid.')';
	}
	
	public function __toString(){
		return $this->message;
	}
	
}

?>