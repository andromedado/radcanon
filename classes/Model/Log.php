<?php

class ModelLog extends Model {
	protected $log_id;
	protected $category='misc';
	protected $criticality='0';
	protected $content='';
	protected $when='0000-00-00 00:00:00';
	protected $file='';
	protected $line=0;
	
	protected $dbFields = array(
		'log_id',
		'category',
		'criticality',
		'content',
		'when',
		'file',
		'line',
	);
	protected $whatIAm='Log';
	protected $table='logs';
	protected $idCol='log_id';
	protected static $WhatIAm='Log';
	protected static $Table='logs';
	protected static $IdCol='log_id';
	protected static $Criticalities=array('0'=>'None',
										  '1'=>'Fix Me',
										  '2'=>'Uh Ohhhh...');
	private static $ALL=array();
	protected static $AllData = array();
	
	protected function load(){
		parent::load();
		$this->c=__CLASS__;
	}
	
	public static function mkLog ($content, $category = 'misc', $criticality = '0', $file = '', $line = 0) {
		$stmt = DBCFactory::rPDO()->prepare("SELECT * FROM " . self::$Table . " WHERE " . self::$IdCol . " = ?");
		if (is_array($content) || is_object($content)) {
			$content = json_encode($content);
		}
		$logInfo = array(
			'content' => $content,
			'category' => $category,
			'addressed' => '0',
			'criticality' => $criticality,
			'file' => $file,
			'line' => $line,
		);
		if ($stmt) {
			$O = new self(0);
			try {
				$O->createWithVars($logInfo);
				return $O->id;
			} catch (ExceptionPDO $e) {
				
			}
		}
		if ($h = fopen(LOG_FILE, 'a+')) {
			$logInfo['when'] = date('Y-m-d H:i:s');
			fwrite($h, json_encode($logInfo) . "\n");
			fclose($h);
		}
	}

}

/*

CREATE TABLE [logs] (
  "log_id" INT IDENTITY NOT NULL PRIMARY KEY, 
  "category" VARCHAR(30) NOT NULL, 
  "criticality" SMALLINT NOT NULL, 
  `addressed` CHAR(1) NULL,
  "content" TEXT NOT NULL, 
  "when" TIMESTAMP NOT NULL, 
  "file" VARCHAR(255) NOT NULL, 
  "line" INT NOT NULL);

CREATE TABLE `logs` (
  `log_id` int(11) unsigned NOT NULL auto_increment,
  `category` varchar(20) NOT NULL default '',
  `criticality` enum('0','1','2') NOT NULL default '0',
  `addressed` enum('0','1') NULL default NULL,
  `file` varchar(120) NOT NULL,
  `content` text NOT NULL,
  `when` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `line` int(11) NOT NULL,
  PRIMARY KEY  (`log_id`),
  KEY `category` (`category`),
  KEY `criticality` (`criticality`),
  KEY `addressed` (`addressed`),
  KEY `file` (`file`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE VIEW `issues` AS
SELECT *
FROM `logs`
WHERE `criticality` > 1 AND `addressed` = _utf8'0';
 */

?>