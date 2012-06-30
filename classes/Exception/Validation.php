<?php

/**
 * Exception Class for Validation Errors
 *
 * @package RadCanon
 * @author Shad Downey
 */
class ExceptionValidation extends ExceptionBase {
	protected static $publicMessage = 'Unable to complete your request:<br />%s';
	
	public function __construct($msg = '', $code = 0, $previous = NULL){
		$this->code = $code;
		$this->internalMessage = "Exception!,\nValidation Error: {$msg}, {$this->file}, {$this->line}";
		$this->message = sprintf(self::$publicMessage, $msg);
	}
	
	public function __toString(){
		return $this->message;
	}
	
	public function getInternalMessage() {
		return $this->internalMessage;
	}
	
	public static function getPublicMessage () {
		return self::$publicMessage;
	}
	
} // END

?>