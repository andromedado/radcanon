<?php
defined('PaZsCA8p') or die('Include Only');

/**
 * Database Connection Factory
 */
abstract class DBCFactory {
	/**
	 * Segregate Read v. Write Queries?
	 */
	const SEGREGATE = false;
	private static $OpenQuote = '';
	private static $CloseQuote = '';
	private static $WriteConnectionResource;
	private static $ReadConnectionResource;
	/** @var PDO $WritePDOConnection */
	private static $WritePDOConnection;
	/** @var PDO $ReadPDOConnection */
	private static $ReadPDOConnection;
	
	private static $DefaultHost = '127.0.0.1';
	private static $DefaultType = 'sqlite';
	private static $DefaultDir = CACHE_DIR;
	private static $DefaultFile = 'radcanon.db';
	
	private static $WriteDBC = array();
	private static $ReadDBCs = array();
	
	public static function addReadInfo (array $info) {
		self::$ReadDBCs[] = $info;
	}
	
	public static function setWriteInfo (array $info) {
		self::$WriteDBC = $info;
	}

	public static function quote ($str) {
		self::constructPDO(false);
		return self::$OpenQuote . $str . self::$CloseQuote;
	}

	/**
	 * Create a PDO Connection of the requested type
	 * @param bool $write Write Connection? (Or just read)
	 * @return PDO
	 */
	private static function constructPDO ($write = true) {
		if (!self::SEGREGATE || $write || empty(self::$ReadDBCs)) {
			$db_info = self::$WriteDBC;
		} else {
			$db_info = self::$ReadDBCs[array_rand(self::$ReadDBCs, 1)];
		}
		$db_info['host'] = empty($db_info['host']) ? self::$DefaultHost : $db_info['host'];
		$type = empty($db_info['type']) ? self::$DefaultType : $db_info['type'];
		switch ($type) {
			case "mysql":
				self::$OpenQuote = self::$CloseQuote = '`';
				$dsn = 'mysql:dbname=' . $db_info['db'] . ';host=' . $db_info['host'];
				break;
			case "sqlsrv":
				self::$OpenQuote = '[';
				self::$CloseQuote = ']';
				$dsn = 'sqlsrv:Server=' . $db_info['host'] . ';Database=' . $db_info['db'];
				break;
			case "sqlite3":
			case "sqlite":
				$dsn = 'sqlite:' . empty($db_info['file']) ? self::$DefaultDir . self::$DefaultFile : $db_info['file'];
				$db_info['usr'] = $db_info['pwd'] = NULL;
				break;
			default:
				throw new ExceptionBase('Unknown DB Type: ' . $type);
		}
		return new PDO($dsn, $db_info['usr'], $db_info['pwd']);
	}
	
	/**
	 * Create a `mysql_connect` resource of the requested type
	 * @param bool $write Write Connection? (Or just read)
	 * @return resource
	 */
	private static function constructMC ($write = true) {
		if (!self::SEGREGATE || $write || empty(self::$ReadDBCs)) {
			$db_info = self::$WriteDBC;
		} else {
			$db_info = self::$ReadDBCs[array_rand(self::$ReadDBCs, 1)];
		}
		$db_info['host'] = empty($db_info['host']) ? '127.0.0.1' : $db_info['host'];
		$r = mysql_connect($db_info['host'], $db_info['usr'], $db_info['pwd']);
		if (!$r) throw new ExceptionMySQL('MySQL Connection Failed');
		mysql_select_db($db_info['db'], $r);
		return $r;
	}
	
	/**
	 * Fetch Read Connection (create it if it doesn't already exist)
	 * @return resource
	 */
	public static function rC () {
		if (is_null(self::$ReadConnectionResource)) {
			self::$ReadConnectionResource = self::constructMC(false);
		}
		return self::$ReadConnectionResource;
	}
	
	/**
	 * Fetch Write Connection (create it if it doesn't already exist)
	 * @return resource
	 */
	public static function wC () {
		if (is_null(self::$WriteConnectionResource)) {
			self::$WriteConnectionResource = self::constructMC(true);
		}
		return self::$WriteConnectionResource;
	}
	
	/**
	 * Fetch Read PDO Connection (create it if it doesn't already exist)
	 * @return PDO
	 */
	public static function rPDO () {
		if (is_null(self::$ReadPDOConnection)) {
			self::$ReadPDOConnection = self::constructPDO(false);
		}
		return self::$ReadPDOConnection;
	}
	
	/**
	 * Fetch Write PDO Connection (create it if it doesn't already exist)
	 * @return PDO
	 */
	public static function wPDO () {
		if (is_null(self::$WritePDOConnection)) {
			self::$WritePDOConnection = self::constructPDO(true);
		}
		return self::$WritePDOConnection;
	}
	
}
?>