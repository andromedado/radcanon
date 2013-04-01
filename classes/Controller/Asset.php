<?php

/**
 * Class ControllerAsset
 */
abstract class ControllerAsset extends ControllerApp
{
    /** @var Array $directories with trailing slash */
    protected $directories = array();
    protected $filters = array(
        'ControllerApp::trimmy',
    );

    public function catchAll($methodName)
    {
        $asset = $methodName;
        foreach ($this->filters as $filter) {
            $asset = call_user_func($filter, $asset);
        }
        $file = $this->translateAssetToFile($asset);
        if ($file && file_exists($file)) {
            $this->serveFile($file);
        }
        $this->notFound();
    }

    protected function translateAssetToFile($asset)
    {
        foreach ($this->directories as $dir) {
            if (file_exists($dir . $asset)) {
                return $dir . $asset;
            }
        }
        return null;
    }

    protected static function trimmy($str)
    {
        return preg_replace('#\.\.' . preg_quote(DS, '#') . '#', '', ltrim($str, '.' . DS));
    }
}

