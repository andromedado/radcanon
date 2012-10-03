<?php

abstract class SQLQuery
{
	const TYPE_SELECT = 'SELECT';
	const TYPE_DELETE = 'DELETE';
	const TYPE_INSERT = 'INSERT';
	const TYPE_UPDATE = 'UPDATE';
	const MY_TYPE = null;
	const WRITE = null;
	const TABLE_ALIAS_BASE = 'table_';
	
	/** @var String $type */
	protected $type;
	/** @var Array $tables */
	protected $tables;
	/** @var Array $tablesToAlias */
	protected $tablesToAlias;
	/** @var Integer $tablesReferenced */
	protected $tablesReferenced = 0;
	
	private static $types = array(
		self::TYPE_SELECT, self::TYPE_DELETE,
		self::TYPE_INSERT, self::TYPE_UPDATE,
	);
	
	public function __construct(
		$type = static::MY_TYPE
	) {
		if (!in_array($type, self::$types)) {
			throw new ExceptionSQL('Invalid Query Type');
		}
		$this->type = $type;
		$this->load();
	}
	
	protected function load() {}
	
	protected function getTableAliasName($tableName)
	{
		if (!in_array($tableName, $this->tables)) {
			$this->tables[] = $tableName;
			$this->tablesReferenced++;
			$this->tablesToAlias[$tableName] = static::TABLE_ALIAS_BASE . $this->tablesReferenced;
		}
		return $this->tablesToAlias[$tableName];
	}
	
}

