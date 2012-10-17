<?php

abstract class FileOfModel
{
	const APPROPRIATE_DIR_PERMISSIONS = 0755;
	public $validationPreg = NULL;
	/** @var Model $Model */
	protected $Model;
	protected $name;
	protected $baseDir = NULL;
	protected $resizeDir = NULL;
	protected $cachedScan = array();
	protected $errors = array(
		1 => 'The uploaded file exceeds the upload_max_filesize',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Server cannot find a temporary folder',
		7 => 'Failed to write file to disk',
		8 => 'A PHP extension stopped the file upload',
	);
	protected $publicErrors = array(
		1 => 'The uploaded file is too big',
		4 => 'No file was uploaded',
	);
	protected static $FileKeys = array(
		'name', 'type', 'tmp_name', 'error', 'size',
	);
	
	public function __construct (Model $M, $name = '', $validationPreg = null)
	{
		$this->Model = $M;
		$this->name = $name;
		if (!is_null($validationPreg)) $this->validationPreg = $validationPreg;
		$this->load();
	}
	
	public function load()
	{
		
	}
	
	/**
	 * Create a file of the given name, with the given content
	 * @throws ExceptionFile
	 * @param String $name **Directory Separators are stripped out
	 * @param String $content
	 * @return FileOfModel
	 */
	public function saveFile($name, $content = '')
	{
		$dest = $this->getBaseDir() . str_replace(DIRECTORY_SEPARATOR, '_', $name);
		$h = fopen($dest, 'c');
		if (!$h) throw new ExceptionFile('Unable to open ' . $dest . ' for writing');
		$b = fwrite($h, $content);
		fclose($h);
		if (!empty($content) && empty($b)) throw new ExceptionFile('Unable to write to ' . $dest);
		return $this;
	}
	
	public function getFilePaths()
	{
		return $this->getFilesInBaseDir();
	}
	
	public function getFilenames () {
		$names = array();
		$Ps = $this->getFilePaths();
		foreach ($Ps as $P) {
			$names[] = basename($P);
		}
		return $names;
	}
	
	public function hasFile ($name) {
		return in_array($name, $this->getFilenames());
	}
	
	public function deleteFile($filename)
	{
		$fPath = $this->getBaseDir() . self::sanitizeFilename($filename);
		if (!file_exists($fPath)) {
			return false;
		}
		if (!unlink($fPath)) {
			throw new ExceptionFile('Could not unlink: "' . $fPath . '"');
		}
		return true;
	}
	
	/**
	 * Is there at least one file?
	 * @return Boolean
	 */
	public function hasAFile()
	{
		$fs = $this->getFilesInBaseDir();
		return !empty($fs);
	}
	
	public function checkForUploadErrorsByInfo(array $info)
	{
		$this->checkError($info['error']);
		if ($info['size'] < 1) {
			throw new ExceptionValidation('Empty File Uploaded');
		}
		if (!is_null($this->validationPreg) && !preg_match($this->validationPreg, $info['name'])) {
			throw new ExceptionValidation('Invalid FileType');
		}
	}
	
	/**
	 * @throws ExceptionValidation
	 * @return void
	 */
	public function checkForUploadErrors () {
		if (!isset($_FILES[$this->name])) {
			throw new ExceptionValidation('No file uploaded');
		}
		if (is_array($_FILES[$this->name]['error'])) {
			$infos = UtilsArray::amalgamateArrays($_FILES[$this->name], static::$FileKeys);
			foreach ($infos as $info) {
				$this->checkForUploadErrorsByInfo($info);
			}
		} else {
			$this->checkForUploadErrorsByInfo($_FILES[$this->name]);
		}
	}
	
	protected function checkError ($error) {
		if ($error !== UPLOAD_ERR_OK) {
			if (array_key_exists($error, $this->publicErrors)) {
				throw new ExceptionValidation($this->publicErrors[$error]);
			} elseif (isset($this->errors[$error])) {
				throw new ExceptionBase($this->errors[$error]);
			} else {
				throw new ExceptionBase('Unknown Upload Error: ' . $error);
			}
		}
	}
	
	protected function determineBaseDir()
	{
		$k = strval(floor($this->Model->id / 1000)) . 'k';
		$baseDir = UPDIR_ROOT . $this->Model->baseName . DS . $k . DS . $this->Model->id . DS;
		if (!empty($this->name)) {
			$baseDir .= $this->name . DS;
		}
		return $baseDir;
	}
	
	public function getBaseDir () {
		if (is_null($this->baseDir)) {
			$this->baseDir = $this->determineBaseDir();
			if (!is_dir($this->baseDir) && !mkdir($this->baseDir, self::APPROPRIATE_DIR_PERMISSIONS, true)) {
				if (RUNNING_AS_CLI) return false;
				throw new ExceptionBase('Unable to make dir ' . $this->baseDir);
			}
		}
		return $this->baseDir;
	}
	
	public function getResizeDir () {
		if (is_null($this->resizeDir)) {
			$this->resizeDir = $this->getBaseDir() . 'resized' . DS;
			if (!is_dir($this->resizeDir) && !mkdir($this->resizeDir, self::APPROPRIATE_DIR_PERMISSIONS, true)) {
				if (RUNNING_AS_CLI) return false;
				throw new ExceptionBase('Unable to make dir '. $this->resizeDir);
			}
		}
		return $this->resizeDir;
	}
	
	public function getFilesInBaseDir () {
		return $this->getFilesInDir($this->getBaseDir());
	}
	
	public function deleteFilesInBaseDir () {
		$fs = $this->getFilesInBaseDir();
		foreach ($fs as $f) {
			$bnbits = explode('.', basename($f));
			array_pop($bnbits);
			$resizedVersions = glob($this->getResizeDir() . implode('.', $bnbits) . '*');
			foreach ($resizedVersions as $rv) {
				unlink($rv);
			}
			unlink($f);
		}
	}
	
	/**
	 * For the given dir, removed any cached scan
	 * @param String $dir
	 * @return FileOfModel
	 */
	protected function clearCacheForDir($dir)
	{
		if (isset($this->cachedScan[$dir])) {
			unset($this->cachedScan[$dir]);
		}
		return $this;
	}
	
	/**
	 * Get an array of the files in the given dir
	 * @param String $dir
	 * @param Boolean $noCache
	 * @return Array
	 */
	public function getFilesInDir ($dir, $noCache = false) {
		if (!isset($this->cachedScan[$dir]) || $noCache) {
			$this->cachedScan[$dir] = glob($dir . '*.*');
			if (!is_array($this->cachedScan[$dir])) {
				$this->cachedScan[$dir] = array();
			}
		}
		return $this->cachedScan[$dir];
	}
	
	public static function getFileKeys()
	{
		return static::$FileKeys;
	}
	
	public static function sanitizeFilename($filename)
	{
		return str_replace(array("\0", DIRECTORY_SEPARATOR), array('', ''), $filename);
	}

}

