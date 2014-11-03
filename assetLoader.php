<?php
class AssetLoader
{
    private static $_loadFiles;

    private static $_serverRootPath;
    private static $_jsDirectoryPath;
    private static $_cssDirectoryPath;

    private static $_defaultParent = 'a27c53f4c1769a5c89a91ba3a6855654';

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
            preg_match_all('/^(\/\/|#)\s*(require|require_dir)\s+(.*)$/', $line, $matches);

            if (!empty($matches[2][0]) && !empty($matches[3][0])) {
                array_push(
                    $requires,
                    array(
                        'path' => trim($matches[3][0]),
                        'isDirectory' => trim($matches[2][0]) === 'require_dir'
                    )
                );
                continue;
            }

            // Get require from comment which format like: /* require application */
            $matches = array();
            preg_match_all('/^\/\*\s*(require|require_dir)\s+(.*)\*\/$/', $line, $matches);
            if (!empty($matches[1][0]) && !empty($matches[2][0])) {
                array_push(
                    $requires,
                    array(
                        'path' => trim($matches[2][0]),
                        'isDirectory' => trim($matches[1][0]) === 'require_dir'
                    )
                );
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
            preg_match_all('/^\*\s*(require|require_dir)\s+(.*)$/', $line, $matches);
            if (!empty($matches[1][0]) && !empty($matches[2][0])) {
                array_push(
                    $requires,
                    array(
                        'path' => trim($matches[2][0]),
                        'isDirectory' => trim($matches[1][0]) === 'require_dir'
                    )
                );
                continue;
            }
            break;
        }

        return $requires;
    }

    private static function _parseFile($file, $type)
    {
        if ($type === 'js') {
            $extNames = self::$_jsExtNames;
            $baseDir = self::$_jsDirectoryPath . DIRECTORY_SEPARATOR;
        } else {
            $extNames = self::$_cssExtNames;
            $baseDir = self::$_cssDirectoryPath . DIRECTORY_SEPARATOR;
        }
        $file = str_replace($extNames, '', $file);

        $path = null;
        foreach ($extNames as $extName) {
            $_path = "{$baseDir}{$file}{$extName}";
            if (file_exists($_path)) {
                $path = $_path;
                break;
            }
        }

        return array($baseDir, $path, str_replace(self::$_serverRootPath, '', $path));
    }

    private static function _isLoaded($path)
    {
        foreach (self::$_loadFiles as $loadFiles) {
            if (in_array($path, $loadFiles)) {
                return true;
            }
        }
        return false;
    }

    private static function _parse($file, $type, $parent = null)
    {
        list($baseDir, $path, $relativePath) = self::_parseFile($file, $type);

        if ($path !== null && self::_isLoaded($relativePath) === false) {
            if ($parent === null) {
                $parent = self::$_defaultParent;
            }

            if (!array_key_exists($parent, self::$_loadFiles)) {
                self::$_loadFiles[$parent] = array();
            }

            $requires = self::_parseRequires($path);
            foreach ($requires as $require) {
                $require['path'] = ltrim($require['path'], './');
                if ($require['isDirectory'] === false) {
                    self::_parse(
                        ltrim($require['path'], DIRECTORY_SEPARATOR),
                        $type,
                        $parent
                    );
                    continue;
                }

                if (strpos($require['path'], '/') === 0) {
                    $dirPath = "{$baseDir}{$require['path']}";
                } else {
                    $dirPath = dirname($path) . DIRECTORY_SEPARATOR . $require['path'];
                }
                $dirPath = realpath($dirPath);
                $dirRelativePath = str_replace(self::$_serverRootPath, '', $dirPath);
                if (is_dir($dirPath) && self::_isLoaded($dirRelativePath) === false) {
                    array_push(self::$_loadFiles[$parent], $dirRelativePath);
                    $requireFiles = scandir($dirPath);
                    foreach ($requireFiles as $requireFile) {
                        if ($requireFile === '.' || $requireFile === '..') {
                            continue;
                        }

                        $requireFilePath = $dirPath . DIRECTORY_SEPARATOR . $requireFile;
                        if (is_dir($requireFilePath)) {
                            continue;
                        }

                        self::_parse(
                            ltrim(str_replace($baseDir, '', $requireFilePath), DIRECTORY_SEPARATOR),
                            $type,
                            $dirRelativePath
                        );
                    }
                }
            }
            array_push(self::$_loadFiles[$parent], $relativePath);
        }
    }

    private static function _loadFiles($key)
    {
        $result = array();
        if (!empty(self::$_loadFiles[$key])) {
            foreach (self::$_loadFiles[$key] as $loadFile) {
                if (!empty(self::$_loadFiles[$loadFile])) {
                    $result = array_merge($result, self::_loadFiles($loadFile));
                } else {
                    array_push($result, $loadFile);
                }
            }
        }
        return $result;
    }

    private static function _load($file, $type)
    {
        self::$_loadFiles = array();
        self::_parse($file, $type);
        return self::_loadFiles(self::$_defaultParent);
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