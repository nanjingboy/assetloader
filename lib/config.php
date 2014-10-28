<?php
namespace AssetLoader;

class Config
{
    private static $_jsDirectories;
    private static $_cssDirectories;

    public static function getJsDirectories()
    {
        return self::$_jsDirectories;
    }

    public static function getCssDirectories()
    {
        return self::$_cssDirectories;
    }

    public static function setJsDirectories($jsDirectories)
    {
        self::$_jsDirectories = $jsDirectories;
    }

    public static function setCssDirectories($cssDirectories)
    {
        self::$_cssDirectories = $cssDirectories;
    }
}