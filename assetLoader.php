<?php
class AssetLoader
{
    private static $_serverRootPath;

    private static $_jsDirectories = array();
    private static $_cssDirectories = array();

    private static $_jsExtNames = array('.js', '.coffee');
    private static $_cssExtNames = array('.css');

    private static function _parseJsComment($comment)
    {
        if (strpos($comment, '//') === 0 || strpos($comment, '#') === 0) {
            return trim(str_replace(array('//', '#'), '', $comment));
        }

        return null;
    }

    private static function _parseCssComment($comment)
    {
        if (preg_match('/^\/\*.*\*\/$/', $comment)) {
            return trim(str_replace(array('/*', '*/'), '', $comment));
        }

        return null;
    }

    private static function _parseRequires($filePath, $type)
    {
        $requires = array();
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($type === 'js') {
                $comment = self::_parseJsComment($line);
            } else {
                $comment = self::_parseCssComment($line);
            }

            $require = null;
            if (strpos($comment, 'require') === 0) {
                $require = trim(str_replace('require', '', $comment));
            }

            // As require comments must be in the top of one file,
            // so if we get a null require, it means has no more requires in the file.
            if ($require === null) {
                return $requires;
            }

            array_push($requires, $require);
        }

        return $requires;
    }

    private static function _parseFilePath($file, $type)
    {
        if ($type === 'js') {
            $extNames = self::$_jsExtNames;
        } else {
            $extNames = self::$_cssExtNames;
        }

        foreach ($extNames as $extName) {
            $filePath = $file . $extName;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }

        return null;
    }

    private static function _parse($file, $type, $result)
    {
        if ($type === 'js') {
            $directories = self::$_jsDirectories;
            $file = str_replace(self::$_jsExtNames, '', $file);
        } else {
            $directories = self::$_cssDirectories;
            $file = str_replace(self::$_cssExtNames, '', $file);
        }

        foreach ($directories as $directory) {
            $directory = $directory . DIRECTORY_SEPARATOR;
            $filePath = self::_parseFilePath("{$directory}{$file}", $type);
            if (!empty($filePath)) {
                array_unshift($result, str_replace(self::$_serverRootPath, '', $filePath));
                $requires = self::_parseRequires($filePath, $type);
                foreach ($requires as $require) {
                    $requirePath = "{$directory}{$require}";
                    if (is_dir($requirePath)) {
                        $requireFiles = scandir($requirePath);
                        foreach ($requireFiles as $requireFile) {
                            if ($requireFile === '.' || $requireFile === '..') {
                                continue;
                            }

                            $requireFilePath = $requirePath . DIRECTORY_SEPARATOR . $requireFile;
                            if (is_dir($requireFile)) {
                                continue;
                            }

                            return self::_parse(
                                ltrim(str_replace($directory, '', $requireFilePath), DIRECTORY_SEPARATOR),
                                $type,
                                $result
                            );
                        }
                    } else {
                        return self::_parse($require, $type, $result);
                    }
                }
                break;
            }
        }

        return $result;
    }

    public static function init($serverRootPath, $jsDirectories, $cssDirectories)
    {
        self::$_serverRootPath = $serverRootPath;
        self::$_jsDirectories = $jsDirectories;
        self::$_cssDirectories = $cssDirectories;
    }

    public static function loadJs($file)
    {
        return self::_parse($file, 'js', array());
    }

    public static function loadCss($file)
    {
        return self::_parse($file, 'css', array());
    }
}