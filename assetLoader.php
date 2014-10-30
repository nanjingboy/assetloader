<?php
class AssetLoader
{
    private static $_loadFiles;

    private static $_serverRootPath;
    private static $_jsDirectoryPath;
    private static $_cssDirectoryPath;

    private static $_jsExtNames = array('.js', '.coffee');
    private static $_cssExtNames = array('.css', '.scss', '.less');

    private static function _parseRequires($filePath)
    {
        $requires = array();
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Get require from comment which format like: // require application or # require application
            $matches = array();
            preg_match_all('/^(\/\/|#)\s*require\s+(.*)$/', $line, $matches);
            if (!empty($matches[2][0])) {
                array_push($requires, trim($matches[2][0]));
                continue;
            }

            // Get require from comment which format like: /* require application */
            $matches = array();
            preg_match_all('/^\/\*\s*require\s+(.*)\*\/$/', $line, $matches);
            if (!empty($matches[1][0])) {
                array_push($requires, trim($matches[1][0]));
                continue;
            }
            break;
        }

        // Get requires from comment which format like below comment:
        // /**
        //  * require application
        //  * require bootstrap
        //  */
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($index === 0) {
                if ($line === '/**') {
                    continue;
                }
                break;
            }

            $matches = array();
            preg_match_all('/^\*\s*require\s+(.*)$/', $line, $matches);
            if (!empty($matches[1][0])) {
                array_push($requires, trim($matches[1][0]));
                continue;
            }
            break;
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
                $requires = self::_parseRequires($filePath);
                foreach ($requires as $require) {
                    if (strpos($require, '/') === 0) {
                        $requirePath = "{$baseDir}{$require}";
                    } else {
                        $requirePath = dirname($filePath) . DIRECTORY_SEPARATOR . $require;
                    }
                    $requirePath = realpath($requirePath);

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