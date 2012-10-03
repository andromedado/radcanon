<?php

class SQLSelect extends SQLQuery
{
	const MY_TYPE = self::TYPE_SELECT;
	const WRITE = false;
	protected $FromClause = 'FROM ';
	protected $FromTables = array();
	protected $joins = array();
	
	protected $WhereClause = 'WHERE ';
	
	public function addFromTable($tableName)
	{
		$this->FromTables[] = $tableName;
		$this->getTableAliasName($tableName);
	}
	
	public function addJoin(){}
	
}

