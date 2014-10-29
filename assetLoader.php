<?php
class AssetLoader
{
    private static $_loadFiles;

    private static $_serverRootPath;
    private static $_jsDirectoryPath;
    private static $_cssDirectoryPath;

    private static $_jsExtNames = array('.js', '.coffee');
    private static $_cssExtNames = array('.css', '.scss', '.less');

    private static function _parseJsComment($comment)
    {
        if (strpos($comment, '//') === 0 || strpos($comment, '#') === 0) {
            return trim(str_replace(array('//', '#'), '', $comment));
        }

        return null;
    }

    private static function _parseCssComment($comment)
    {
        if (preg_match('/^\/\*.*\*\/$/', $comment) || strpos($comment, '//') === 0) {
            return trim(str_replace(array('/*', '*/', '//'), '', $comment));
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
                $require = rtrim(
                    trim(str_replace('require', '', $comment)),
                    DIRECTORY_SEPARATOR
                );
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

    private static function _parse($file, $type)
    {
        if ($type === 'js') {
            $extNames = self::$_jsExtNames;
            $baseDir = self::$_jsDirectoryPath;
        } else {
            $extNames = self::$_cssExtNames;
            $baseDir = self::$_cssDirectoryPath;
        }

        $filePath = self::_parseFilePath(
            $baseDir . DIRECTORY_SEPARATOR . str_replace($extNames, '', $file),
            $type
        );

        if ($filePath !== null) {
            $fileRelativePath = str_replace(self::$_serverRootPath, '', $filePath);
            if (in_array($fileRelativePath, self::$_loadFiles) === false) {
                array_unshift(self::$_loadFiles, $fileRelativePath);
                $requires = self::_parseRequires($filePath, $type);
                foreach ($requires as $require) {
                    if (strpos($require, '/') === 0) {
                        $requirePath = "{$baseDir}{$require}";
                    } else {
                        $requirePath = dirname($filePath) . DIRECTORY_SEPARATOR . $require;
                    }

                    if (is_dir($requirePath)) {
                        $requireFiles = scandir($requirePath);
                        foreach ($requireFiles as $requireFile) {
                            if ($requireFile === '.' || $requireFile === '..') {
                                continue;
                            }
                            $requireFilePath = $requirePath . DIRECTORY_SEPARATOR . $requireFile;
                            if (is_dir($requireFilePath)) {
                                continue;
                            }
                            self::_parse(
                                ltrim(str_replace($baseDir, '', $requireFilePath), DIRECTORY_SEPARATOR),
                                $type
                            );
                        }
                    } else {
                        self::_parse(ltrim($require, DIRECTORY_SEPARATOR), $type);
                    }
                }
            }
        }
    }

    private static function _load($file, $type)
    {
        self::$_loadFiles = array();
        self::_parse($file, $type);
        return self::$_loadFiles;
    }

    public static function init($serverRootPath, $jsDirectoryPath, $cssDirectoryPath)
    {
        self::$_serverRootPath = rtrim($serverRootPath, DIRECTORY_SEPARATOR);
        self::$_jsDirectoryPath = rtrim($jsDirectoryPath, DIRECTORY_SEPARATOR);
        self::$_cssDirectoryPath = rtrim($cssDirectoryPath, DIRECTORY_SEPARATOR);
    }

    public static function loadJs($file)
    {
        return self::_load($file, 'js');
    }

    public static function loadCss($file)
    {
        return self::_load($file, 'css');
    }
}