<?php

class CSV {
	
	private static $InitCSV = false;
	private static $CSVDelimiter = ',';
	private static $CSVStringQuote = '"';
	private static $CSVPermittedQuote = "'";
	private static $Rows = 0;
	private static $Cells = 0;
	
	public static function initCSV ($fname = 'report') {
		$_SESSION['CSV']['title'] = $fname;
		$_SESSION['CSV']['csv'] = '';
		self::$Rows = 0;
		self::$Cells = 0;
		self::$InitCSV = true;
	}
	
	public static function getLink() {
		return Html::n('a', APP_SUB_DIR . '/' . __CLASS__ . '/Download/st', 'Download as CSV');
	}
	
	public static function addCSVCell ($content = '') {
		if (self::$Cells > 0) $_SESSION['CSV']['csv'] .= self::$CSVDelimiter;
		if (is_null($content)) $content = 'NULL';
		$_SESSION['CSV']['csv'] .= self::$CSVStringQuote . str_replace(self::$CSVStringQuote, self::$CSVPermittedQuote, $content) . self::$CSVStringQuote;
		self::$Cells += 1;
	}
	
	public static function newCSVRow () {
		if (self::$Rows > 0) $_SESSION['CSV']['csv'] .= "\r\n";
		self::$Rows += 1;
		self::$Cells = 0;
	}
	
	public static function addCSVRow (array $cells) {
		self::newCSVRow();
		foreach ($cells as $content) {
			self::addCSVCell($content);
		}
	}
	
	public static function getContents () {
		return $_SESSION['CSV']['csv'];
	}
	
	/**
	 * Serve the most recently created CSV as a download
	 * @return void
	 */
	public static function view_Download () {
		header("Content-type: text/csv");
		header('Content-disposition: attachment; filename="'.$_SESSION['CSV']['title'].'.csv"');
		echo $_SESSION['CSV']['csv'];
		exit;
	}
	
}

?>