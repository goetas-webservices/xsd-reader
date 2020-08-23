<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * @var SchemaReader
     */
    protected $reader;

    public function setUp(): void
    {
        $this->reader = new SchemaReader();
        error_reporting(error_reporting() & ~E_NOTICE);
    }
}
