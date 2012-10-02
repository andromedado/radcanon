<?php

class ExceptionPDO extends ExceptionBase
{
	
	public function __construct(
		PDOStatement $stmt = NULL,
		$msg = '',
		$code = 2,
		$previous = NULL
	) {
		parent::__construct("PDO Error,\nAdiInfo: " . $msg . ' errors: ' . json_encode(is_null($stmt) ? 'null' : $stmt->errorInfo()), $code, $previous);
	}
	
}

