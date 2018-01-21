<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Schema;

class ImportTest extends BaseTest
{
    public function testBase()
    {
        $remoteSchema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:attribute name="myAttribute" type="xs:string"></xs:attribute>
            </xs:schema>', 'http://www.example.com/xsd.xsd');

        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.user.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:import schemaLocation="http://www.example.com/xsd.xsd" namespace="http://www.example.com"></xs:import>

                <xs:attributeGroup name="myAttributeGroup">
                    <xs:attribute ref="ex:myAttribute"></xs:attribute>
                </xs:attributeGroup>

            </xs:schema>');

        $this->assertContains($remoteSchema, $schema->getSchemas(), false, true);

        $remoteAttr = $remoteSchema->findAttribute('myAttribute', 'http://www.example.com');
        $localAttr = $schema->findAttribute('myAttribute', 'http://www.example.com');

        $this->assertSame($remoteAttr, $localAttr);

        $localAttrGroup = $schema->findAttributeGroup('myAttributeGroup', 'http://www.user.com');
        $localAttrs = $localAttrGroup->getAttributes();

        $this->assertSame($remoteAttr, $localAttrs[0]);
    }

    public function testKnownImport()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.user.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:import namespace="http://www.w3.org/2001/XMLSchema"/>
                 <xs:complexType name="t">
                    <xs:attribute name="att"/>
                </xs:complexType>
            </xs:schema>');
        $this->assertInstanceOf(Schema::class, $schema);
    }

    public function testKnownLocationImport()
    {
        $this->reader->addKnownSchemaLocation('http://www.example.com/test.xsd', __DIR__.'/schema/test.xsd');

        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.user.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:import namespace="http://www.w3.org/2001/XMLSchema" schemaLocation="http://www.example.com/test.xsd"/>
                 <xs:complexType name="out">
                    <xs:sequence>
                        <xs:element type="ex:in" name="foo"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>');
        $this->assertInstanceOf(Schema::class, $schema);
    }

    public function testBaseNode()
    {
        $dom = new \DOMDocument();
        $dom->loadXML('
        <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:attribute name="myAttribute" type="xs:string"></xs:attribute>
        </xs:schema>
        ');
        $schema = $this->reader->readNode($dom->documentElement);
        $attr = $schema->findAttribute('myAttribute', 'http://www.example.com');

        $this->assertInstanceOf(AttributeItem::class, $attr);
    }

    public function testDependentImport()
    {
        $dom = new \DOMDocument();
        $dom->loadXML('
        <types xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:schema targetNamespace="http://tempuri.org/1" xmlns:t2="http://tempuri.org/2">
                <xs:import namespace="http://tempuri.org/2"/>
                <xs:element name="outerEl" type="t2:inner"/>
            </xs:schema>
            <xs:schema targetNamespace="http://tempuri.org/2">
                <xs:complexType name="inner">
                    <xs:attribute name="inner_attr"/>
                </xs:complexType>
            </xs:schema>
        </types>
        ');
        $schema = $this->reader->readNodes(iterator_to_array($dom->documentElement->childNodes), 'file.xsd');

        $this->assertInstanceOf(ElementDef::class, $schema->findElement('outerEl', 'http://tempuri.org/1'));
    }
}
