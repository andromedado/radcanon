<?php

abstract class Model implements Iterator
{
	protected $valid;
	protected $id;
	protected $name;
	protected $_position = null;
	
	protected $baseName;
	protected $foundWith = NULL;
	protected $dbFields = array();
	protected $dontUpdate = array();
	protected $genericallyAvailable = array(
		'id', 'idCol', 'table', 'whatIAm', 'baseName',
	);
	protected $readOnly = array(
	);
	protected $requiredFields = array(
	);
	protected $symmetricallyEncryptedFields = array(
	);	
	protected $RowAttributes = array(
	);
	protected $PermissionsSet = false;
	protected $canSee = false;
	protected $canEdit = false;
	protected $whatIAm;
	protected $table;
	protected $idCol;
	protected static $WhatIAm;
	protected static $Table;
	protected static $IdCol;
	protected static $sortField = null;
	protected static $AllData = array();
	
	public function __construct ($id = 0) {
		if (!empty($this->dbFields)) {
			$this->_position = 0;
		}
		$this->loadAs($id);
	}
	
	public function rewind()
	{
		if (!empty($this->dbFields)) {
			$this->_position = 0;
		} else {
			$this->_position = null;
		}
	}
	
	public function current()
	{
		if (!empty($this->dbFields)) {
			$var = $this->dbFields[$this->_position];
			return $this->$var;
		}
		return null;
	}
	
	public function key()
	{
		if (!empty($this->dbFields)) {
			return $this->dbFields[$this->_position];
		}
		return null;
	}
	
	public function next()
	{
		if (!empty($this->dbFields)) {
			$this->_position += 1;
		}
	}
	
	public function valid()
	{
		return array_key_exists($this->_position, $this->dbFields);
	}
	
	public function importFromCsv(
		$csvPath,
		$headerRow = false,
		$delimiter = ',',
		$enclosure = '"',
		$escape = '\\'
	) {
		$handle = fopen($csvPath, 'r');
		if (!$handle) throw new ExceptionValidation('Invalid File');
		$useKey = array();
		$cols = $values = $comma = '';
		if ($headerRow) {
			$headers = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
			if ($headers === false) throw new ExceptionValidation('Invalid CSV');
			foreach ($headers as $key => $header) {
				if (in_array($header, $this->dbFields)) {
					$useKey[] = $key;
					$cols .= $comma . DBCFactory::quote($header);
					$values .= $comma . '?';
					$comma = ', ';
				}
			}
		} else {
			$row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
			if ($row === false) throw new ExceptionValidation('Invalid CSV');
			rewind($handle);
			$until = min(count($row), count($this->dbFields));
			for ($i = 0; $i < $until; $i++) {
				$useKey[] = $i;
				$cols .= $comma . DBCFactory::quote($this->dbFields[$i]);
				$values .= $comma . '?';
				$comma = ', ';
			}
		}
		$sql = "INSERT INTO " . DBCFactory::quote($this->table) . " ({$cols}) VALUES({$values})";
		$stmt = DBCFactory::wPDO()->prepare($sql);
		while (($data = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
			$values = UtilsArray::getValuesForTheseKeys($useKey, $data);
			$r = $stmt->execute($values);
		}
	}

	public function getRawData()
	{
		$d = array();
		foreach ($this->dbFields as $f) {
			$d[$f] = $this->$f;
		}
		return $d;
	}
	
	public function getData () {
		$d = isset(static::$AllData[$this->id]) ? static::$AllData[$this->id] : array();
		foreach ($this->genericallyAvailable as $f) {
			$d[$f] = $this->$f;
		}
		if (empty(static::$AllData[$this->id])) {
			$d = array_merge($d, $this->getRawData());
		}
		foreach ($this->readOnly as $f) {
			$d[$f] = $this->$f;
		}
		$d['isValid'] = $this->isValid();
		if (!isset($d['href'])) $d['href'] = FilterRoutes::buildUrl(array($this->baseName, 'review', $this->id));
		$this->appendAdditionalData($d);
		return $d;
	}
	
	protected function appendAdditionalData(array &$data) {
		
	}
	
	public function __get ($property) {
		if ((in_array($property, $this->dbFields) || in_array($property, $this->genericallyAvailable) || in_array($property, $this->readOnly)) && isset($this->$property)) return $this->$property;
		return NULL;
	}
	
	public function __set ($var, $val) {
		if (DEBUG) {
			return $this->$var = $val;
		}
		return false;
	}
	
	public function loadAs($id) {
		$this->id = abs((int)$id);
		$this->valid = $this->loadFromCache() || $this->loadFromTable();
		$this->c = get_called_class();
		$this->baseName = preg_replace('/^Model/', '', $this->c);
		$this->load();
	}
	
	/**
	 * Does this Model belong to the given Model?
	 * @param Model $M
	 * @return Boolean
	 */
	public function belongsTo (Model $M) {
		$idc = $M->idCol;
		return $this->$idc === $M->$idc;
	}
	
	public function idInArray (array $haystack) {
		return in_array($this->id, $haystack);
	}
	
	public function safeFindAll (array $options) {
		$options['fields'] = UtilsArray::filterWithWhiteList(isset($options['fields']) ? $options['fields'] : array(), $this->dbFields, false);
		return static::findAll($options);
	}
	
	public function safeFindAllLike (array $options) {
		$options['fields'] = UtilsArray::filterWithWhiteList(isset($options['fields']) ? $options['fields'] : array(), $this->dbFields, false);
		return static::findAllLike($options);
	}
	
	protected function mirrorCrypt ($text) {
		$key = get_called_class() . ' how now brown c0w';
		srand(348756);
		$out = '';
		$keyLength = strlen($key);
		for ($i = 0, $textLength = strlen($text); $i < $textLength; $i++) {
			$j = ord(substr($key, $i % $keyLength, 1));
			while ($j--) {
				rand(0, 255);
			}
			$mask = rand(0, 255);
			$out .= chr(ord(substr($text, $i, 1)) ^ $mask);
		}
		srand();
		return $out;
	}
	
	protected function load(){
		if (!empty($this->symmetricallyEncryptedFields)) {
			foreach ($this->symmetricallyEncryptedFields as $field) {
				$this->$field = $this->mirrorCrypt($this->$field);
			}
		}
	}
	
	public function adminView($fPrompt = true){
		if (!$this->valid || !Visitor::AV()) return '';
		$a = HtmlE::n('a', '', 'edit');
		if ($fPrompt) {
			$a->href = 'javascript:void(0)';
			$a->onclick("App.prompt('{$this->c}/Form/{$this->id}')");
		} else {
			$a->href = FilterRoutes::buildUrl(array($this->baseName, 'Form', $this->id));
		}
		$html = $this->name . '&nbsp;' . $a . '&nbsp;' . $this->deleteButton();
		return $html;
	}
	
	public function setPermissions(){
		$this->PermissionsSet = true;
	}
	
	public function canEdit(){
		if (!$this->PermissionsSet) $this->setPermissions();
		return $this->canEdit;
	}
	
	public function canSee(){
		if (!$this->PermissionsSet) $this->setPermissions();
		return $this->canSee;
	}
	
	public function attachRowNames(Html $p, Html $wrapper){
		foreach ($this->RowAttributes as $Name) {
			$O = clone $wrapper;
			$O->apT($p)->a($Name);
		}
		return $p;
	}
	
	public function attachRowValues(Html $p, Html $wrapper){
		foreach ($this->RowAttributes as $Field=>$Name) {
			$O = clone $wrapper;
			if (strpos($Field, '*') === false){
				$V = $this->$Field;
			}else{
				$m = substr($Field,1);
				$V = call_user_func(array($this,$m));
			}
			$O->apT($p)->a($V);
		}
		return $p;
	}
	
	/**
	 * Update/Create this Object
	 */
	public function update(Request $req, Response $res) {
		$p = $req->post();
		if (!$this->canEdit()) throw new ExceptionPermission('May not update this ' . $this->c . ':'. $this->id);
		$this->filterPostedUpdate($p);
		if ($this->valid) {
			$this->updateVars($p);
			$this->setUpdatedMsg();
			$res->redirectTo($this->getUpdatedLoc());
		} else {
			$this->createWithVars($p);
			$this->setCreatedMsg();
			$res->redirectTo($this->getCreatedLoc());
		}
		return true;
	}
	
	/**
	 * Save the given data
	 * if this is valid, update, else create
	 */
	public function saveData (array $data) {
		if ($this->isValid()) {
			return $this->updateVars($data);
		}
		return $this->createWithVars($data);
	}
	
	/**
	 * If creation fails, attempt to perform an update
	 */
	public function createOrUpdateWithVars($varsToVals, $performAllFollowUp = true) {
		$success = false;
		try {
			$success = $this->createWithVars($varsToVals, $performAllFollowUp);
		} catch (ExceptionPDO $e) {
			if (isset($varsToVals[$this->idCol])) {
				$this->id = $varsToVals[$this->idCol];
				$success = $this->updateVars($varsToVals, $performAllFollowUp);
			}
		}
		return $success;
	}
	
	public function truncateTable () {
		DBCFactory::wPDO()->query("TRUNCATE TABLE " . DBCFactory::quote($this->table));
	}
	
	public function createFromRawData (array $data) {
		$c = count($data);
		$f = $this->dbFields;
		if ($c === count($f) - 1) {
			array_unshift($f);
		}
		if ($c !== count($f)) {
			throw new ExceptionBase('Column count mismatch');
		}
		$varsToVals = array_combine($f, $data);
		return $this->createWithVars($varsToVals);
	}
	
	public function safeCreateWithVars (array $varsToVals, $performAllFollowUp = true) {
		$fields = $this->dbFields;
		array_shift($fields);
		$this->preFilterVars($varsToVals, true);
		$fin = UtilsArray::filterWithWhiteList($varsToVals, $fields, false);
//		vdump($fin, $fields, $varsToVals);
		foreach ($this->requiredFields as $field => $msg) {
			if (empty($fin[$field])) {
				throw new ExceptionValidation($msg);
			}
		}
		return $this->createWithVars($fin, $performAllFollowUp);
	}
	
	/**
	 * Create this object in the database with the given properties
	 * 
	 * @throws ExceptionMySQL
	 * @param array $varsToVals
	 * @param bool $performAllFollowUp
	 * @return bool
	 */
	public function createWithVars($varsToVals, $performAllFollowUp = true, $dieOnFailure = false) {
		$sets = $comma = '';
		$sql = "INSERT INTO " . DBCFactory::quote($this->table) . " (";
		$values = "VALUES (";
		$vs = array();
		foreach ($varsToVals as $var => $val) {
			$sql .= $comma . DBCFactory::quote($var);
			$values .= "{$comma}?";
			if (in_array($var, $this->symmetricallyEncryptedFields)) {
				$val = $this->mirrorCrypt($val);
			}
			$vs[] = $val;
			$comma = ', ';
		}
		$sql .= ') ' . $values . ')';
		$stmt = DBCFactory::wPDO()->prepare($sql);
		$r = $stmt->execute($vs);
		if (!$r) {
			if ($dieOnFailure) {
				vdump(DBCFactory::wPDO()->errorInfo(), $stmt, $sql, $vs);
			}
			throw new ExceptionPDO($stmt, $sql . ', Class: ' . $this->c . ' ' . json_encode(array('dberror' => DBCFactory::wPDO()->errorInfo(), 'args' => $vs)));
		}
		if ($performAllFollowUp) {
			$this->id = DBCFactory::wPDO()->lastInsertId();
			$this->valid = $this->loadFromTable();
			$this->load();
			$this->createFollowUp();
		}
		return true;
	}
	
	/**
	 * Get the descriptive attributes of this Object, optionally preceded by the names of the attributes
	 * @param bool $header Include the names of the attribtues?
	 * @param string $pTag The HTML tag to wrap the row in
	 * @param string $nTag The HTML tag to wrap the attribute values in
	 * @param string $hTag the HTML tag to wrap the attribute names in
	 * @return HtmlC The requested row in an HtmlC wrapper.
	 */
	public function getRow($header=false, $pTag='tr', $nTag=NULL, $hTag='th'){
		$C = new HtmlC;
		if ($header) {
			$this->attachRowNames(HtmlE::n($pTag)->apT($C),HtmlE::n($hTag));
		}
		if (is_null($nTag)) $nTag = HtmlE::n($pTag)->getNaturalChildTag();
		$this->attachRowValues(HtmlE::n($pTag)->apT($C),HtmlE::n($nTag));
		return $C;
	}
	
	/**
	 * Get the given `view` URL of this class [e.g. /Class/_View_/id]
	 * @param string $v The Requested View [NOT prefixed with `view`]
	 * @return string The URL
	 */
	public function getUrl($v = 'review'){
		return $this->getViewUrl($v);
	}
	
	/**
	 * Get the given `view` URL of this class [e.g. /Class/_View_/id]
	 * @param string $v The Requested View [NOT prefixed with `view`]
	 * @return string The URL
	 */
	public function getViewUrl($v){
		return FilterRoutes::buildUrl(array($this->baseName, $v, $this->id));
	}
	
	/**
	 * Get the given word wrapped in an HtmlA link
	 * to goes to the given `view` URL of this class [e.g. /Class/_View_/id]
	 * @param string $v The Requested View [NOT prefixed with `view`]
	 * @param string $w The word to wrap in the link
	 * @return HtmlA The link
	 */
	public function getViewLink($v, $w = 'link'){
		return HtmlE::n('a', $this->getViewUrl($v), $w);
	}
	
	/**
	 * Get an HtmlA link that goes to this Object's `getUrl` location
	 * @param string $w The word to wrap in the link
	 * @return HtmlA The Link
	 */
	public function getLink($w = 'review'){
		return HtmlE::n('a', $this->getUrl(), $w);
	}
	
	/**
	 * Get this Object's name wrapped in an HtmlA link
	 * that goes to this Object's `getUrl` location
	 * @return HtmlA The Link
	 */
	public function getLinkedName(){
		return $this->getLink($this->getName());
	}

	/**
	 * Get an edit link for this Object
	 * If not $this->canEdit() returns empty anchor tag
	 * @param String $w String to put in the anchor
	 * @param String $m Method to call (Edit by default)
	 * @return HtmlA
	 */
	public function getEditLink($w = 'edit', $m = 'Form'){
		if (!$this->canEdit()) return Html::n('a');
		return Html::n('a', 'javascript:void(0)', $w)->onclick("App.prompt('{$this->c}/{$m}/{$this->id}')");
	}

	/**
	 * @return HtmlA
	 */
	public function getHardEditLink($w = 'edit', $m = 'Form'){
		if (!$this->canEdit()) {
			return HtmlE::N('a');
		}
		return Html::n('a', FilterRoutes::buildUrl(array($this->baseName, $m, $this->id)), $w);
	}

	/**
	 * Get's an HTML Input with the name specified, and this class's value for that attribute
	 * @param string $property Property to get Element for
	 * @param string|array $formatter Function/Class-Method to call to format the property
	 * @return Html
	 */
	public function formInput($property = 'name', $formatter = NULL) {
		$prop = $this->$property;
		if (!is_null($formatter)){
			$prop = call_user_func($formatter, $prop);
		}
		return Html::n('input', 't:text', $prop)->name($property);
	}
	
	protected function loadFromTable() {
		$sql="SELECT * " .
			"FROM " . DBCFactory::quote($this->table) . " " .
			"WHERE " . DBCFactory::quote($this->idCol) . " = ?";
		$stmt = DBCFactory::rPDO()->prepare($sql);
		$data = false;
		if ($stmt->execute(array($this->id))) $data = $stmt->fetch(PDO::FETCH_ASSOC);
		static::setCache($this->id, $data);
		return $this->loadFromCache();
	}
	
	protected static function setCache($id, $data) {
		static::$AllData[$id] = $data;
	}
	
	protected static function updateCacheValue($id, $var, $val) {
		if (!isset(static::$AllData[$id]) || !is_array(static::$AllData[$id])) static::$AllData[$id] = array();
		return static::$AllData[$id][$var] = $val;
	}
	
	protected static function updateCacheValues($id, array $varsToVals) {
		foreach ($varsToVals as $var => $val) {
			static::updateCacheValue($id, $var, $val);
		}
		return true;
	}
	
	protected function loadFromCache() {
		if (!isset(static::$AllData[$this->id]) || !is_array(static::$AllData[$this->id])) return false;
		foreach (static::$AllData[$this->id] as $var => $val) {
			$this->$var = $val;
		}
		return true;
	}
	
	/**
	 * Update the given property on this object, and in the database
	 * 
	 * @throws ExceptionMySQL
	 * @param string $var
	 * @param mixed $val
	 * @return bool
	 */
	public function updateVar($var, $val = NULL) {
		$sql="UPDATE " . DBCFactory::quote($this->table) . " " .
			"SET " . DBCFactory::quote($var) . " = ? " .
			"WHERE " . DBCFactory::quote($this->idCol) . " = ?";
		$stmt = DBCFactory::wPDO()->prepare($sql);
		$r = $stmt->execute(array($val, $this->id));
		if (!$r) throw new ExceptionPDO($stmt, $sql . ', Class: ' . $this->c);
		$this->$var = $val;
		static::updateCacheValue($this->id, $var, $val);
		$this->updateFollowUp(array($var));
		return true;
	}
	
	/**
	 * @throws ExceptionValidation
	 * @param Array $vars passed by reference
	 * @param Boolean $creating are these vars being used to create a new entry?
	 * @return void
	 */
	protected function preFilterVars (array &$vars, $creating) {
		
	}
	
	/**
	 * @throws ExceptionValidation
	 * @param Array $vars Key=>Val var=>val used to update this entry
	 * @return Boolean
	 */
	public function safeUpdateVars (array $vars) {
		$fs = $this->dbFields;
		array_shift($fs);
		if (!empty($this->dontUpdate)) {
			$fin = array();
			foreach ($fs as $f) {
				if (in_array($f, $this->dontUpdate)) continue;
				$fin[] = $f;
			}
		} else {
			$fin = $fs;
		}
		$this->preFilterVars($vars, false);
		$fin = UtilsArray::filterWithWhiteList($vars, $fin);
		foreach ($this->requiredFields as $field => $msg) {
			if (empty($fin[$field])) {
				throw new ExceptionValidation($msg);
			}
		}
		return $this->updateVars($fin);
	}
	
	/**
	 * Update the given properties on this object, and in the database
	 * 
	 * @throws ExceptionPDO
	 * @param array $varsToVals
	 * @return bool
	 */
	public function updateVars(array $varsToVals)
	{
		$sets = $comma = '';
		$vs = array();
		foreach ($varsToVals as $var => $val) {
			if ($var == $this->idCol) continue;
			$sets .= $comma . DBCFactory::quote($var) . " = ?";
			$comma = ', ';
			if (in_array($var, $this->symmetricallyEncryptedFields)) {
				$val = $this->mirrorCrypt($val);
			}
			$vs[] = $val;
		}
		$vs[] = $this->id;
		$sql="UPDATE " . DBCFactory::quote($this->table) . " " .
			"SET {$sets} ".
			"WHERE " . DBCFactory::quote($this->idCol) . " = ?";
		$stmt = DBCFactory::wPDO()->prepare($sql);
		$r = $stmt->execute($vs);
		if (!$r) {
			if (DEBUG) {
				var_dump($r, $stmt->errorInfo(), $sql, $vs);
				if (!RUNNING_AS_CLI) {
					exit;
				}
			}
			throw new ExceptionPDO($stmt, $sql . ', Class: ' . $this->c);
		}
		static::updateCacheValues($this->id, $varsToVals);
		foreach ($varsToVals as $var => $val) {
			$this->$var = $val;
		}
		$this->updateFollowUp(array_keys($varsToVals));
		return true;
	}
	
	/**
	 * Perform any class specific update follow up
	 * @param Array $updatedProperties The Properties that were updated
	 * @return void
	 */
	protected function updateFollowUp(array $updatedProperties) {
		
		return;
	}
	
	/**
	 * Build an array of the default posted values for an update/create
	 * 
	 * @return array
	 */
	public function buildDefaults() {
		$A = array();
		foreach ($this->Editables as $f) {
			$A[$f] = $this->$f;
		}
		return $A;
	}
	
	/**
	 * Filter out any superflous posted information
	 * The array passed by reference will only
	 * contain indexes consistent with $this::$Editables,
	 * and they will be modified by $this::modPostedUpate
	 * 
	 * @param Array $post
	 * @return Array
	 */
	public function filterPostedUpdate(array &$post) {
		$post = array_merge($this->buildDefaults(), $post);
		$this->modPostedUpdate($post);
		$p = $post;
		foreach ($p as $k => $v) {
			if (!in_array($k, $this->Editables)) unset($post[$k]);
		}
		return;
	}
	
	/**
	 * Perform any class specific modifications to the POSTed Update/Create
	 * Before any non-editables are filtered out
	 * 
	 * @param array $post
	 * @return void
	 */
	public function modPostedUpdate(array &$post) {
		
		return;
	}
	
	/**
	 * Set the Updated Message
	 * @return void
	 */
	public function setUpdatedMsg() {
		$_SESSION['msg'] = $this->whatIAm . ' ' . $this->UpdatedWord;
	}
	
	/**
	 * Set the Created Message
	 * @return void
	 */
	public function setCreatedMsg() {
		$_SESSION['msg'] = $this->whatIAm . ' ' . $this->CreatedWord;
	}
	
	/**
	 * Set the Updated Final Destination
	 * @return void
	 */
	public function getUpdatedLoc() {
		return FilterRoutes::buildUrl(array($this->baseName, 'Review', $this->id));
	}
	
	/**
	 * Set the Created Final Destination
	 * @return void
	 */
	public function getCreatedLoc() {
		return FilterRoutes::buildUrl(array($this->baseName, 'Review', $this->id));
	}
	
	public function delete ($force = false) {
		if (!$this->valid && !$force) return false;
		return $this->deleteMyself();
	}
	
	protected function deleteMyself(){
		$sql = "DELETE FROM " . DBCFactory::quote($this->table) . " WHERE " . DBCFactory::quote($this->idCol) . " = ?";// LIMIT 1
		$stmt = DBCFactory::wPDO()->prepare($sql);
		$stmt->bindParam(1, $this->id, PDO::PARAM_INT);
		return $stmt->execute();
	}
	
	public function isValid(){
		return (bool)$this->valid;
	}
	
	public function getID(){
		return $this->id;
	}
	
	public static function getIDCol(){
		return static::$IdCol;
	}
	
	public static function getTable(){
		return static::$Table;
	}
	
	function whatAmI(){
		return $this->whatIAm;
	}
	
	function getName(){
		return trim(ucwords($this->name));
	}
	
	function __toString(){
		return strval($this->getName());
	}
	
	function __call($function, $args){
		return false;
	}
	
	public static function getMaxFieldValue($field)
	{
		$sql = "SELECT MAX(" . DBCFactory::quote($field) . ") FROM " . DBCFactory::quote(static::$Table);
		$stmt = DBCFactory::rPDO()->prepare($sql);
		if (!$stmt) throw new ExceptionBase(DBCFactory::rPDO()->errorInfo(), 1);
		$r = $stmt->execute($params);
		if (!$r) throw new ExceptionPDO($stmt, 'attempted field: ' . $field);
		list($value) = $stmt->fetch(PDO::FETCH_NUM);
		return $value;
	}
	
	protected static function buildQueryFromOptions (array $options, $Class = NULL, $type = 'SELECT') {
		$c = get_called_class();
		if (is_null($Class)) $Class = $c;
		switch ($type) {
			case "DELETE":
				$sql = "DELETE";
			break;
			default:
				$sql = "SELECT " . DBCFactory::quote($c::$IdCol);
		}
		$sql .= " FROM " . DBCFactory::quote($c::$Table);
		$args = array();
		if (!empty($options['fields']) || !empty($options['conditions'])) {
			$and = '';
			$sql .= " WHERE ";
			if (!empty($options['fields'])) {
				if (isset($options['LIKE'])) {
					$args = array_values($options['fields']);
					foreach ($options['fields'] as $f => $v) {
						$sql .= $and . DBCFactory::quote($f) . " LIKE ?";
						$and = ' AND ';
					}
					foreach ($args as &$arg) {
						$arg = '%' . $arg . '%';
					}
				} else {
					foreach ($options['fields'] as $f => $v) {
						if (is_null($v)) {
							$sql .= $and . DBCFactory::quote($f) . " IS NULL";
						} else {
							$sql .= $and . DBCFactory::quote($f) . " = ?";
							$args[] = $v;
						}
						$and = ' AND ';
					}
				}
			}
			if (!empty($options['conditions'])) {
				$sql .= $options['conditions']['sql'];
				foreach ($options['conditions']['args'] as $v) { $args[] = $v;}
			}
		}
		if (isset($options['sort'])) {
			if (is_array($options['sort'])) {
				$sql .= " ORDER BY " . implode(', ', $options['sort']);
			} else {
				$sql .= " ORDER BY {$options['sort']}";
			}
		}
		if (isset($options['limit'])) {
			$sql .= " LIMIT " . $options['limit'];
		}
		return array($sql, $args, $Class);
	}
	
	/**
	 * @return Model
	 */
	public static function findOneBelongingTo () {
		$args = func_get_args();
		$fields = array();
		foreach ($args as $Model) {
			if (!is_a($Model, 'Model')) throw new ExceptionBase('Invalid class passed in, Model required');
			$fields[$Model->idCol] = $Model->id;
		}
		return static::findOne(array(
			'fields' => $fields,
		));
	}
	
	/**
	 * @return Model
	 */
	public static function findOwner (Model $M, $additionalOptions = array()) {
		$idc = static::$IdCol;
		return static::findOne(array_merge($additionalOptions, array(
			'fields' => array(
				$idc => $M->$idc,
			),
		)));
	}
	
	public static function findAllBelongingTo (Model $Model, $additionalOptions = array()) {
		if (!is_null(static::$sortField)) {
			if (is_array(static::$sortField)) {
				$sort = implode(' ASC, ', static::$sortField) . ' ASC';
			} else {
				$sort = static::$sortField . ' ASC';
			}
			$additionalOptions = array_merge(array(
				'sort' => $sort,
			), $additionalOptions);
		}
		return static::findAll(array_merge($additionalOptions, array(
			'fields' => array(
				$Model->idCol => $Model->id,
			),
		)));
	}
	
	/**
	 * @return Model
	 */
	public static function findOne (array $options = array(), $Class = NULL) {
		list($sql, $args, $Class) = static::buildQueryFromOptions($options, $Class);
		$O = UtilsPDO::fetchIdIntoInstance($sql, $args, $Class);
		$O->foundWith = $options;
		return $O;
	}
	
	public static function findAll (array $options = array(), $Class = NULL) {
		if (!is_null(static::$sortField)) {
			if (is_array(static::$sortField)) {
				$sort = implode(' ASC, ', static::$sortField) . ' ASC';
			} else {
				$sort = static::$sortField . ' ASC';
			}
			$options = array_merge(array(
				'sort' => $sort,
			), $options);
		}
		list($sql, $args, $Class) = static::buildQueryFromOptions($options, $Class);
		//var_dump($sql, $args);
		$Os = UtilsPDO::fetchIdsIntoInstances($sql, $args, $Class);
		foreach ($Os as $O) {
			$O->foundWith = $options;
		}
		return $Os;
	}
	
	public static function deleteAll (array $options = array()) {
		list($sql, $args) = static::buildQueryFromOptions($options, NULL, 'DELETE');
		$stmt = DBCFactory::wPDO()->prepare($sql);
		return $stmt->execute($args);
	}
	
	public static function findAllLike (array $options = array(), $Class = NULL) {
		$options['LIKE'] = true;
		list($sql, $args, $Class) = static::buildQueryFromOptions($options, $Class);
//		vdump($sql, $args);
		$Os = UtilsPDO::fetchIdsIntoInstances($sql, $args, $Class);
		foreach ($Os as $O) {
			$O->foundWith = $options;
		}
		return $Os;
	}
	
	/**
	 * Given an array of Model Ids, return an array of the corresponding models
	 * @param Array $ids Model Ids
	 * @param Boolean $useIdForKey
	 * @return Array
	 */
	public static function translateIdsIntoModels (array $ids, $useIdForKey = true)
	{
		$Models = array();
		$class = get_called_class();
		$i = 0;
		foreach ($ids as $k => $id) {
			$key = $useIdForKey ? $id : $k;
			$Models[$key] = new $class($id);
		}
		return $Models;
	}
	
	/**
	 * Get data from each Model in the given array
	 * @param Array $Models
	 * @return Array Data
	 */
	public static function extractDataFromArray (array $Models)
	{
		return UtilsArray::callOnAll($Models, 'getData');
	}
	
	public static function getAllData (array $options = array())
	{
		return static::extractDataFromArray(static::findAll($options));
	}
	
}

