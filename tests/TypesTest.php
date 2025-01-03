<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group as ElementGroup;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;

class TypesTest extends BaseTest
{
    public function getXsdBaseTypes(): array
    {
        return [['xs:dateTime'], ['xs:date'], ['xs:int']];
    }

    /**
     * @dataProvider getXsdBaseTypes
     */
    public function testPrimitiveTypes($type): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" type="' . $type . '"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');

        $elements = $complex->getElements();
        self::assertNotNull($elements[0]->getType()->getName());
        self::assertEquals($type, 'xs:' . $elements[0]->getType()->getName());
    }

    public function testAnonymousTypes(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1"></xs:element>
                    </xs:sequence>
                    <xs:attribute name="att1"></xs:attribute>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $attrs = $complex->getAttributes();
        $elements = $complex->getElements();

        self::assertEquals('anyType', $attrs[0]->getType()->getName());
        self::assertEquals('anyType', $elements[0]->getType()->getName());
    }

    public function testAttrAttr(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" nillable="true" type="xs:string" form="qualified"></xs:element>
                    </xs:sequence>
                    <xs:attribute name="att1" nillable="true" form="qualified" use="required" type="xs:string"></xs:attribute>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $attrs = $complex->getAttributes();
        self::assertTrue($attrs[0]->isNil());
        self::assertTrue($attrs[0]->isQualified());
        self::assertEquals('required', $attrs[0]->getUse());
    }

    public function testSequenceAll(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:all>
                        <xs:element name="el1" nillable="true" type="xs:string" form="qualified"></xs:element>
                        <xs:element name="el2" nillable="true" type="xs:string" form="qualified"></xs:element>
                    </xs:all>
                </xs:complexType>
            </xs:schema>');

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $elements = $complex->getElements();
        self::assertCount(2, $elements);
    }

    public function testElementAttr(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" nillable="true" type="xs:string" form="qualified"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $elements = $complex->getElements();
        self::assertTrue($elements[0]->isNil());
        self::assertTrue($elements[0]->isQualified());
    }

    public function getMaxOccurences(): array
    {
        return [
            ['1', 1],
            ['0', 0],
            ['10', 10],
            ['unbounded', -1],
        ];
    }

    /**
     * @dataProvider getMaxOccurences
     */
    public function testElementMaxOccurences($maxOccurs, $expected): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" maxOccurs="' . $maxOccurs . '" type="xs:string"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        self::assertInstanceOf(ComplexType::class, $complex);
        $elements = $complex->getElements();
        self::assertEquals($expected, $elements[0]->getMax());
    }

    /**
     * @dataProvider getMinOccurencesOverride
     */
    public function testSequenceMinOccursOverride($sequenceMinOccurs, $childMinOccurs, $expected): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence minOccurs="' . $sequenceMinOccurs . '" >
                        <xs:element name="el1" minOccurs="' . $childMinOccurs . '" type="xs:string"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        self::assertInstanceOf(ComplexType::class, $complex);
        $elements = $complex->getElements();
        self::assertEquals($expected, $elements[0]->getMin());
    }

    public function getMinOccurencesOverride(): array
    {
        return [
            ['1', '1', 1],
            ['2', '1', 2],
            ['3', '0', 3],
            ['3', '2', 3],
            ['0', '0', 0],
            [null, '0', 0],
        ];
    }

    /**
     * @dataProvider getMaxOccurencesOverride
     */
    public function testSequenceMaxOccursOverride($sequenceMaxOccurs, $childMaxOccurs, $expected): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence maxOccurs="' . $sequenceMaxOccurs . '" >
                        <xs:element name="el1" maxOccurs="' . $childMaxOccurs . '" type="xs:string"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        self::assertInstanceOf(ComplexType::class, $complex);
        $elements = $complex->getElements();
        self::assertEquals($expected, $elements[0]->getMax());
    }

    /**
     * @dataProvider getMaxOccurencesOverride
     */
    public function testSequenceChoiceMaxOccursOverride($sequenceMaxOccurs, $childMaxOccurs, $expected): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence maxOccurs="' . $sequenceMaxOccurs . '" >
                        <xs:choice>
                            <xs:element name="el1" maxOccurs="' . $childMaxOccurs . '" type="xs:string"></xs:element>
                        </xs:choice>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        self::assertInstanceOf(ComplexType::class, $complex);
        $choice = $complex->getElements()[0];
        $elements = $choice->getElements();

        self::assertEquals($expected, $elements[0]->getMax());
    }

    public function getMaxOccurencesOverride(): array
    {
        return [
            ['0', '5', 5], // maxOccurs=0 is ignored
            ['1', '5', -1],
            ['2', '5', -1],
            ['4', '5', -1],
            ['6', '5', -1],
            ['unbounded', '5', -1],
            ['5', 'unbounded', -1],
        ];
    }

    public function getMinOccurences(): array
    {
        return [
            ['1', 1],
            ['0', 0],
        ];
    }

    /**
     * @dataProvider getMinOccurences
     */
    public function testElementMinOccurences($minOccurs, $expected): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" minOccurs="' . $minOccurs . '" type="xs:string"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        self::assertInstanceOf(ComplexType::class, $complex);
        $elements = $complex->getElements();
        self::assertEquals($expected, $elements[0]->getMin());
    }

    public function testElementDefault(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element default="testDefault" name="el1" nillable="true" type="xs:string" form="qualified"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        self::assertInstanceOf(ComplexType::class, $complex);
        $elements = $complex->getElements();
        self::assertEquals('testDefault', $elements[0]->getDefault());
    }

    public function testComplex(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" type="xs:string"></xs:element>
                        <xs:element name="el2">
                        </xs:element>
                        <xs:element ref="ex:el3"></xs:element>
                        <xs:group ref="ex:g1"></xs:group>
                    </xs:sequence>
                    <xs:attribute name="att1" type="xs:string"></xs:attribute>
                    <xs:attribute name="att2"></xs:attribute>
                    <xs:attribute ref="ex:att"></xs:attribute>
                    <xs:attributeGroup ref="ex:attGroup"></xs:attributeGroup>
                </xs:complexType>

                <xs:attribute name="att" type="xs:string"></xs:attribute>
                <xs:attributeGroup name="attGroup">
                    <xs:attribute name="alone" type="xs:string"></xs:attribute>
                </xs:attributeGroup>

                <xs:element name="el3" type="xs:string"></xs:element>
                <xs:group name="g1">
                    <xs:sequence>
                        <xs:element name="aaa" type="xs:string"></xs:element>
                    </xs:sequence>
                </xs:group>

            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        self::assertInstanceOf(ComplexType::class, $complex);
        self::assertEquals('http://www.example.com', $complex->getSchema()->getTargetNamespace());
        self::assertEquals('complexType', $complex->getName());

        $elements = $complex->getElements();
        self::assertCount(4, $elements);

        self::assertInstanceOf(Element::class, $elements[0]);
        self::assertInstanceOf(Element::class, $elements[1]);
        self::assertInstanceOf(ElementItem::class, $elements[2]);
        self::assertInstanceOf(ElementGroup::class, $elements[3]);

        self::assertEquals('el1', $elements[0]->getName());
        self::assertEquals('el2', $elements[1]->getName());
        self::assertEquals('el3', $elements[2]->getName());
        self::assertEquals('g1', $elements[3]->getName());

        self::assertEquals('anyType', $elements[1]->getType()->getName());

        $attributes = $complex->getAttributes();
        self::assertCount(4, $attributes);

        self::assertInstanceOf(Attribute::class, $attributes[0]);
        self::assertInstanceOf(Attribute::class, $attributes[1]);
        self::assertInstanceOf(AttributeItem::class, $attributes[2]);
        self::assertInstanceOf(AttributeGroup::class, $attributes[3]);

        self::assertEquals('att1', $attributes[0]->getName());
        self::assertEquals('att2', $attributes[1]->getName());
        self::assertEquals('att', $attributes[2]->getName());
        self::assertEquals('attGroup', $attributes[3]->getName());

        self::assertEquals('anyType', $attributes[1]->getType()->getName());
    }

    public function testSimple(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:simpleType name="simpleType">

                </xs:simpleType>
            </xs:schema>'
        );

        $simple = $schema->findType('simpleType', 'http://www.example.com');
        self::assertInstanceOf(SimpleType::class, $simple);
        self::assertEquals('http://www.example.com', $simple->getSchema()->getTargetNamespace());
        self::assertEquals('simpleType', $simple->getName());
    }

    public function testComplexSimpleContent(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:simpleContent>
                        <xs:extension base="xs:string"></xs:extension>
                    </xs:simpleContent>
                    <xs:attribute name="att1" type="xs:string"></xs:attribute>
                    <xs:attribute name="att2"></xs:attribute>
                    <xs:attribute ref="ex:att"></xs:attribute>
                    <xs:attributeGroup ref="ex:attGroup"></xs:attributeGroup>
                </xs:complexType>

                <xs:attribute name="att" type="xs:string"></xs:attribute>
                <xs:attributeGroup name="attGroup">
                    <xs:attribute name="alone" type="xs:string"></xs:attribute>
                </xs:attributeGroup>

            </xs:schema>'
        );

        $complex = $schema->findType('complexType', 'http://www.example.com');
        self::assertInstanceOf(ComplexTypeSimpleContent::class, $complex);
        self::assertEquals('http://www.example.com', $complex->getSchema()->getTargetNamespace());
        self::assertEquals('complexType', $complex->getName());

        $extension = $complex->getExtension();
        $base1 = $extension->getBase();
        self::assertInstanceOf(SimpleType::class, $base1);
        self::assertEquals('http://www.w3.org/2001/XMLSchema', $base1->getSchema()->getTargetNamespace());
        self::assertEquals('string', $base1->getName());

        $attributes = $complex->getAttributes();
        self::assertCount(4, $attributes);

        self::assertInstanceOf(Attribute::class, $attributes[0]);
        self::assertInstanceOf(Attribute::class, $attributes[1]);
        self::assertInstanceOf(AttributeItem::class, $attributes[2]);
        self::assertInstanceOf(AttributeGroup::class, $attributes[3]);

        self::assertEquals('att1', $attributes[0]->getName());
        self::assertEquals('att2', $attributes[1]->getName());
        self::assertEquals('att', $attributes[2]->getName());
        self::assertEquals('attGroup', $attributes[3]->getName());

        self::assertEquals('anyType', $attributes[1]->getType()->getName());
    }
}
