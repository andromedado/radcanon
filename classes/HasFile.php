<?php
defined('PaZsCA8p') or exit;

abstract class HasFile extends ImageEdit {
	
	protected $serverPrefix = SERVER_PREFIX;//Without Trailing Slash
	protected $upDirRoot = UPDIR_ROOT;//With Trailing Slash
	protected $fTypeRestriction;
	protected $fullUpDir='';
	protected $File='';
	protected $FileN='';
	protected $FileUrl='';
	protected $NumFiles=0;
	protected $inputName='file';
	
	protected function loadFile(){
		if(!is_dir($this->fullUpDir)){
			if(CRON){return 'Running as Cron';}
			if(!mkdir($this->fullUpDir)){return 'Unable to access '.$this->fullUpDir;}
		}
		$this->FileUrl=$this->File='';
		$files=glob($this->fullUpDir.'*.*');
		$Files=array();
		$this->NumFiles=0;
		if(is_array($files)){
			foreach($files as $filen){
				$i=filectime($filen);
				while(isset($Files[$i])){$i++;}
				$Files[$i]=$filen;
				$this->NumFiles++;
			}
		}
		if(!empty($Files)){
			krsort($Files);
			$this->File=array_shift($Files);
			$this->FileUrl=str_replace($this->serverPrefix,'',$this->File);
			$this->FileN=str_replace($this->fullUpDir,'',$this->File);
		}else{
			$this->FileUrl=$this->Files='';
		}
		return true;
	}
	
	protected function uploadForm($formWrap=false,$append='',$head=''){
		if($formWrap){
			$html=<<<EOT
<div class="c_form">
{$head}
<form enctype="multipart/form-data" action="" method="post">
	<input type="hidden" name="inst" value="{$this->c}:handleUpload:{$this->id}" />
EOT;
		}else{$html='';}
		$html.='<input type="file" name="'.$this->inputName.'" />'.$append;
		if($formWrap){
			$html.=<<<EOT
	<div align="center">
		<input type="submit" value="Upload" />
	</div>
</form>
</div>
EOT;
		}
		return $html;
	}
	
	public function handleUpload(){
		if(!$this->valid){
			return 'Unable To Comply';
		}
		if (empty($_FILES[$this->inputName]['name'])) return true;
		if($_FILES[$this->inputName]['size']<=0){return 'No File Found';}
		if($_FILES[$this->inputName]['error']>0){return self::handleUploadError($this->inputName);}
		$dest = $this->fullUpDir . UtilsString::urlSafe($_FILES[$this->inputName]['name'], true);
		if(isset($this->fTypeRestriction) && !preg_match($this->fTypeRestriction,$dest)){return 'Invalid File Type';}
		if(file_exists($dest) && !unlink($dest)){return 'Unable to remove pre-existing file';}
		if(!copy($_FILES[$this->inputName]['tmp_name'],$dest)){return 'There was a problem copying to '.$dest;}
		if($this->File!=''){unlink($this->File);}
		$_SESSION['msg']='File Uploaded Successfully';
		return true;
	}
	
	protected static function handleUploadError($fname='',$i=NULL){
		if(!isset($_FILES[$fname])){return 'File Not Found';}
		$E=$i==NULL?$_FILES[$fname]['error']:$_FILES[$fname]['error'][$i];
		if($E==0){return 'Upload Error Handler Incorrectly Called';}
		$errors=array(1=>'The uploaded file exceeds the upload_max_filesize',
					  2=>'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
					  3=>'The uploaded file was only partially uploaded',
					  4=>'No file was uploaded',
					  6=>'Server cannot find a temporary folder',
					  7=>'Failed to write file to disk',
					  8=>'A PHP extension stopped the file upload');
		if(isset($errors[$E])){
			return $errors[$E];
		}
		return 'Unkown File Upload Error: '.$E;
	}

}

?>