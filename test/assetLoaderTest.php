<?php
class AssetLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testLoadJs()
    {
        $this->assertEquals(
            array(
                '/assets/javascripts/application.coffee',
                '/assets/javascripts/base/bootstrap.js',
                '/assets/javascripts/home.js'
            ),
            AssetLoader::loadJs('home')
        );
    }

    public function testLoadCss()
    {
        $this->assertEquals(
            array(
                '/assets/stylesheets/application.scss',
                '/assets/stylesheets/ui/bootstrap.scss',
                '/assets/stylesheets/ui/global/menu.css',
                '/assets/stylesheets/ui/base.less',
                '/assets/stylesheets/home.css'
            ),
            AssetLoader::loadCss('home')
        );
    }
}