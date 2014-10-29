<?php
class AssetLoader
{
    private static $_jsDirectories = array();
    private static $_cssDirectories = array();

    private static function _parseJsRequire($comment)
    {
        $require = null;
        if (strpos($comment, '//') === 0) {
            $comment = trim(str_replace('//', '', $comment));
            if (strpos($comment, 'require') === 0) {
                $require = trim(str_replace('require', '', $comment));
            }
        }
        return $require;
    }

    private static function _parseCssRequire($comment)
    {
        $require = null;
        if (preg_match('/^\/\*.*\*\/$/', $comment)) {
            $comment = trim(str_replace(array('/*', '*/'), '', $comment));
            if (strpos($comment, 'require') === 0) {
                $require = trim(str_replace('require', '', $comment));
            }
        }
        return $require;
    }

    private static function _parseRequires($filePath, $type)
    {
        $requires = array();
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($type === 'js') {
                $require = self::_parseJsRequire($line);
            } else {
                $require = self::_parseCssRequire($line);
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

    private static function _parse($file, $type, $result)
    {
        $file = str_replace(".{$type}", '', $file) . '.' . $type;
        if ($type === 'js') {
            $directories = self::$_jsDirectories;
        } else {
            $directories = self::$_cssDirectories;
        }

        foreach ($directories as $directory) {
            $filePath = $directory . DIRECTORY_SEPARATOR . $file;
            if (file_exists($filePath)) {
                array_unshift($result, $file);
                $requires = self::_parseRequires($filePath, $type);
                foreach ($requires as $require) {
                    $requirePath = $directory . DIRECTORY_SEPARATOR . $require;
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
                                ltrim(
                                    str_replace($directory, '', $requireFilePath),
                                    DIRECTORY_SEPARATOR
                                ),
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

    public static function init($jsDirectories, $cssDirectories)
    {
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