<?php

class FileMultiple extends FileOfModel
{
	
	public function getHrefs ()
	{
		$hrefs = array();
		$Paths = $this->getFilePaths();
		foreach ($Paths as $Path) {
			$hrefs[] = APP_SUB_DIR . str_replace(SERVER_PREFIX, '', $Path);
		}
		return $hrefs;
	}
	
	public function acceptUploadByInfo(array $info)
	{
		$this->checkForUploadErrorsByInfo($info);
		$fn = UtilsString::urlSafe($info['name'], true);
		$dir = $this->getBaseDir();
		do {
			$dest = $dir . $fn;
			$fn = rand(1, 9) . '-' . $fn;
		} while (file_exists($dest));
		if (!move_uploaded_file($info['tmp_name'], $dest)) {
			throw new ExceptionBase('Couldnt move from ' . $info['tmp_name'] . ' to ' . $dest);
		}
	}
	
}

