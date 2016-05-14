<?php namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Utils\UrlUtils;

class UrlUtilsTest extends BaseTest
{

    public function testHttpWithout()
    {
        $this->assertEquals('http://example.com/', UrlUtils::resolveRelativeUrl('http://example.com/', ''));
        $this->assertEquals('http://example.com', UrlUtils::resolveRelativeUrl('http://example.com', ''));
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/test', ''));
    }

    public function testHttpPaths()
    {
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/', '/test'));
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com', '/test'));
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com//', '/test'));
        $this->assertEquals('http://example.com/test/', UrlUtils::resolveRelativeUrl('http://example.com', '/test/'));
        $this->assertEquals('http://example.com/test/test', UrlUtils::resolveRelativeUrl('http://example.com/', '/test/test'));
    }

    public function testHttpPathsParent()
    {
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '/test'));
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/parent', '/test'));
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com//parent', '/test'));
        $this->assertEquals('http://example.com/test/', UrlUtils::resolveRelativeUrl('http://example.com/parent', '/test/'));
        $this->assertEquals('http://example.com/test/test', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '/test/test'));
    }

    public function testHttpPathsParentRelative()
    {
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '../test'));
        $this->assertEquals('http://example.com/test/', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '../test/'));
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com//parent/', '../test'));
        $this->assertEquals('http://example.com/test/test', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '../test/test'));
    }


    public function testHttpAnchors()
    {
        $this->assertEquals('http://example.com/#test', UrlUtils::resolveRelativeUrl('http://example.com/', '#test'));
        $this->assertEquals('http://example.com/test#test', UrlUtils::resolveRelativeUrl('http://example.com/', 'test#test'));
        $this->assertEquals('http://example.com/test#test', UrlUtils::resolveRelativeUrl('http://example.com', 'test#test'));
        $this->assertEquals('http://example.com/test/test#test', UrlUtils::resolveRelativeUrl('http://example.com/', 'test/test#test'));
        $this->assertEquals('http://example.com/test/test#test', UrlUtils::resolveRelativeUrl('http://example.com', 'test/test#test'));
    }


    public function testHttpQS()
    {
        $this->assertEquals('http://example.com/?test=1', UrlUtils::resolveRelativeUrl('http://example.com/', '?test=1'));
        $this->assertEquals('http://example.com/test?test=1', UrlUtils::resolveRelativeUrl('http://example.com/', 'test?test=1'));
        $this->assertEquals('http://example.com/test?test=1', UrlUtils::resolveRelativeUrl('http://example.com', 'test?test=1'));
        $this->assertEquals('http://example.com/test/test?test=1', UrlUtils::resolveRelativeUrl('http://example.com/', 'test/test?test=1'));
        $this->assertEquals('http://example.com/test/test?test=1', UrlUtils::resolveRelativeUrl('http://example.com', 'test/test?test=1'));
    }


    public function testFilePaths()
    {
        $this->assertEquals('file:///test', UrlUtils::resolveRelativeUrl('file:///', '/test'));
        $this->assertEquals('file:///test', UrlUtils::resolveRelativeUrl('file:///', 'test'));
        /* Assert that any filenames will be stripped from base */
        $this->assertEquals('file:///bar.xsd', UrlUtils::resolveRelativeUrl('file:///foo.xsd', 'bar.xsd'));
    }


    public function testRegularPaths()
    {
        $this->assertEquals('/test', UrlUtils::resolveRelativeUrl('/', '/test'));
        $this->assertEquals('/test', UrlUtils::resolveRelativeUrl('/', 'test'));
    }


    public function testRegularPathsParent()
    {
        $this->assertEquals('/testing', UrlUtils::resolveRelativeUrl('/test/child', '../testing'));
    }

}
