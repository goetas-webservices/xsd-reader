<?php
namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Schema;

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

    public function testFindElementInNestedSchemas()
    {
        $schemaRoot = new Schema();
        $schema = new Schema();

        $schema->addSchema($this->reader->readString('
            <xs:schema xmlns:tns="http://www.my-company-domain.com/" 
                    xmlns:xs="http://www.w3.org/2001/XMLSchema" 
                    version="1.0"
                    targetNamespace="http://www.my-company-domain.com/">
                <xs:element name="getCatalog" type="tns:getCatalog" />
                <xs:complexType name="getCatalog">
                    <xs:sequence>
                    <xs:element name="arg0" type="xs:string" minOccurs="0" />
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>'));

        $schemaRoot->addSchema($schema);

        $this->assertInstanceOf(
            'GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef',
            $schemaRoot->findElement('getCatalog', 'http://www.my-company-domain.com/')
        );
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
}
