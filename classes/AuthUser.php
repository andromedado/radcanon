<?php

/**
 * Base class for representing an authenticated user
 * Encapsulates the logic for persisting their `login`
 */
class AuthUser extends User {
	const SESSION_KEY = '_AuthUser_ID';
	const MODEL_CLASS = 'ModelUser';
	/** @var ModelUser $Model */
	protected $Model = NULL;
	
	public function __construct() {
		$id = empty($_SESSION[static::SESSION_KEY]) ? 0 : $_SESSION[static::SESSION_KEY];
		$c = static::MODEL_CLASS;
		$this->Model = new $c($id);
		$this->valid = $this->Model->isValid();
	}
	
	public function __call ($func, $args) {
		return call_user_func_array(array($this->Model, $func), $args);
	}
	
	public function __get ($var) {
		return $this->Model->$var;
	}
	
	/**
	 * @return Model
	 */
	public function getModel () {
		return $this->Model;
	}
	
	public static function removeLoginId () {
		$_SESSION[static::SESSION_KEY] = NULL;
	}
	
	public static function setLoginId ($id) {
		$_SESSION[static::SESSION_KEY] = $id;
	}
}

?>