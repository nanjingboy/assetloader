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

### Usage Example:

Get a example from [test](https://github.com/nanjingboy/assetloader/tree/master/test)

### Notes:

* The require comments must on the top in a asset file.
* It's just used to parse the load paths for asset's dependencies, it can't compile any files(e.g. a coffeeScript file).

### License:
MIT