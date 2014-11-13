### AssetLoader:

AssetLoader is a simple library to load javascript & css files.

### Getting Started:

* Create composer.json file in root directory of  your application:

```json
 {
    "require": {
        "php": ">=5.4.0",
        "nanjingboy/assetloader": "*"
    }
}
```
* Install it via [composer](https://getcomposer.org/doc/00-intro.md)

* Create directory in root directory of your application:

```shell
 $ mkdir tmp/assetLoader -p
 $ chmod -R a+w tmp
```

* Init the config in your bootstrap.php:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
AssetLoader::init($serverRootPath, $jsDirectoryPath, $cssDirectoryPath);
```

### Usage:
We can use the require or require_dir directive to load the dependencies, there are the formats for this directive below:

* CoffeeScript:
```coffeescript
# require dependency
# require_dir dependency_dir
```

* JavaScript, Scss, Less:
```js
// require dependency
// require_dir dependency_dir
```

* JavaScript, Css, Scss, Less(single line):
```js
/* require dependency */
/* require_dir dependency_dir */
```

* JavaScript, Css, Scss, Less(multi line):
```js
/**
 * require one_dependency
 * require two_dependency
 * require_dir dependency_dir
 */
```

### Notes:

* The require comments must on the top in a asset file.
* The dependency's path is relative with the root directory path of your javascripts or stylesheets.
* The require_dir directive auto load all files in this directory without recursive.
* It's just used to parse the load paths for asset's dependencies, it can't compile any files(e.g. a coffeeScript file).

### Example:

Get a example from [test](https://github.com/nanjingboy/assetloader/tree/master/test)

### License:
MIT