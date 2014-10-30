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

### Usage:
We can use the require directive to load the dependencies, there are the formats for this directive below:

* CoffeeScript:
```coffeescript
# require dependency
```

* JavaScript, Scss, Less:
```js
// require dependency
```

* JavaScript, Css, Scss, Less(single line):
```js
/* require dependency */
```

* JavaScript, Css, Scss, Less(multi line):
```js
/**
 * require one_dependency
 * require two_dependency
 */
```

### Notes:

* The require comments must on the top in a asset file.
* The dependency's path is relative with the root directory path of your javascripts or stylesheets.
* If dependency is a directory, it will auto load all files in this directory(non-recursive).
* It's just used to parse the load paths for asset's dependencies, it can't compile any files(e.g. a coffeeScript file).

### Example:

Get a example from [test](https://github.com/nanjingboy/assetloader/tree/master/test)

### License:
MIT