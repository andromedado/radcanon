<?php

class ModelIpLog extends ModelApp
{
	protected $ip;
	protected $pip;
	protected $user_agent = '';
	protected $hits = 0;
	protected $last_hit;
	protected $reputation = 0;
	
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

/*
CREATE TABLE `ip_log` (
  `ip` bigint(20) NOT NULL,
  `pip` varchar(60) NOT NULL DEFAULT '',
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `hits` bigint(20) NOT NULL,
  `last_hit` int(11) NOT NULL,
  `reputation` tinyint(4) NOT NULL,
  PRIMARY KEY (`ip`),
  KEY `user_agent` (`user_agent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 */
