<?php
defined('PaZsCA8p') or exit;

abstract class UtilsMySQL {
	
	/**
	 * A takes a MySQL query and fetches the first column as
	 * Object ID, and instantiates the given Class
	 * @param string $sql MySQL Query
	 * @param string $Class Class Name
	 * @return Object|NULL Object Instance
	 */
	public static function fetchIdIntoInstance($sql, $Class, Exception $NotFoundException = NULL){
		$O = NULL;
		$r = dbq($sql, false);
		if (!$r) throw new ExceptionMySQL($sql);
		if (mysql_num_rows($r) > 0) {
			list($id) = mysql_fetch_row($r);
			if (method_exists($Class, 'getO')) {
				$O = call_user_func(array($Class,'getO'),$id);
			} else {
				$O = new $Class($id);
			}
		} elseif (!is_null($NotFoundException)) {
			throw $NotFoundException;
		}
		return $O;
	}
	
	/**
	 * A takes a MySQL query, and fetches the first columns
	 * as Object IDs, and instantiates the given Class
	 * @param string $sql MySQL Query
	 * @param string $Class Class Name
	 * @return array Array of Object Instances
	 */
	public static function fetchIdsIntoInstances($sql, $Class){
		$Os = array();
		$r = dbq($sql, false);
		if (!$r) throw new ExceptionMySQL($sql);
		if (mysql_num_rows($r) > 0) {
			$go = method_exists($Class, 'getO');
			while (list($id) = mysql_fetch_row($r)) {
				if ($go) {
					$Os[$id] = call_user_func(array($Class,'getO'),$id);
				} else {
					$Os[$id] = new $Class($id);
				}
			}
		}
		return $Os;
	}
	
}

?>