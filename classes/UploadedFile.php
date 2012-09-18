<?php

class UploadedFile
{
	protected $name;
	protected $type;
	protected $tmp_name;
	protected $error = null;
	protected $size;
	
	protected $key;
	protected $index;
	protected $valid = false;
	protected $errorMessage;
	
	protected $readOnly = array(
		'name', 'type', 'tmp_name',
		'error', 'size', 'errorMessage',
		'valid',
	);
	
	protected $uFields = array(
		'name', 'type', 'tmp_name', 'error', 'size',
	);
	protected $errors = array(
		1 => 'The uploaded file exceeds the upload_max_filesize',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Server cannot find a temporary folder',
		7 => 'Failed to write file to disk',
		8 => 'A PHP extension stopped the file upload',
	);
	
	public function __construct($key, $index = null) {
		$this->key = $key;
		$this->index = $index;
		$this->load();
	}
	
	public function load () {
		if (isset($_FILES[$this->key])) {
			if (is_null($this->index)) {
				if (is_array($_FILES[$this->key]['name'])) {
					throw new ExceptionBase('Indexed Upload, pass index to UploadedFile constructor');
				}
				foreach ($this->uFields as $f) {
					$this->$f = isset($_FILES[$this->key][$f]) ? $_FILES[$this->key][$f] : null;
				}
			} elseif (isset($_FILES[$this->key]['name'][$this->index])) {
				foreach ($this->uFields as $f) {
					$this->$f = isset($_FILES[$this->key][$this->index][$f]) ? $_FILES[$this->key][$this->index][$f] : null;
				}
			}
		}
		$this->setValidity();
	}

	protected function setValidity() {
		if (is_null($this->error)) {
			$this->valid = false;
			$this->errorMessage = 'Invalid Upload';
			return;
		}
		if ($this->size < 1) {
			$this->valid = false;
			$this->errorMessage = 'Empty File Uploaded';
			return;
		}
		if ($this->error < 1) {
			$this->valid = true;
			return;
		}
		$this->valid = false;
		if (isset($this->errors[$this->error])) {
			$this->errorMessage = $this->errors[$this->error];
		} else {
			$this->errorMessage = 'Unknown Error: ' . $this->error;
		}
		return;
	}
	
	public function __get($var) {
		if (in_array($var, $this->readOnly)) return $this->$var;
		return NULL;
	}
	
}
