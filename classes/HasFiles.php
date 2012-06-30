<?php
defined('PaZsCA8p') or exit;

abstract class HasFiles extends HasFile {
	
	protected $Files=array();
	protected $FileNs=array();
	protected $FileUrls=array();
	
	protected function loadFiles(){
		if(!is_dir($this->fullUpDir) && !mkdir($this->fullUpDir)){return 'Unable to access '.$this->fullUpDir;}
		$this->FileUrl=$this->File='';
		$files=glob($this->fullUpDir.'*.*');
		$this->NumFiles=0;
		if(is_array($files)){
			foreach($files as $filen){
				$i=filectime($filen);
				while(isset($this->Files[$i])){$i++;}
				$this->NumFiles++;
				$this->Files[$i]=$filen;
				$this->FileNs[$i]=str_replace($this->fullUpDir,'',$filen);
				$this->FileUrls[$i]=str_replace($this->serverPrefix,'',$filen);
			}
			krsort($this->Files);
		}
		return true;
	}
	
	protected function uploadForm($formWrap=false,$name='files'){
		if($formWrap){
			$html=<<<EOT
<form enctype="multipart/form-data" action="" method="post">
	<input type="hidden" name="inst" value="{$this->c}:handleUpload:{$this->id}" />
	
EOT;
		}else{$html='';}
		$html.='<input type="file" name="'.$name.'[]" onchange="moreF(event)" />';
		if(!empty($this->Files)){
			$html.='<table border="0" cellspacing="0" cellpadding="0">';
			foreach($this->Files as $k=>$F){
				$html.='<tr>
				<td><a target="_blank" href="'.$this->FileUrls[$k].'">'.$this->FileNs[$k].'</a>&nbsp;</td>
				<td><input type="checkbox" name="deleteF[]" value="'.$k.'" /></td>
				<td><i>Delete?</i></td>
				</tr>';
			}
			$html.='</table>';
		}
		if($formWrap){
			$html.=<<<EOT
	<div align="center">
		<input type="submit" value="Upload" />
	</div>
</form>
EOT;
		}
		return $html;
	}
	
	protected function handleDelete($array){
		$v=true;
		$msg=$br='';
		foreach($array as $k){
			if(isset($this->Files[$k])){
				if(!unlink($this->Files[$k])){
					$v=false;
					$msg.=$br.$this->FileNs[$k].' could not be deleted';
					$br='<br />';
				}
			}else{
				$v=false;
				$msg.=$br.'File to be deleted not found';
				$br.='<br />';
			}
		}
		if(!$v){return $msg;}
		return true;
	}
	
	public function handleUpload($fname='files'){
		if(!$this->valid){return 'Unable To Comply';}
		$is=array_keys($_FILES[$fname]['name']);
		$v=true;
		$msg=$br='';
		foreach($is as $i){
			if($_FILES[$fname]['size'][$i]==0){continue;}
			$b=$this->handleIndexedUpload($fname,$i);
			if($b!==true){
				$v=false;
				$msg.=$br.$b;
				$br='<br />';
			}
		}
		if(!$v){return $msg;}
		$_SESSION['msg']='File(s) Uploaded Successfully';
		return true;
	}
	
	protected function handleIndexedUpload($fname,$i){
		if($_FILES[$fname]['size'][$i]<=0){return 'No File Found';}
		if($_FILES[$fname]['error'][$i]>0){return self::handleUploadError($fname,$i);}
		$dest=$this->fullUpDir.self::urlSafe($_FILES[$fname]['name'][$i],true);
		if(isset($this->fTypeRestriction) && !preg_match($this->fTypeRestriction,$dest)){return 'Invalid File Type: '.$_FILES[$fname]['name'][$i];}
		if(file_exists($dest) && !unlink($dest)){return 'Unable to remove pre-existing file';}
		if(!copy($_FILES[$fname]['tmp_name'][$i],$dest)){return 'There was a problem copying to '.$dest;}
		return true;
	}
	
}

?>