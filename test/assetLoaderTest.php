<?php
class AssetLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testLoadJs()
    {
        $this->assertEquals(
            array(
                'base/bootstrap.js',
                'application.coffee',
                'home.js'
            ),
            AssetLoader::loadJs('home')
        );
    }

    public function testLoadCss()
    {
        $this->assertEquals(
            array(
                'base/bootstrap.css',
                'application.css',
                'home.css'
            ),
            AssetLoader::loadCss('home')
        );
    }
}