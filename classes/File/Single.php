<?php

class FileSingle extends FileOfModel
{
	
	public function hasFile () {
		return $this->hasAFile();
	}
	
	public function getHref ()
	{
		return APP_SUB_DIR . str_replace(SERVER_PREFIX, '', $this->getBaseDir() . $this->getFilename());
	}
	
	public function getFilePath()
	{
		$files = $this->getFilesInBaseDir();
		if (empty($files)) return '';
		return array_pop($files);
	}
	
	public function getFilename () {
		if (!$this->hasFile()) return NULL;
		return basename($this->getFilePath());
	}
	
	public function getFileExtension () {
		return array_pop(explode('.', $this->getFilename()));
	}
	
	public function acceptUpload () {
		$this->checkForUploadErrors();
		if (is_array($_FILES[$this->name]['tmp_name'])) {
			throw new ExceptionValidation('Invalid Upload Type - Only Single File Permitted');
		}
		if ($this->hasFile()) {
			$this->deleteFilesInBaseDir();
		}
		$dest = $this->getBaseDir() . UtilsString::urlSafe($_FILES[$this->name]['name'], true);
		if (!move_uploaded_file($_FILES[$this->name]['tmp_name'], $dest)) {
			throw new ExceptionBase('Couldnt move from ' . $_FILES[$this->name]['tmp_name'] . ' to ' . $dest);
		}
	}
	
	public function acceptUploadByInfo(array $info)
	{
		$this->checkForUploadErrorsByInfo($info);
		if ($this->hasFile()) {
			$this->deleteFilesInBaseDir();
		}
		$dest = $this->getBaseDir() . UtilsString::urlSafe($info['name'], true);
		if (!move_uploaded_file($info['tmp_name'], $dest)) {
			throw new ExceptionBase('Couldnt move from ' . $info['tmp_name'] . ' to ' . $dest);
		}
	}
	
}

