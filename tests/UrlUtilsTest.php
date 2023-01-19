<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Utils\UrlUtils;

class UrlUtilsTest extends BaseTest
{
    public function testHttpWithout()
    {
        self::assertEquals('http://example.com/', UrlUtils::resolveRelativeUrl('http://example.com/', ''));
        self::assertEquals('http://example.com', UrlUtils::resolveRelativeUrl('http://example.com', ''));
        self::assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/test', ''));
    }

    public function testHttpPaths()
    {
        self::assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/', '/test'));
        self::assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com', '/test'));
        self::assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com//', '/test'));
        self::assertEquals('http://example.com/test/', UrlUtils::resolveRelativeUrl('http://example.com', '/test/'));
        self::assertEquals('http://example.com/test/test', UrlUtils::resolveRelativeUrl('http://example.com/', '/test/test'));
    }

    public function testHttpPathsParent()
    {
        self::assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '/test'));
        self::assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/parent', '/test'));
        self::assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com//parent', '/test'));
        self::assertEquals('http://example.com/test/', UrlUtils::resolveRelativeUrl('http://example.com/parent', '/test/'));
        self::assertEquals('http://example.com/test/test', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '/test/test'));
    }

    public function testHttpPathsParentRelative()
    {
        self::assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '../test'));
        self::assertEquals('http://example.com/test/', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '../test/'));
        self::assertEquals('http://example.com/test', UrlUtils::resolveRelativeUrl('http://example.com//parent/', '../test'));
        self::assertEquals('http://example.com/test/test', UrlUtils::resolveRelativeUrl('http://example.com/parent/', '../test/test'));
    }

    public function testHttpAnchors()
    {
        self::assertEquals('http://example.com/#test', UrlUtils::resolveRelativeUrl('http://example.com/', '#test'));
        self::assertEquals('http://example.com/test#test', UrlUtils::resolveRelativeUrl('http://example.com/', 'test#test'));
        self::assertEquals('http://example.com/test#test', UrlUtils::resolveRelativeUrl('http://example.com', 'test#test'));
        self::assertEquals('http://example.com/test/test#test', UrlUtils::resolveRelativeUrl('http://example.com/', 'test/test#test'));
        self::assertEquals('http://example.com/test/test#test', UrlUtils::resolveRelativeUrl('http://example.com', 'test/test#test'));
    }

    public function testHttpQS()
    {
        self::assertEquals('http://example.com/?test=1', UrlUtils::resolveRelativeUrl('http://example.com/', '?test=1'));
        self::assertEquals('http://example.com/test?test=1', UrlUtils::resolveRelativeUrl('http://example.com/', 'test?test=1'));
        self::assertEquals('http://example.com/test?test=1', UrlUtils::resolveRelativeUrl('http://example.com', 'test?test=1'));
        self::assertEquals('http://example.com/test/test?test=1', UrlUtils::resolveRelativeUrl('http://example.com/', 'test/test?test=1'));
        self::assertEquals('http://example.com/test/test?test=1', UrlUtils::resolveRelativeUrl('http://example.com', 'test/test?test=1'));
    }

    public function testFilePaths()
    {
        self::assertEquals('file:///test', UrlUtils::resolveRelativeUrl('file:///', '/test'));
        self::assertEquals('file:///test', UrlUtils::resolveRelativeUrl('file:///', 'test'));
        /* Assert that any filenames will be stripped from base */
        self::assertEquals('file:///bar.xsd', UrlUtils::resolveRelativeUrl('file:///foo.xsd', 'bar.xsd'));
    }

    public function testRegularPaths()
    {
        self::assertEquals('/test', UrlUtils::resolveRelativeUrl('/', '/test'));
        self::assertEquals('/test', UrlUtils::resolveRelativeUrl('/', 'test'));
    }

    public function testRegularPathsParent()
    {
        self::assertEquals('/testing', UrlUtils::resolveRelativeUrl('/test/child', '../testing'));
    }
}
