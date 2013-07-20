<?php

abstract class UtilsImage
{
	
	public static function getMimeType ($filename) {
		$ext = strtolower(preg_replace('/[^A-Z\d]+/i', '', array_pop(explode('.', $filename))));
		$type = 'image/';
		switch ($ext) {
			case 'jpg':
				$type .= 'jpeg';
			break;
			default:
				$type .= $ext;
		}
		return $type;
	}
	
	public static function resizeImage ($b_file, $store, $newWidth) {
		if (is_dir($b_file) || file_exists($store) && !unlink($store)) {
            return true;
        }
		$img_parts = pathinfo($b_file);
		$ext = strtolower($img_parts['extension']);
		$fileNoExt=$img_parts['filename'];
		$filename = $b_file;
		$storename = $store; // gets ../site/folder_name/sub-folder/file.ext
		$file=basename($store);
		$storeDir=dirname($store);

		if (!is_dir($storeDir) && !mkdir($storeDir, 0755, true)) {
			if (RUNNING_AS_CLI) return false;
			throw new ExceptionBase('Unable to make dir ' . $storeDir);
		}		
		if ($ext=="jpg" || $ext=="jpeg"){ $src_img=@imagecreatefromjpeg($filename); }
		elseif ($ext=="gif"){ $src_img=@imagecreatefromgif($filename); }
		elseif ($ext=="png"){ 
			$src_img=@imagecreatefrompng($filename); 
			@imagealphablending($src_img, true); // setting alpha blending on
			@imagesavealpha($src_img, true);
		}
		
		if($ext=="gif" && !$src_img){
			$src_img2=@imagecreatefromjpeg($filename);
			if($src_img2!==FALSE) {
				$storename=str_replace($file,$fileNoExt.'.jpg',$storename); 
				$newFileName=str_replace($file,$fileNoExt.'.jpg',$filename);
				copy($filename,$newFileName);
				$filename=str_replace($file,$fileNoExt.'.jpg',$filename);
				$changedExt=1;
			}
		}
			
		if (!$src_img && !$src_img2) {
			return false;
		}
		list($width_orig, $height_orig) = getimagesize($filename);	
		if ($width_orig < $newWidth) {
			$d = copy($b_file, $store);
			return $d;
		}
		$percent = $newWidth / $width_orig;
		$new_width = $width_orig * $percent;
		$new_height = $height_orig * $percent;
		if($width_orig<$new_width || $height_orig<$new_height) { 
			$new_width = $width_orig;
			$new_height = $height_orig;
		}
		 
		if ($new_height < 1) {
			$new_height = 1;
		}
		if ($new_width < 1) {
			$new_width = 1;
		}
		$new_height=(int)$new_height;
		$new_width=(int)$new_width;
		
		$at=0;
		if($ext=="gif") { 
			$at=1;
			$dst_img = @imagecreate($new_width,$new_height);
			$trans = imagecolorallocate($dst_img, 0, 0, 0);
			imagecolortransparent($dst_img, $trans);
		} elseif($ext=="png") {
			$at=2;
			$dst_img = @imagecreatetruecolor($new_width,$new_height);
			imagealphablending($dst_img, false);
			$color = imagecolortransparent($dst_img, imagecolorallocatealpha($dst_img, 0, 0, 0, 127));
			imagefill($dst_img, 0, 0, $color);
			imagesavealpha($dst_img, true);
		} else { 
			$at=3;
			$dst_img = @imagecreatetruecolor($new_width,$new_height);
		} 
		
		
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$new_width, $new_height, $width_orig, $height_orig);		
		if ($ext=="png"){
			$makeImg=imagepng($dst_img,$storename);
		}elseif($ext=="gif"){
			$makeImg=imagegif($dst_img,$storename);
		}else{
			$makeImg=imagejpeg($dst_img,$storename,90);
		}
		imagedestroy($dst_img);
		imagedestroy($src_img);
		if (!$makeImg) throw new ExceptionBase('Unable to create requested image');
		return true;
	}
	
}

?>
