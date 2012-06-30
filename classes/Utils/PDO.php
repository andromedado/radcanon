<?php

abstract class UtilsPDO {
	
	/**
	 * Using the given SQL, params and Class, fetch Instances from the resulting Ids
	 * @param String $sql
	 * @param Array $params
	 * @param String|Array $Class ClassName or Array Argument for call_user_func to generate Object
	 * @return Array
	 */
	public static function fetchIdsIntoInstances($sql, array $params, $Class) {
		$Os = array();
		$stmt = DBCFactory::rPDO()->prepare($sql);
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
	public static function fetchIdIntoInstance($sql, array $params, $Class) {
		$id = 0;
		$stmt = DBCFactory::rPDO()->prepare($sql);
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

?>