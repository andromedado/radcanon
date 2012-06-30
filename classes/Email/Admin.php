<?php

abstract class EmailAdmin extends Email {
	/** @var ModelAdmin $Admin */
	protected $Admin = NULL;
	
	protected function load () {
		parent::load();
		if (is_null($this->Admin) || !is_a($this->Admin, 'ModelAdmin') || !$this->Admin->isValid()) {
			throw new ExceptionBase('Invalid Admin provided to ' . get_called_class());
		}
		$this->to = $this->Admin->getEmail();
	}
	
}

?>