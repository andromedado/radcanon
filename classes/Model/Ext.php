<?php

class ModelExt extends Model {
	protected $dbFields = array(
	);
	protected $whatIAm = '';
	protected $table = '';
	protected $idCol = '';
	protected static $WhatIAm = '';
	protected static $Table = '';
	protected static $IdCol = '';
	protected static $AllData = array();
	
	public function load() {
		parent::load();
	}
	
}

?>