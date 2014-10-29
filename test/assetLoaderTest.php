<?php
class AssetLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testLoadJs()
    {
        $this->assertEquals(
            array(
                '/assets/javascripts/base/bootstrap.js',
                '/assets/javascripts/application.coffee',
                '/assets/javascripts/home.js'
            ),
            AssetLoader::loadJs('home')
        );
    }

    public function testLoadCss()
    {
        $this->assertEquals(
            array(
                '/assets/stylesheets/base/bootstrap.css',
                '/assets/stylesheets/application.css',
                '/assets/stylesheets/home.css'
            ),
            AssetLoader::loadCss('home')
        );
    }
}