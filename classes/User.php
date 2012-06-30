<?php

/**
 * Base class for people interacting with the site
 */
class User {
	protected $valid = false;
	
	public function __construct() {
		$this->valid = true;
	}
	
	public function isValid() {
		return $this->valid;
	}
}

?>