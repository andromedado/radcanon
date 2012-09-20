<?php

/**
 * Base class for people interacting with the site
 */
class User
{
	protected $valid = false;
	
	public function __construct()
	{
		$this->valid = true;
	}
	
	public function __get($var)
	{
		return false;
	}
	
	public function __call($func, $args)
	{
		return false;
	}
	
	public function isValid()
	{
		return $this->valid;
	}
}

