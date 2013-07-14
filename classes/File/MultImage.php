<?php

class FileMultImage extends FileMultiple
{
    public $validationPreg = UtilsString::IMAGE_FILENAME_REGEXP;

    public function acceptUpload()
    {
        $this->checkForUploadErrors();
        foreach ($_FILES[$this->name]['name'] as $index => $name) {
            $dest = $this->getBaseDir() . UtilsString::urlSafe($name, true);
            if (!move_uploaded_file($_FILES[$this->name]['tmp_name'][$index], $dest)) {
                throw new ExceptionBase('Couldnt move from ' . $_FILES[$this->name]['tmp_name'] . ' to ' . $dest);
            }
        }
    }

    public function getSrcsToImagesAtWidth ($width) {
        $paths = $this->getPathsToImagesAtWidth($width);
        $srcs = array();
        foreach ($paths as $path) {
            $srcs[] = APP_SUB_DIR . str_replace(SERVER_PREFIX, '', $path);
        }
        return $srcs;
    }

    public function getPathsToImagesAtWidth($width) {
        $width = abs((int)$width);
        if ($width < 1) throw new ExceptionBase('invalid width');
        $rd = $this->getResizeDir();
        $fileNames = $this->getFilenames();
        $paths = array();
        foreach ($fileNames as $fileName) {
            $nameParts = explode('.', $fileName);
            $ext = array_pop($nameParts);
            $nameParts[] = $width;
            $nameParts[] = $ext;
            $requestedName = implode('.', $nameParts);
            if (!file_exists($rd . $requestedName)) {
                UtilsImage::resizeImage($this->getBaseDir() . $fileName, $rd . $requestedName, $width);
            }
            $paths[] = $rd . $requestedName;
        }
        return $paths;
    }

}

