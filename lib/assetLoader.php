<?php
use AssetLoader\Config;

class AssetLoader
{
    public static function init($jsDirectories, $cssDirectories)
    {
        Config::setJsDirectories($jsDirectories);
        Config::setCssDirectories($cssDirectories);
    }

    public static function loadJs($file)
    {
    }

    public static function loadCss($file)
    {
    }
}