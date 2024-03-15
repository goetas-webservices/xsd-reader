<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;

class FilesystemTest extends BaseTest
{
    /**
     * Test that a referenced Xsd file is found when the base-path contains " " (space-character).
     *
     * Covers the issue described in {@link https://github.com/goetas/xsd-reader/pull/10 PR #10}.
     */
    public function testReferencedOnFileSystem1(): void
    {
        /*
         * Using vfsStream seems ideal, but currently seems to have an issue with directorypaths with a space in
         * combination with DOMDocument::load(). For now use actual filesystem to create the testcase.
         */
        $schemaXsd = __DIR__ . DIRECTORY_SEPARATOR . 'foo bar' . DIRECTORY_SEPARATOR . 'schema.xsd';

        $schema = $this->reader->readFile($schemaXsd);

        self::assertCount(1, $schema->getTypes());
        self::assertInstanceOf(ComplexType::class, $schema->findType('myType', 'http://www.example.com'));
    }
}
