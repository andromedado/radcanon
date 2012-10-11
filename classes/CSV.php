<?php

class CSV
{
	const TYPE_SESSION = 0;
	const NEW_LINE = "\n";
	
	protected $type;
	protected $name;
	protected $data;
	protected $delimiter;
	protected $quote;
	protected $quoteReplaceWith;
	protected $cellCount = 0;
	protected $rowCount = 0;
	
	protected $visible = array(
		'type', 'name', 'data', 'delimiter',
		'quote', 'quoteReplaceWith', 'cellCount', 'rowCount',
	);
	
	protected $preFilters = array();
	
	private static $InitCSV = false;
	private static $CSVDelimiter = ',';
	private static $CSVStringQuote = '"';
	private static $CSVPermittedQuote = "'";
	private static $Rows = 0;
	private static $Cells = 0;
	
	public function __construct (
		$name = 'data.csv',
		$storeType = self::TYPE_SESSION,
		$delimiter = ',',
		$quote = '"',
		array $preFilters = array()
	) {
		$this->name = $name;
		$this->type = $storeType;
		$this->delimiter = $delimiter;
		$this->quote = $quote;
		$this->quoteReplaceWith = $this->quote === '"' ? "'" : '"';
		switch ($this->type) {
			case self::TYPE_SESSION ://Intentional Fall-Thru
			default:
				if (!isset($_SESSION[__CLASS__])) $_SESSION[__CLASS__] = array();
				if (!isset($_SESSION[__CLASS__][$this->name])) $_SESSION[__CLASS__][$this->name] = '';
				$this->data =& $_SESSION[__CLASS__][$this->name];
		}
		$this->preFilters = $preFilters;
	}
	
	public function isEmpty()
	{
		return empty($this->data);
	}
	
	public function __get($var)
	{
		if (in_array($var, $this->visible)) {
			return $this->$var;
		}
		return null;
	}
	
	/**
	 * @return String
	 */
	public function getData()
	{
		return $this->data;
	}
	
	/**
	 * @return CSV
	 */
	public function clearData()
	{
		$this->data = '';
		return $this;
	}
	
	public function addPreFilter()
	{
		$filters = func_get_args();
		foreach ($filters as $filter) {
			$this->preFilters[] = $filter;
		}
	}
	
	protected function preFilterEntry(&$entry)
	{
		foreach ($this->preFilters as $filter) {
			$entry = call_user_func($filter, $entry);
		}
	}
	
	/**
	 * @return CSV
	 */
	public function addCell ($entry)
	{
		if ($this->cellCount > 0) $this->data .= $this->delimiter;
		$this->preFilterEntry($entry);
		if (is_null($entry)) $entry = 'NULL';
		if (is_object($entry) || is_array($entry)) $entry = json_encode($entry);
		$this->data .= $this->quote . str_replace($this->quote, $this->quoteReplaceWith, $entry) . $this->quote;
		if ($this->cellCount === 0) $this->rowCount++;
		$this->cellCount++;
		return $this;
	}
	
	/**
	 * @return CSV
	 */
	public function finishRow ()
	{
		$this->data .= self::NEW_LINE;
		$this->cellCount = 0;
		return $this;
	}
	
	/**
	 * @return CSV
	 */
	public function addRow (array $entries)
	{
		if ($this->cellCount > 0) {
			$this->finishRow();
		}
		$this->addCells($entries);
		return $this->finishRow();
	}
	
	/**
	 * @return CSV
	 */
	public function addCells(array $entries)
	{
		foreach ($entries as $entry) {
			$this->addCell($entry);
		}
		return $this;
	}
	
	/**
	 * @return CSV
	 */
	public function addRows (array $rows)
	{
		foreach ($rows as $row) {
			$this->addRow($row);
		}
		return $this;
	}
	
	public function handleDownloadResponse (Response $Response)
	{
		$Response->type = Response::TYPE_RAW_ECHO;
		$Response->contentType = 'text/csv';
		$Response->addHeader('Content-disposition: attachment; filename="' . str_replace('"', '', $this->name) . '"');
		return $this->data;
	}
	
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

