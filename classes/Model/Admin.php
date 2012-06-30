<?php

class ModelAdmin extends Model {
	const MIN_VALID_LEVEL = 0;
	const TMP_PASS_VALID_FOR = '+12 hours';
	protected $adid;
	protected $email;
	protected $salt = 'sw0rdf7z5';
	protected $pwd;
	protected $level = 0;
	protected $fname;
	protected $lname;
	protected $tmp_pass;
	protected $tmp_expires;
	protected $requires_password_change;
	
	protected $requiredFields = array(
		'fname' => 'First Name is required',
		'lname' => 'Last Name is required',
		'email' => 'Email Address is required',
	);
	protected $dbFields = array(
		'adid',
		'email',
		'salt',
		'pwd',
		'level',
		'fname',
		'lname',
		'tmp_pass',
		'tmp_expires',
		'requires_password_change',
	);
	protected $RowAttributes = array(
		'*getManageLinkedName' => 'Name',
		'email' => 'Email Address',
		'*displayIsSuperAdmin' => 'Super Admin',
	);
	protected $whatIAm = 'Admin';
	protected $table = 'admins';
	protected $idCol = 'adid';
	protected static $WhatIAm = 'Admin';
	protected static $Table = 'admins';
	protected static $IdCol = 'adid';
	protected static $AllData = array();
	protected static $HashPreference = array(
		'sha512', 'sha384', 'sha256', 'sha224', 'sha1',
	);
	protected static $HashAlgo = NULL;
	
	public function load() {
		parent::load();
		$this->c = get_called_class();
		$this->valid = $this->valid && $this->level >= static::MIN_VALID_LEVEL;
		$this->name = $this->fname . ' ' . $this->lname;
	}
	
	protected function preFilterVars (array $vars, $creating) {
		if (isset($vars['email'])) {
			if (!UtilsString::isEmail($vars['email'])) {
				throw new ExceptionValidation('Invalid E-mail Address Provided');
			}
		}
		$emailTaken = false;
		if ($creating) {
			if (isset($vars['email'])) {
				$emailTaken = static::findOne(array(
					'fields' => array(
						'email' => $vars['email'],
					),
				))->isValid();
			}
		} else {
			if (isset($vars['email'])) {
				$emailTaken = static::findOne(array(
					'fields' => array(
						'email' => $vars['email'],
					),
					'conditions' => array(
						'sql' => ' AND email != ?',
						'args' => array($vars['email']),
					),
				))->isValid();
			}
		}
		if ($emailTaken) {
			throw new ExceptionValidation('E-mail Address is already taken');
		}
	}
	
	protected function createFollowUp () {
		$this->salt = $salt = substr(md5(microtime(true)), -10);
		$firstPassword = strtoupper(substr(md5(microtime(true)), -8));
		$this->updateVars(array(
			'salt' => $salt,
			'pwd' => $this->hashPassword($firstPassword),
			'requires_password_change' => 1,
		));
		$E = new EmailWelcome($this, $firstPassword);
		$E->send();
	}
	
	public function displayIsSuperAdmin () {
		return 'No';
	}
	
	public function getManageLinkedName () {
		return AppLink::newLink($this->getName(), array('Admin', 'manage', $this->id));
	}
	
	public function updatePassword (array $post) {
		if (empty($post['new_pass']) || empty($post['confirm_new_pass'])) throw new ExceptionValidation('Password is required');
		if (preg_match('/^[A-Z]+$/i', $post['new_pass'])) throw new ExceptionValidation('Password must contain at least one number or special character');
		if (strlen($post['new_pass']) < 6) throw new ExceptionValidation('Password must be at least 6 characters long');
		if ($post['new_pass'] === $this->email) throw new ExceptionValidation('Password may not be your e-mail address');
		if ($post['new_pass'] !== $post['confirm_new_pass']) throw new ExceptionValidation('Passwords do not match');
		$this->updateVars(array(
			'pwd' => $this->hashPassword($post['new_pass']),
			'requires_password_change' => 0,
		));
	}
	
	public function requiresPasswordChange () {
		return $this->requires_password_change === '1';
	}
	
	public function getTmpPasswordExpires () {
		return $this->tmp_expires;
	}
	
	public function getEmail () {
		return $this->email;
	}
	
	/**
	 * @return String|Boolean
	 */
	public function setTemporaryPassword () {
		if ($this->tmp_expires > time()) return false;
		$tmpPassword = strtoupper(substr(md5(microtime()), -8));
		$this->updateVars(array(
			'tmp_pass' => $this->hashPassword($tmpPassword),
			'tmp_expires' => strtotime(self::TMP_PASS_VALID_FOR),
		));
		return $tmpPassword;
	}
	
	protected function hashPassword ($password) {
		$str = $password;
		$alg = self::getHashAlgo();
		for ($i = 0; $i < 1002; $i++) {
			$str = hash($alg, ($i % 2 ? $this->salt : '') . $str . ($i % 2 ? '' : $this->salt));
		}
		return $str;
	}
	
	public static function getHashAlgo () {
		if (is_null(self::$HashAlgo)) {
			$avail = hash_algos();
			foreach (self::$HashPreference as $algo) {
				if (in_array($algo, $avail)) {
					break;
				}
			}
			self::$HashAlgo = $algo;
		}
		return self::$HashAlgo;
	}
	
	public function recordLogin () {
		Admin::setLoginId($this->id);
		if ($this->tmp_expires > time()) {
			$this->updateVar('tmp_expires', 0);
		}
	}
	
	public function passwordAcceptable ($password) {
		if ($this->valid) {
			if ($this->hashPassword($password) === $this->pwd) {
				return true;
			}
			if ($this->tmp_expires > time() && $this->hashPassword($password) === $this->tmp_pass) {
				$this->updateVars(array(
					'requires_password_change' => 1,
					'tmp_expires' => 0,
				));
				return true;
			}
		}
		return false;
	}
	
}

/*

CREATE TABLE admins ( 
  "adid" INT IDENTITY NOT NULL PRIMARY KEY, 
  "email" VARCHAR(100) NOT NULL, 
  "salt" VARCHAR(10) NULL, 
  "pwd" VARCHAR(128) NULL, 
  "level" SMALLINT NOT NULL, 
  "fname" VARCHAR(80) NOT NULL, 
  "lname" VARCHAR(80) NOT NULL, 
  "tmp_pass" VARCHAR(128) NULL , 
  "tmp_expires" INT NULL , 
  "requires_password_change" SMALLINT NULL );

 */

?>