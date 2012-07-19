<?php

abstract class ControllerOnlyUserType extends ControllerApp {
	protected $UserType = 'AuthUser';
	protected $permittedMethods = array(
		'notFound'
	);
	
	public function prefilterInvocation (&$method, array &$arguments) {
		parent::prefilterInvocation($method, $arguments);
		if (!in_array($method, $this->permittedMethods) && !is_a($this->user, $this->UserType)) {
			if (DEBUG) {
				$this->set('tried', array($method, $arguments));
			}
			$method = 'notFound';
		}
	}
	
}
