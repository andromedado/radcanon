<?php

/**
 * Class representing Super Admins interacting with the application
 */
class SuperAdmin extends Admin {
	const MODEL_CLASS = 'ModelSuperAdmin';
	/** @var ModelSuperAdmin $Model */
	protected $Model = NULL;
	protected $AdiNavItems = array(
		'E-mail Templates' => array('EmailTemplate', 'review'),
		'Manage Admins' => array('Admin', 'manage'),
		'Export Data' => array('Data', 'export'),
	);
	
}

?>