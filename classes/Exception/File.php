<?php

class ExceptionFile extends ExceptionBase {
	
	public function __construct($msg='',$code=2,$previous=NULL){
		parent::__construct("File Exception: " . $msg, $code, $previous);
	}
	
}

?>