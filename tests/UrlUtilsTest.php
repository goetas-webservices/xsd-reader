<?php namespace Goetas\XML\XSDReader\Tests;

use Goetas\XML\XSDReader\Utils\UrlUtils;

class UrlUtilsTest extends BaseTest {

    public function testHttpPaths()
    {
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/', '/test'));
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com', '/test'));
        $this->assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com//', '/test'));
        $this->assertEquals('http://example.com/test/', UrlUtils::resolveRelativeUrl('http://example.com', '/test/'));
        $this->assertEquals('http://example.com/test/test', UrlUtils::resolveRelativeUrl('http://example.com/', '/test/test'));
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

} 
