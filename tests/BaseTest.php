<?php

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * @var SchemaReader
     */
    protected $reader;

    public function setUp()
    {
        $this->reader = new SchemaReader();
        error_reporting(error_reporting() & ~E_NOTICE);
    }
}
