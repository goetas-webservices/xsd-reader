<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;

class LocalTest extends BaseTest
{
    public function testSchemaCanBeLoaded(): void
    {
        $schema = $this->reader->readFile(__DIR__ . '/schema/local.xsd');
        self::assertInstanceOf(Schema::class, $schema);
    }

    public function testTypesLocalPropertyIsValid(): void
    {
        $schema = $this->reader->readFile(__DIR__ . '/schema/local.xsd');
        self::assertInstanceOf(Schema::class, $schema);

        $type1 = $schema->getType('type1');
        self::assertInstanceOf(ComplexType::class, $type1);

        $localElements = $type1->getElements();
        self::assertCount(1, $localElements);
        /** @var ElementRef $localElement */
        $localElement = $localElements[0];

        self::assertInstanceOf(ElementRef::class, $localElement);
        self::assertFalse($localElement->isLocal());

        $referencedElement = $localElement->getReferencedElement();
        self::assertNotNull($referencedElement);

        /** @var ComplexType $referencedElementType */
        $referencedElementType = $referencedElement->getType();
        self::assertNotNull($referencedElementType);

        self::assertInstanceOf(ComplexType::class, $referencedElementType);
        $elements = $referencedElementType->getElements();
        self::assertCount(1, $elements);

        $dataElement = $elements[0];
        self::assertInstanceOf(Element::class, $dataElement);

        self::assertEquals('data', $dataElement->getName());
        self::assertTrue($dataElement->isLocal());
    }
}
