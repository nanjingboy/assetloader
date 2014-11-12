<?php
class AssetLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testLoadJs()
    {
        $this->assertEquals(
            array(
                '/assets/javascripts/base.js',
                '/assets/javascripts/application.coffee',
                '/assets/javascripts/bootstrap.js/bootstrap.js',
                '/assets/javascripts/home.js'
            ),
            AssetLoader::loadJs('home')
        );
    }

    public function testLoadCss()
    {
        $this->assertEquals(
            array(
                '/assets/stylesheets/application.css',
                '/assets/stylesheets/ui.css/global/menu.css',
                '/assets/stylesheets/ui.css/base.less',
                '/assets/stylesheets/ui.css/bootstrap.scss',
                '/assets/stylesheets/home.css'
            ),
            AssetLoader::loadCss('home')
        );
    }
}