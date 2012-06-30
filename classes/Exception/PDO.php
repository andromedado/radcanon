<?php
defined('PaZsCA8p') or exit;

class ExceptionPDO extends ExceptionBase {
	
	public function __construct(PDOStatement $stmt = NULL, $msg = '', $code = 2, $previous = NULL){
		parent::__construct("PDO Error,\nAdiInfo: " . $msg, $code, $previous);
	}
	
}

?>