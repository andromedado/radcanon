<?php

class ModelIpLog extends Model {
	protected $ip;
	protected $pip;
	protected $user_agent = '';
	protected $hits = 0;
	protected $last_hit;
	protected $reputation;
	
	protected $dbFields = array(
		'ip',
		'pip',
		'user_agent',
		'hits',
		'last_hit',
		'reputation',
	);
	protected $whatIAm = 'Ip Log Entry';
	protected $table = 'ip_log';
	protected $idCol = 'ip';
	protected static $WhatIAm = 'Ip Log Entry';
	protected static $Table = 'ip_log';
	protected static $IdCol = 'ip';
	protected static $AllData = array();
}

?>