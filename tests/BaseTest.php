<?php
namespace Goetas\XML\XSDReader\Tests;

use Goetas\XML\XSDReader\SchemaReader;
abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var SchemaReader
     */
    protected $reader;
    public function setUp()
    {
        $this->reader = new SchemaReader();
        error_reporting(error_reporting() &~E_NOTICE);
    }
}