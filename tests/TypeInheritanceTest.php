<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;

class TypeInheritanceTest extends BaseTest
{
    public function testInheritanceWithExtension(): void
    {
        $schema = $this->reader->readString(
            '
             <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:complexType name="complexType-1">
                     <xs:attribute name="attribute-2" type="xs:string"/>
                     <xs:sequence>
                            <xs:element name="complexType-1-el-1" type="xs:string"/>
                     </xs:sequence>
                </xs:complexType>
                <xs:complexType name="complexType-2">
                     <xs:complexContent>
                        <xs:extension base="ex:complexType-1">
                             <xs:sequence>
                                <xs:element name="complexType-2-el1" type="xs:string"></xs:element>
                            </xs:sequence>
                            <xs:attribute name="complexType-2-att1" type="xs:string"></xs:attribute>
                        </xs:extension>
                    </xs:complexContent>
                </xs:complexType>
            </xs:schema>
            '
        );
        self::assertInstanceOf(ComplexType::class, $type1 = $schema->findType('complexType-1', 'http://www.example.com'));
        self::assertInstanceOf(ComplexType::class, $type2 = $schema->findType('complexType-2', 'http://www.example.com'));

        self::assertSame($type1, $type2->getExtension()->getBase());

        $elements = $type2->getElements();
        $attributes = $type2->getAttributes();
        self::assertInstanceOf(Element::class, $elements[0]);
        self::assertInstanceOf(Attribute::class, $attributes[0]);

        self::assertEquals('complexType-2-el1', $elements[0]->getName());
        self::assertEquals('complexType-2-att1', $attributes[0]->getName());
    }

    public function testBase(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:simpleType name="mySimple">
                    <xs:restriction base="xs:string"></xs:restriction>
                </xs:simpleType>

                <xs:simpleType name="mySimpleWithRestr">
                    <xs:restriction base="ex:mySimple"></xs:restriction>
                </xs:simpleType>

                <xs:complexType name="myComplex">
                    <xs:simpleContent>
                        <xs:extension base="ex:mySimpleWithRestr"></xs:extension>
                    </xs:simpleContent>
                </xs:complexType>

                <xs:simpleType name="mySimpleWithUnion">
                    <xs:union memberTypes="xs:string ex:mySimpleWithRestr"></xs:union>
                </xs:simpleType>

            </xs:schema>'
        );

        self::assertCount(4, $schema->getTypes());
        self::assertInstanceOf(SimpleType::class, $type = $schema->findType('mySimple', 'http://www.example.com'));
        self::assertInstanceOf(SimpleType::class, $type2 = $schema->findType('mySimpleWithRestr', 'http://www.example.com'));
        self::assertInstanceOf(ComplexTypeSimpleContent::class, $type3 = $schema->findType('myComplex', 'http://www.example.com'));
        self::assertInstanceOf(SimpleType::class, $type4 = $schema->findType('mySimpleWithUnion', 'http://www.example.com'));

        $restriction1 = $type->getRestriction();
        $base1 = $restriction1->getBase();
        self::assertInstanceOf(SimpleType::class, $base1);
        self::assertEquals('http://www.w3.org/2001/XMLSchema', $base1->getSchema()->getTargetNamespace());
        self::assertEquals('string', $base1->getName());

        $restriction2 = $type2->getRestriction();
        $base2 = $restriction2->getBase();
        self::assertInstanceOf(SimpleType::class, $base2);
        self::assertEquals('http://www.example.com', $base2->getSchema()->getTargetNamespace());
        self::assertEquals('mySimple', $base2->getName());

        $extension = $type3->getExtension();
        $base3 = $extension->getBase();
        self::assertInstanceOf(SimpleType::class, $base3);
        self::assertEquals('http://www.example.com', $base3->getSchema()->getTargetNamespace());
        self::assertEquals('mySimpleWithRestr', $base3->getName());

        $unions = $type4->getUnions();
        self::assertCount(2, $unions);
        self::assertInstanceOf(SimpleType::class, $unions[0]);
        self::assertInstanceOf(SimpleType::class, $unions[1]);

        self::assertEquals('http://www.w3.org/2001/XMLSchema', $unions[0]->getSchema()->getTargetNamespace());
        self::assertEquals('string', $unions[0]->getName());

        self::assertEquals('http://www.example.com', $unions[1]->getSchema()->getTargetNamespace());
        self::assertEquals('mySimpleWithRestr', $unions[1]->getName());
    }

    public function testAnonyeExtension(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:simpleType name="myType">
                    <xs:restriction>
                        <xs:simpleType>
                            <xs:restriction base="xs:string"></xs:restriction>
                        </xs:simpleType>
                    </xs:restriction>
                </xs:simpleType>

            </xs:schema>'
        );

        self::assertCount(1, $schema->getTypes());
        self::assertInstanceOf(SimpleType::class, $type = $schema->findType('myType', 'http://www.example.com'));

        $restriction = $type->getRestriction();
        $base = $restriction->getBase();
        self::assertInstanceOf(SimpleType::class, $base);
        self::assertEquals('http://www.example.com', $base->getSchema()->getTargetNamespace());
        self::assertTrue(!$base->getName());

        $restriction2 = $base->getRestriction();
        $base2 = $restriction2->getBase();
        self::assertInstanceOf(SimpleType::class, $base2);
        self::assertEquals('http://www.w3.org/2001/XMLSchema', $base2->getSchema()->getTargetNamespace());
        self::assertEquals('string', $base2->getName());
    }

    public function testAnonymUnion(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com"
            xmlns:xs="http://www.w3.org/2001/XMLSchema"
            xmlns:ex="http://www.example.com">

                <xs:simpleType name="myType">
                    <xs:restriction base="xs:string"></xs:restriction>
                </xs:simpleType>

                <xs:simpleType name="myAnonUnion">
                    <xs:union>
                        <xs:simpleType><xs:restriction base="xs:string"></xs:restriction></xs:simpleType>
                        <xs:simpleType><xs:restriction base="ex:myType"></xs:restriction></xs:simpleType>
                    </xs:union>
                </xs:simpleType>

            </xs:schema>'
        );

        self::assertInstanceOf(SimpleType::class, $type = $schema->findType('myAnonUnion', 'http://www.example.com'));

        $unions = $type->getUnions();

        self::assertCount(2, $unions);
        self::assertInstanceOf(SimpleType::class, $unions[0]);
        self::assertInstanceOf(SimpleType::class, $unions[1]);

        self::assertEquals('http://www.example.com', $unions[0]->getSchema()->getTargetNamespace());
        self::assertTrue(!$unions[0]->getName());

        self::assertEquals('http://www.example.com', $unions[1]->getSchema()->getTargetNamespace());
        self::assertTrue(!$unions[1]->getName());

        $restriction1 = $unions[0]->getRestriction();
        $base1 = $restriction1->getBase();
        self::assertInstanceOf(SimpleType::class, $base1);
        self::assertEquals('http://www.w3.org/2001/XMLSchema', $base1->getSchema()->getTargetNamespace());
        self::assertEquals('string', $base1->getName());

        $restriction2 = $unions[1]->getRestriction();
        $base2 = $restriction2->getBase();
        self::assertInstanceOf(SimpleType::class, $base2);
        self::assertEquals('http://www.example.com', $base2->getSchema()->getTargetNamespace());
        self::assertEquals('myType', $base2->getName());
    }
}
