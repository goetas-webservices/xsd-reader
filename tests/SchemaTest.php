<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Exception\IOException;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;

class SchemaTest extends BaseTest
{
    public function testWithXSDAsDefaultNamespace()
    {
        $xml = '
        <schema xmlns="http://www.w3.org/2001/XMLSchema"
            xmlns:ds="http://www.example.com"
            targetNamespace="http://www.example.com"
            elementFormDefault="qualified">

            <simpleType name="CryptoBinary">
              <restriction base="base64Binary"/>
            </simpleType>
            <simpleType name="LocalCryptoBinary">
              <restriction base="ds:CryptoBinary"/>
            </simpleType>
        </schema>';
        $schema = $this->reader->readString($xml);

        $crypto = $schema->findType('CryptoBinary', 'http://www.example.com');
        $this->assertInstanceOf(SimpleType::class, $crypto);

        $localCrypto = $schema->findType('LocalCryptoBinary', 'http://www.example.com');
        $this->assertInstanceOf(SimpleType::class, $localCrypto);
    }

    /**
     * @throws \GoetasWebservices\XML\XSDReader\Exception\IOException
     */
    public function testErrorString()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Can't load the schema");
        $this->reader->readString('abcd');
    }

    /**
     * @throws \GoetasWebservices\XML\XSDReader\Exception\IOException
     */
    public function testErrorFile()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Can't load the file 'abcd'");
        $this->reader->readFile('abcd');
    }

    public function testBaseEmpty()
    {
        $schema = $this->reader->readString('<xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"/>');

        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Schema', $schema);
        $this->assertEquals('http://www.example.com', $schema->getTargetNamespace());

        $schemas = $schema->getSchemas();

        $this->assertFalse(empty($schemas));
    }

    public function getTypesToSearch()
    {
        return [
            ['findType'],
            ['findElement'],
            ['findAttribute'],
            ['findAttributeGroup'],
            ['findGroup'],
        ];
    }

    /**
     * @dataProvider getTypesToSearch
     */
    public function testNotFoundType($find)
    {
        $schema = $this->reader->readString('<xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"/>');

        $this->expectException('GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException');
        $schema->$find('foo');
    }

    public function testBase()
    {
        $schema = $this->reader->readString('
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="myType"></xs:complexType>
                <xs:element name="myElement" type="myType"></xs:element>

                <xs:group name="myGroup">
                    <xs:sequence></xs:sequence>
                </xs:group>
                <xs:attribute name="myAttribute" type="xs:string"></xs:attribute>
                <xs:attributeGroup name="myAttributeGroup"></xs:attributeGroup>
            </xs:schema>');

        $this->assertCount(1, $schema->getTypes());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType', $schema->findType('myType', 'http://www.example.com'));
        //$this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType', $schema->findType('myType'));

        $this->assertCount(1, $schema->getElements());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $schema->findElement('myElement', 'http://www.example.com'));

        $this->assertCount(1, $schema->getGroups());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\Group', $schema->findGroup('myGroup', 'http://www.example.com'));

        $this->assertCount(1, $schema->getAttributeGroups());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\Group', $schema->findAttributeGroup('myAttributeGroup', 'http://www.example.com'));

        $this->assertCount(1, $schema->getAttributes());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef', $schema->findAttribute('myAttribute', 'http://www.example.com'));
    }

    public function testMultipleSchemasInSameFile()
    {
        $file = 'schema.xsd';
        $schema1 = $this->reader->readString('
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="myType"></xs:complexType>
                <xs:element name="myElement" type="myType"></xs:element>

                <xs:group name="myGroup">
                    <xs:sequence></xs:sequence>
                </xs:group>
                <xs:attribute name="myAttribute" type="xs:string"></xs:attribute>
                <xs:attributeGroup name="myAttributeGroup"></xs:attributeGroup>
            </xs:schema>',
            $file
        );

        $this->assertCount(1, $schema1->getTypes());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType', $schema1->findType('myType', 'http://www.example.com'));

        $this->assertCount(1, $schema1->getElements());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $schema1->findElement('myElement', 'http://www.example.com'));

        //Now use a second schema which imports from the first one, and is in the SAME file
        $schema2 = $this->reader->readString('
            <xs:schema targetNamespace="http://www.example2.com" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:ns1="http://www.example.com">
                <xs:import namespace="http://www.example.com"/>
                <xs:element name="myElement2" type="ns1:myType"></xs:element>
            </xs:schema>',
            $file
        );

        $this->assertCount(0, $schema2->getTypes());

        $this->assertCount(1, $schema2->getElements());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $schema2->findElement('myElement2', 'http://www.example2.com'));
    }

    public function testMultipleSchemasInSameFileWithSameTargetNamespace()
    {
        $file = 'schema.xsd';
        $schema1 = $this->reader->readString('
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="myType"></xs:complexType>
                <xs:element name="myElement" type="myType"></xs:element>
            </xs:schema>',
            $file
        );

        $this->assertCount(1, $schema1->getTypes());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType', $schema1->findType('myType', 'http://www.example.com'));

        $this->assertCount(1, $schema1->getElements());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $schema1->findElement('myElement', 'http://www.example.com'));

        //Now use a second schema which uses the same targetNamespace
        $schema2 = $this->reader->readString('
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:import namespace="http://www.example.com"/>
                <xs:element name="myElement2" type="ns1:myType"></xs:element>
            </xs:schema>',
            $file
        );

        $schema1->addSchema($schema2);

        $this->assertCount(0, $schema2->getTypes());
        $this->assertCount(1, $schema2->getElements());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $schema2->findElement('myElement2', 'http://www.example.com'));

        $this->assertCount(1, $schema1->getTypes());
        $this->assertCount(1, $schema2->getElements());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $schema1->findElement('myElement2', 'http://www.example.com'));
    }

    public function testGroupRefInType()
    {
        $schema1 = $this->reader->readString('
        <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="myElement">
                <xs:complexType>
                    <xs:group ref="myGroup"/>
                </xs:complexType>
            </xs:element>
            <xs:group name="myGroup">
                <xs:choice>
                    <xs:element name="groupElement" type="xs:string"/>
                </xs:choice>
            </xs:group>
        </xs:schema>');

        $this->assertCount(1, $schema1->getGroups());
        $group = $schema1->findGroup('myGroup', 'http://www.example.com');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\Group', $group);

        $this->assertCount(1, $schema1->getElements());

        /**
         * @var ElementDef
         * @var $type      ComplexType
         */
        $element = $schema1->findElement('myElement', 'http://www.example.com');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $element);

        $type = $element->getType();
        $this->assertCount(1, $type->getElements());

        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef', $type->getElements()[0]);
        $this->assertEquals($group->getElements(), $type->getElements()[0]->getElements());
    }

    public function testDependentReferencingSchemes()
    {
        $dom = new \DOMDocument();
        $dom->loadXML('
        <types xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:schema targetNamespace="http://tempuri.org/1">
                <xs:complexType name="Categories" mixed="true">
                    <xs:sequence>
                        <xs:element minOccurs="0" maxOccurs="unbounded" name="Category" type="xs:string" />
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            <xs:schema targetNamespace="http://tempuri.org/2">
                <xs:element name="CategoryList">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element minOccurs="1" maxOccurs="1" name="result" type="q1:Categories" xmlns:q1="http://tempuri.org/1" />
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>
        </types>
        ');
        $schema = $this->reader->readNodes(iterator_to_array($dom->documentElement->childNodes), 'file.xsd');

        $this->assertInstanceOf(ElementDef::class, $schema->findElement('CategoryList', 'http://tempuri.org/2'));
        $this->assertInstanceOf(ComplexType::class, $schema->findType('Categories', 'http://tempuri.org/1'));
    }
}
