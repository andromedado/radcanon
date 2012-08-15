<?php

abstract class FileOfModel {
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
	
	public function __construct (Model $M, $name = '', $validationPreg = NULL) {
		$this->Model = $M;
		$this->name = $name;
		if (!is_null($validationPreg)) $this->validationPreg = $validationPreg;
	}
	
	public function hasFile ($name) {
		$fs = $this->getFilesInBaseDir();
		$has = false;
		foreach ($fs as $file) {
			$has = $has || basename($file) === $name;
		}
		return $has;
	}
	
	public function checkForUploadErrors () {
		if (!isset($_FILES[$this->name])) {
			throw new ExceptionValidation('No file uploaded');
		}
		if (is_array($_FILES[$this->name]['error'])) {
			foreach ($_FILES[$this->name]['error'] as $k => $error) {
				$this->checkError($error);
				if ($_FILES[$this->name]['size'][$k] < 1) {
					throw new ExceptionValidation('Empty File Uploaded');
				}
				if (!is_null($this->validationPreg) && !preg_match($this->validationPreg, $_FILES[$this->name]['name'][$k])) {
					throw new ExceptionValidation('Invalid FileType');
				}
			}
		} else {
			$this->checkError($_FILES[$this->name]['error']);
			if ($_FILES[$this->name]['size'] < 1) {
				throw new ExceptionValidation('Empty File Uploaded');
			}
			if (!is_null($this->validationPreg) && !preg_match($this->validationPreg, $_FILES[$this->name]['name'])) {
				throw new ExceptionValidation('Invalid FileType');
			}
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
	
	public function getBaseDir () {
		if (is_null($this->baseDir)) {
			$k = strval(floor($this->Model->id / 1000)) . 'k';
			$this->baseDir = UPDIR_ROOT . $this->Model->baseName . DS . $k . DS . $this->Model->id . DS;
			if (!empty($this->name)) {
				$this->baseDir .= $this->name . DS;
			}
			if (!is_dir($this->baseDir) && !mkdir($this->baseDir, self::APPROPRIATE_DIR_PERMISSIONS, true)) {
				throw new ExceptionBase('Unable to make dir ' . $this->baseDir);
			}
		}
		return $this->baseDir;
	}
	
	public function getResizeDir () {
		if (is_null($this->resizeDir)) {
			$this->resizeDir = $this->getBaseDir() . 'resized' . DS;
			if (!is_dir($this->resizeDir) && !mkdir($this->resizeDir, self::APPROPRIATE_DIR_PERMISSIONS, true)) {
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
	
	public function getFilesInDir ($dir, $noCache = false) {
		if (!isset($this->cachedScan[$dir]) || $noCache) {
			$this->cachedScan[$dir] = glob($dir . '*.*');
		}
		return $this->cachedScan[$dir];
	}
	
	
}
