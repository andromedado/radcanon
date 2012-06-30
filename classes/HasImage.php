<?php
defined('PaZsCA8p') or exit;

abstract class HasImage extends HasFiles {
	protected $resizeDir;
	
	protected function loadFiles(){
		parent::loadFiles();
		if($this->valid){
			$this->resizeDir=$this->fullUpDir.'resized/';
			if(!is_dir($this->resizeDir) && !mkdir($this->resizeDir)){return false;}
		}
		return true;
	}
	
	protected function loadFile(){
		parent::loadFile();
		if($this->valid){
			$this->resizeDir=$this->fullUpDir.'resized/';
			if(!is_dir($this->resizeDir)){
				if(CRON){return false;}
				if(!mkdir($this->resizeDir)){return false;}
			}
		}
		return true;
	}
	
	protected function getImagesAtSize($pwidth=100){
		$width=(int)$pwidth;
		if($width<1){die('Help! :'.$pwidth);}
		$images=array();
		foreach($this->Files as $index=>$f){
			$fname=$this->FileNs[$index];
			$ext=array_pop(explode('.',$fname));
			$basename=preg_replace('/\.'.$ext.'$/','',$fname);
			$resizedFile=$this->resizeDir.$basename.'_'.$width.'.'.$ext;
			if(!file_exists($resizedFile) || filectime($resizedFile)<filectime($f)){
				self::resizeImage($f,$resizedFile,$width) or die('Unable to resize');
			}
			$src=str_replace($this->serverPrefix,'',$resizedFile);
			$images[$index]=$src;
		}
		return $images;
	}
	
	protected function getImageAtSize($pwidth = 100){
		if($this->NumFiles<1){return '';}
		$width=(int)$pwidth;
		if($width<1){die('Help! :'.$pwidth);}
		$fname=$this->FileN;
		$ext=array_pop(explode('.',$fname));
		$basename=preg_replace('/\.'.$ext.'$/','',$fname);
		$resizedFile=$this->resizeDir.$basename.$width.'.'.$ext;
		if(!file_exists($resizedFile)){
			$r = self::resizeImage($this->File, $resizedFile, $width);
			if (!$r) throw new ExceptionFile('Unable to Resize: ' . $this->File . ' -> ' . $resizedFile . ' at ' . $width . ' failed');
		}
		$src=str_replace($this->serverPrefix,'',$resizedFile);
		return $src;
	}
	
}

?>