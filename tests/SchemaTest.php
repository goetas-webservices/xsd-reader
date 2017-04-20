<?php
namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;

class SchemaTest extends BaseTest
{
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

        $this->setExpectedException('GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException');
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
         * @var $element ElementDef
         * @var $type ComplexType
         */
        $element = $schema1->findElement('myElement', 'http://www.example.com');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $element);

        $type = $element->getType();
        $this->assertCount(1, $type->getElements());

        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef', $type->getElements()[0]);
        $this->assertEquals($group->getElements(), $type->getElements()[0]->getElements());
    }
}
