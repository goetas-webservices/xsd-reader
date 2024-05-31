<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeRef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeSingle;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class AttributesTest extends BaseTest
{
    public function testBase(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:attribute name="myAttribute" type="xs:string"></xs:attribute>
                <xs:attribute name="myAttributeOptions" type="xs:string" use="required" nil="true"></xs:attribute>

                <xs:attributeGroup name="myAttributeGroup">
                    <xs:attribute name="alone" type="xs:string"></xs:attribute>
                    <xs:attribute ref="ex:myAttribute"></xs:attribute>
                    <xs:attributeGroup ref="ex:myAttributeGroup2"></xs:attributeGroup>
                </xs:attributeGroup>

                <xs:attributeGroup name="myAttributeGroup2">
                    <xs:attribute name="alone2" type="xs:string"></xs:attribute>
                </xs:attributeGroup>
            </xs:schema>'
        );

        $myAttribute = $schema->findAttribute('myAttribute', 'http://www.example.com');
        self::assertInstanceOf(AttributeDef::class, $myAttribute);
        // self::assertEquals('http://www.example.com', $myAttribute->getSchema()->getTargetNamespace());
        self::assertEquals('myAttribute', $myAttribute->getName());
        self::assertEquals('string', $myAttribute->getType()->getName());

        $base1 = $myAttribute->getType();
        self::assertInstanceOf(SimpleType::class, $base1);
        self::assertEquals('http://www.w3.org/2001/XMLSchema', $base1->getSchema()->getTargetNamespace());
        self::assertEquals('string', $base1->getName());

        $myAttributeGroup = $schema->findAttributeGroup('myAttributeGroup', 'http://www.example.com');
        self::assertInstanceOf(Group::class, $myAttributeGroup);
        // self::assertEquals('http://www.example.com', $myAttribute->getSchema()->getTargetNamespace());
        self::assertEquals('myAttributeGroup', $myAttributeGroup->getName());
        $attributesInGroup = $myAttributeGroup->getAttributes();
        self::assertCount(3, $attributesInGroup);

        self::assertInstanceOf(Attribute::class, $attributesInGroup[0]);
        self::assertInstanceOf(AttributeRef::class, $attributesInGroup[1]);
        self::assertInstanceOf(AttributeDef::class, $attributesInGroup[1]->getReferencedAttribute());
        self::assertInstanceOf(Group::class, $attributesInGroup[2]);

        $myAttribute = $schema->findAttribute('myAttributeOptions', 'http://www.example.com');
        self::assertInstanceOf(AttributeDef::class, $myAttribute);
        // self::assertEquals('http://www.example.com', $myAttribute->getSchema()->getTargetNamespace());
        self::assertEquals('myAttributeOptions', $myAttribute->getName());
        self::assertEquals('string', $myAttribute->getType()->getName());
    }

    public function testAnonym(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:attribute name="myAttributeAnonType">
                    <xs:simpleType>
                        <xs:restriction base="xs:string"></xs:restriction>
                    </xs:simpleType>
                </xs:attribute>

            </xs:schema>'
        );

        $myAttributeAnon = $schema->findAttribute('myAttributeAnonType', 'http://www.example.com');
        self::assertInstanceOf(AttributeDef::class, $myAttributeAnon);
        // self::assertEquals('http://www.example.com', $myAttribute->getSchema()->getTargetNamespace());
        self::assertEquals('myAttributeAnonType', $myAttributeAnon->getName());
        self::assertNull($myAttributeAnon->getType()->getName());

        $base2 = $myAttributeAnon->getType();
        self::assertInstanceOf(SimpleType::class, $base2);
        self::assertEquals('http://www.example.com', $base2->getSchema()->getTargetNamespace());
        self::assertTrue(!$base2->getName());

        $restriction1 = $base2->getRestriction();
        $base3 = $restriction1->getBase();
        self::assertInstanceOf(SimpleType::class, $base3);
        self::assertEquals('http://www.w3.org/2001/XMLSchema', $base3->getSchema()->getTargetNamespace());
        self::assertEquals('string', $base3->getName());
    }

    public function testAttributeUseOverriding(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:attribute name="lang" use="optional" type="xs:language"/>
                <xs:element name="Name">
                    <xs:complexType mixed="true">
                        <xs:attribute ref="lang" use="required"/>
                    </xs:complexType>
                </xs:element>
                <xs:complexType name="MyNameType">
                    <xs:sequence>
                        <xs:element ref="Name"/>
                    </xs:sequence>
                </xs:complexType>
                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="myName" type="MyNameType"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];

        self::assertEquals('myName', $element->getName());
        $elementType = $element->getType();
        self::assertEquals('MyNameType', $elementType->getName());

        self::assertCount(1, $elementType->getElements());
        $subElement = $elementType->getElements()[0];
        self::assertEquals('Name', $subElement->getName());
        $subElementType = $subElement->getType();
        self::assertInstanceOf(ComplexType::class, $subElementType);
        self::assertTrue($subElementType->isMixed());

        self::assertCount(1, $subElementType->getAttributes());
        $attribute = $subElementType->getAttributes()[0];
        self::assertEquals('lang', $attribute->getName());
        self::assertEquals('language', $attribute->getType()->getName());
        self::assertEquals(AttributeSingle::USE_REQUIRED, $attribute->getUse());
    }

    public function testMetaInformation(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:tns="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:attribute name="myAttribute" type="xs:string" tns:meta="hello" />
            </xs:schema>'
        );

        $myAttribute = $schema->findAttribute('myAttribute', 'http://www.example.com');
        self::assertInstanceOf(AttributeDef::class, $myAttribute);

        $meta = $myAttribute->getMeta();
        self::assertCount(1, $meta);
        self::assertEquals('meta', $meta[0]->getName());
        self::assertEquals('hello', $meta[0]->getValue());
        self::assertEquals('http://www.example.com', $meta[0]->getNamespaceURI());
        self::assertSame(SchemaReader::XSD_NS, $meta[0]->getContextSchema()->getTargetNamespace());
    }

    public function testExternalSchemaReferencingMetaInformationPrefixed(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML(
            '
            <types xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:schema targetNamespace="http://www.ref.com">
                    <xs:attribute name="metaType" type="xs:string" />
                </xs:schema>
                <xs:schema targetNamespace="http://www.example.com" xmlns:ref="http://www.ref.com">
                    <xs:import namespace="http://www.ref.com" />
                    <xs:attribute name="myAttribute" type="xs:string" ref:metaType="xs:string" />
                </xs:schema>
            </types>
        ');
        $schema = $this->reader->readNodes(iterator_to_array($dom->documentElement->childNodes), 'file.xsd');

        $myAttribute = $schema->findAttribute('myAttribute', 'http://www.example.com');
        self::assertInstanceOf(AttributeDef::class, $myAttribute);

        $meta = $myAttribute->getMeta();
        self::assertCount(1, $meta);
        self::assertEquals('metaType', $meta[0]->getName());
        self::assertEquals('xs:string', $meta[0]->getValue());

        $refAttr = $schema->findAttribute('metaType', 'http://www.ref.com');
        self::assertSame($refAttr->getSchema()->getTargetNamespace(), $meta[0]->getNamespaceURI());
        self::assertSame(SchemaReader::XSD_NS, $meta[0]->getContextSchema()->getTargetNamespace());
    }

    public function testExternalSchemaReferencingMetaInformationUnprefixed(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML(
            '
            <types xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:schema targetNamespace="http://www.ref.com">
                    <xs:attribute name="metaType" type="xs:string" />
                </xs:schema>
                <schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="http://www.example.com" xmlns:ref="http://www.ref.com">
                    <import namespace="http://www.ref.com" />
                    <attribute name="myAttribute" type="string" ref:metaType="string" />
                </schema>
            </types>
        ');
        $schema = $this->reader->readNodes(iterator_to_array($dom->documentElement->childNodes), 'file.xsd');

        $myAttribute = $schema->findAttribute('myAttribute', 'http://www.example.com');
        self::assertInstanceOf(AttributeDef::class, $myAttribute);

        $meta = $myAttribute->getMeta();

        self::assertCount(1, $meta);
        self::assertEquals('metaType', $meta[0]->getName());
        self::assertEquals('string', $meta[0]->getValue());

        $refAttr = $schema->findAttribute('metaType', 'http://www.ref.com');
        self::assertSame($refAttr->getSchema()->getTargetNamespace(), $meta[0]->getNamespaceURI());
        self::assertSame(SchemaReader::XSD_NS, $meta[0]->getContextSchema()->getTargetNamespace());
    }
}
