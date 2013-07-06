<?php

class FileMultImage extends FileOfModel
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

}

