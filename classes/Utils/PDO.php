<?php

abstract class UtilsPDO
{
	
	public static function writeExecute($sql, $args = array())
	{
		$stmt = DBCFactory::wPDO()->prepare($sql);
		if (!$stmt) throw new ExceptionBase(DBCFactory::wPDO()->errorInfo());
		return $stmt->execute($args);
	}
	
	public static function getResultSetColumns($sql, array $params = array())
	{
		$Columns = array();
		$stmt = DBCFactory::rPDO()->prepare($sql);
		if (!$stmt) throw new ExceptionBase(DBCFactory::rPDO()->errorInfo(), 1);
		$r = $stmt->execute($params);
		if (!$r) throw new ExceptionPDO($stmt);
		$Columns = UtilsArray::autoAmalgamateArrays($stmt->fetchAll(PDO::FETCH_NUM));
		return $Columns;
	}
	
	public static function getResultSetColumn(
		$column,
		$sql,
		array $params = array()
	) {
		$R = array();
		$Columns = self::getResultSetColumns($sql, $params);
		if (array_key_exists($column, $Columns)) {
			$R = $Columns[$column];
		}
		return $R;
	}
	
	/**
	 * Using the given SQL, params and Class, fetch Instances from the resulting Ids
	 * @param String $sql
	 * @param Array $params
	 * @param String|Array $Class ClassName or Array Argument for call_user_func to generate Object
	 * @return Array
	 */
	public static function fetchIdsIntoInstances($sql, array $params = array(), $Class)
	{
		$Os = array();
		$stmt = DBCFactory::rPDO()->prepare($sql);
		if (!$stmt) throw new ExceptionBase(DBCFactory::rPDO()->errorInfo(), 1);
		$r = $stmt->execute($params);
		if ($r) {
			$data = $stmt->fetchAll(PDO::FETCH_NUM);
			foreach ($data as $result) {
				if (is_array($Class)) {
					$Os[$result[0]] = call_user_func($Class, $result[0]);
				} else {
					$Os[$result[0]] = new $Class($result[0]);
				}
			}
		}
		return $Os;
	}
	
	/**
	 * Using the given SQL, params and Class, fetch Instance from the resulting Id
	 * @param String $sql
	 * @param Array $params
	 * @param String|Array $Class ClassName or Array Argument for call_user_func to generate Object
	 * @return stdClass
	 */
	public static function fetchIdIntoInstance($sql, array $params = array(), $Class)
	{
		$id = 0;
		$stmt = DBCFactory::rPDO()->prepare($sql);
		if (!$stmt) throw new ExceptionBase(DBCFactory::rPDO()->errorInfo(), 1);
		$r = $stmt->execute($params);
		if ($r) {
			list($id) = $stmt->fetch(PDO::FETCH_NUM);
		}
		if (is_array($Class)) {
			$O = call_user_func($Class, $id);
		} else {
			$O = new $Class($id);
		}
		return $O;
	}
	
}

