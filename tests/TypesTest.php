<?php
namespace GoetasWebservices\XML\XSDReader\Tests;

class TypesTest extends BaseTest
{
    public function getXsdBaseTypes()
    {
        return [['xs:dateTime'], ['xs:date'], ['xs:int']];
    }

    /**
     * @dataProvider getXsdBaseTypes
     */
    public function testPrimitiveTypes($type)
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" type="' . $type . '"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>');


        $complex = $schema->findType('complexType', 'http://www.example.com');

        $elements = $complex->getElements();
        $this->assertNotNull($elements[0]->getType()->getName());
        $this->assertEquals($type, "xs:" . $elements[0]->getType()->getName());
    }

    public function testAnonymousTypes()
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
            </xs:schema>');


        $complex = $schema->findType('complexType', 'http://www.example.com');
        $attrs = $complex->getAttributes();
        $elements = $complex->getElements();

        $this->assertEquals("anyType", $attrs[0]->getType()->getName());
        $this->assertEquals("anyType", $elements[0]->getType()->getName());

    }

    public function testAttrAttr()
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
            </xs:schema>');

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $attrs = $complex->getAttributes();
        $this->assertTrue($attrs[0]->isNil());
        $this->assertTrue($attrs[0]->isQualified());
        $this->assertEquals('required', $attrs[0]->getUse());
    }

    public function testSequenceAll()
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
        $this->assertCount(2, $elements);
    }

    public function testElementAttr()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" nillable="true" type="xs:string" form="qualified"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>');

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $elements = $complex->getElements();
        $this->assertTrue($elements[0]->isNil());
        $this->assertTrue($elements[0]->isQualified());
    }

    public function getMaxOccurences()
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
    public function testElementMaxOccurences($xml, $expected)
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" maxOccurs="' . $xml . '" type="xs:string"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>');

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $elements = $complex->getElements();
        $this->assertEquals($expected, $elements[0]->getMax());
    }

    public function getMinOccurences()
    {
        return [
            ['1', 1],
            ['0', 0],
        ];
    }

    /**
     * @dataProvider getMinOccurences
     */
    public function testElementMinOccurences($xml, $expected)
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" minOccurs="' . $xml . '" type="xs:string"></xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>');

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $elements = $complex->getElements();
        $this->assertEquals($expected, $elements[0]->getMin());
    }

    public function testComplex()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="complexType">
                    <xs:sequence>
                        <xs:element name="el1" type="xs:string"></xs:element>
                        <xs:element name="el2">
                        </xs:element>
                        <xs:element ref="ex:el3"></xs:element>
                        <xs:group ref="g1"></xs:group>
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

            </xs:schema>');

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType', $complex);
        $this->assertEquals('http://www.example.com', $complex->getSchema()->getTargetNamespace());
        $this->assertEquals('complexType', $complex->getName());

        $elements = $complex->getElements();
        $this->assertCount(4, $elements);

        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\Element', $elements[0]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\Element', $elements[1]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem', $elements[2]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\Group', $elements[3]);

        $this->assertEquals('el1', $elements[0]->getName());
        $this->assertEquals('el2', $elements[1]->getName());
        $this->assertEquals('el3', $elements[2]->getName());
        $this->assertEquals('g1', $elements[3]->getName());

        $this->assertEquals("anyType", $elements[1]->getType()->getName());

        $attributes = $complex->getAttributes();
        $this->assertCount(4, $attributes);

        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute', $attributes[0]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute', $attributes[1]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem', $attributes[2]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\Group', $attributes[3]);

        $this->assertEquals('att1', $attributes[0]->getName());
        $this->assertEquals('att2', $attributes[1]->getName());
        $this->assertEquals('att', $attributes[2]->getName());
        $this->assertEquals('attGroup', $attributes[3]->getName());

        $this->assertEquals("anyType", $attributes[1]->getType()->getName());

    }

    public function testSimple()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:simpleType name="simpleType">

                </xs:simpleType>
            </xs:schema>');

        $simple = $schema->findType('simpleType', 'http://www.example.com');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $simple);
        $this->assertEquals('http://www.example.com', $simple->getSchema()->getTargetNamespace());
        $this->assertEquals('simpleType', $simple->getName());

    }

    public function testComplexSimpleContent()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
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

            </xs:schema>');

        $complex = $schema->findType('complexType', 'http://www.example.com');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent', $complex);
        $this->assertEquals('http://www.example.com', $complex->getSchema()->getTargetNamespace());
        $this->assertEquals('complexType', $complex->getName());

        $extension = $complex->getExtension();
        $base1 = $extension->getBase();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base1);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema', $base1->getSchema()->getTargetNamespace());
        $this->assertEquals('string', $base1->getName());


        $attributes = $complex->getAttributes();
        $this->assertCount(4, $attributes);

        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute', $attributes[0]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute', $attributes[1]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem', $attributes[2]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\Group', $attributes[3]);

        $this->assertEquals('att1', $attributes[0]->getName());
        $this->assertEquals('att2', $attributes[1]->getName());
        $this->assertEquals('att', $attributes[2]->getName());
        $this->assertEquals('attGroup', $attributes[3]->getName());

        $this->assertEquals("anyType", $attributes[1]->getType()->getName());

    }
}