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

    private static function _isLoaded($path)
    {
        foreach (self::$_loadFiles as $loadFiles) {
            foreach ($loadFiles as $loadFile) {
                if ($loadFile['path'] === $path) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function _getBaseDir($type)
    {
        if ($type === 'js') {
            return self::$_jsDirectoryPath . DIRECTORY_SEPARATOR;
        }

        return $baseDir = self::$_cssDirectoryPath . DIRECTORY_SEPARATOR;
    }

    private static function _getFileInfo($file, $type)
    {
        $fileInfo = new SplFileInfo($file);
        $filePath = $fileInfo->getPath();
        $fileBaseName = $fileInfo->getBasename('.' . $fileInfo->getExtension());
        if (empty($filePath)) {
            $file = $fileBaseName;
        } else {
            $file = $filePath . DIRECTORY_SEPARATOR . $fileBaseName;
        }

        $path = null;
        $baseDir = self::_getBaseDir($type);
        $extNames = ($type === 'js' ? self::$_jsExtNames : self::$_cssExtNames);
        foreach ($extNames as $extName) {
            $_path = "{$baseDir}{$file}{$extName}";
            if (file_exists($_path)) {
                $path = $_path;
                break;
            }
        }

        return array($path, str_replace(self::$_serverRootPath, '', $path));
    }

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

    private static function _parseDirectory($path, $type, $parent)
    {
        $path = realpath($path);
        $baseDir = self::_getBaseDir($type);
        $relativePath = str_replace(self::$_serverRootPath, '', $path);
        if (self::_isLoaded($relativePath) === false) {
            if (array_key_exists($parent, self::$_loadFiles) === false) {
                self::$_loadFiles[$parent] = array();
            }
            array_push(
                self::$_loadFiles[$parent],
                array('path' => $relativePath, 'lastModified' => filemtime($path))
            );
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filePath)) {
                    continue;
                }

                self::_parse(
                    ltrim(str_replace($baseDir, '', $filePath), DIRECTORY_SEPARATOR),
                    $type,
                    $relativePath
                );
            }
        }
    }

    private static function _parse($file, $type, $parent = null)
    {
        $baseDir = self::_getBaseDir($type);
        list($path, $relativePath) = self::_getFileInfo($file, $type);
        if ($path !== null && self::_isLoaded($relativePath) === false) {
            if ($parent === null) {
                $parent = self::$_defaultParent;
            }

            if (array_key_exists($parent, self::$_loadFiles) === false) {
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

                if (is_dir($dirPath)) {
                    self::_parseDirectory($dirPath, $type, $parent);
                }
            }
            array_push(
                self::$_loadFiles[$parent],
                array('path' => $relativePath, 'lastModified' => filemtime($path))
            );
        }
    }

    private static function _parseCachePath($file, $type)
    {
        list($path, $relativePath) = self::_getFileInfo($file, $type);
        if ($path === null) {
            return false;
        }

        $cacheDir = self::$_serverRootPath . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'assetLoader';
        if (file_exists($cacheDir) === false) {
            mkdir($cacheDir, 0775, true);
        }

        return array(
            'path' => $cacheDir . DIRECTORY_SEPARATOR . str_replace(
                DIRECTORY_SEPARATOR, '_', trim($relativePath,  DIRECTORY_SEPARATOR)
            ),
            'lastModified' => filemtime($path)
        );
    }

    private static function _loadFiles($key)
    {
        $result = array();
        if (!empty(self::$_loadFiles[$key])) {
            foreach (self::$_loadFiles[$key] as $loadFile) {
                if (!empty(self::$_loadFiles[$loadFile['path']])) {
                    $result = array_merge($result, self::_loadFiles($loadFile['path']));
                } else {
                    array_push($result, $loadFile['path']);
                }
            }
        }
        return $result;
    }

    private static function _load($file, $type)
    {
        $cachedFile = self::_parseCachePath($file, $type);
        if ($cachedFile === false) {
            return array();
        }

        if (file_exists($cachedFile['path'])) {
            $cachedLoad = unserialize(file_get_contents($cachedFile['path']));
            if (!empty($cachedLoad[$cachedFile['lastModified']])) {
                $cachedLoad = $cachedLoad[$cachedFile['lastModified']];
            } else {
                $cachedLoad = array();
            }
        } else {
            $cachedLoad = array();
        }

        self::$_loadFiles = array();
        if (empty($cachedLoad)) {
            self::_parse($file, $type);
        } else {
            $baseDir = self::_getBaseDir($type);
            foreach ($cachedLoad as $parent => $files) {
                if (array_key_exists($parent, self::$_loadFiles)) {
                    continue;
                }

                self::$_loadFiles[$parent] = array();
                foreach ($files as $index => $file) {
                    $realPath = self::$_serverRootPath . $file['path'];
                    if (file_exists($realPath) === false) {
                        continue;
                    }

                    if (filemtime($realPath) === $file['lastModified']) {
                        array_push(self::$_loadFiles[$parent], $file);
                        continue;
                    }

                    if (is_dir($realPath)) {
                        self::_parseDirectory($realPath, $type, $parent);
                        continue;
                    }

                    self::_parse(
                        ltrim(str_replace($baseDir, '', $realPath), DIRECTORY_SEPARATOR),
                        $type,
                        $parent
                    );
                }
            }
        }

        file_put_contents(
            $cachedFile['path'],
            serialize(array($cachedFile['lastModified'] => self::$_loadFiles))
        );

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