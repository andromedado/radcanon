<?php

class FileSingle extends FileOfModel {
	
	public function hasFile () {
		$fs = $this->getFilesInBaseDir();
		return !empty($fs);
	}
	
	public function getFilename () {
		if (!$this->hasFile()) return NULL;
		return basename(array_shift($this->getFilesInBaseDir()));
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
	
}

?>