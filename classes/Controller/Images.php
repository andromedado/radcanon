<?php

class ControllerImages extends ControllerApp {
	
	public function catchAll () {
		$args = func_get_args();
		$content = IMAGE_NOT_FOUND_FILE;
		$contentType = IMAGE_NOT_FOUND_TYPE;
		if (count($args) === 4) {
			list($model, $id, $type, $file) = $args;
			$c = 'Model' . preg_replace('#[^A-Z\d_-]#i', '', $model);
			if (class_exists($c)) {
				$C = new $c($id);
				if (preg_match('#(.*)_(\d+)(\.[^\.]+)$#', $file, $m)) {
					$width = $m[2];
					$fileName = $m[1] . $m[3];
					$F = new FileMultImage($C, $type);
					if ($F->hasFile($fileName)) {
						$content = $F->getPathToImageAtWidth($fileName, $width);
						$contentType = UtilsImage::getMimeType($fileName);
					}
				} else {
					$width = (int)array_shift(explode('.', $file));
					$F = new FileSingleImage($C, $type);
					if ($width > 0 && $F->hasFile()) {
						$content = $F->getPathToImageAtWidth($width);
						$contentType = $F->getFileMimeType();
					}
				}
			}
		}
		$this->response->contentType = $contentType;
		$this->response->type = Response::TYPE_FILESTREAM;
		return $content;
	}
	
}

?>