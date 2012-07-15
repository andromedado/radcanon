<?php
defined('PaZsCA8p') or exit;

/**
 * Base Exception Class for Rad Canon
 *
 * @package RadCanon
 * @author Shad Downey
 */
class ExceptionBase extends Exception {
	protected $internalMessage = '';
	protected static $publicMessage = 'The system has experienced an error (EC-%d), please try again later';
	
	public function __construct($msg = '', $code = 1, $previous = NULL){
		$this->code = $code;
		if (is_array($msg)) $msg = json_encode($msg);
		$this->internalMessage = "Exception!,\nError: {$msg}";
		$this->message = self::$publicMessage;
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