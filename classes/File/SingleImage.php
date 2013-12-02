<?php

class FileSingleImage extends FileSingle
{
    public $validationPreg = UtilsString::IMAGE_FILENAME_REGEXP;

    public function getFileMimeType () {
        return UtilsImage::getMimeType($this->getFilename());
    }

    public function getSrcToImageAtWidth ($width) {
        return $this->getAppSubDir() . str_replace($this->getServerPrefix(), '', $this->getPathToImageAtWidth($width));
    }

    public function getPathToImageAtWidth($width) {
        $width = abs((int)$width);
        if ($width < 1) throw new ExceptionBase('invalid width');
        $rd = $this->getResizeDir();
        $fileName = $this->getFilename();
        $nameParts = explode('.', $fileName);
        $ext = array_pop($nameParts);
        $nameParts[] = $width;
        $nameParts[] = $ext;
        $requestedName = implode('.', $nameParts);
        if (!file_exists($rd . $requestedName)) {
            UtilsImage::resizeImage($this->getBaseDir() . $fileName, $rd . $requestedName, $width);
        }
        return $rd . $requestedName;
    }

}

