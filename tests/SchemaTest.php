<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Exception\IOException;
use GoetasWebservices\XML\XSDReader\Exception\TypeException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group as ElementGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
use GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;

class SchemaTest extends BaseTest
{
    public function testWithXSDAsDefaultNamespace(): void
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
        self::assertInstanceOf(SimpleType::class, $crypto);

        $localCrypto = $schema->findType('LocalCryptoBinary', 'http://www.example.com');
        self::assertInstanceOf(SimpleType::class, $localCrypto);
    }

    /**
     * @throws IOException
     */
    public function testErrorString(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Can't load the schema");
        $this->reader->readString('abcd');
    }

    /**
     * @throws IOException
     */
    public function testErrorFile(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Can't load the file 'abcd'");
        $this->reader->readFile('abcd');
    }

    public function testBaseEmpty(): void
    {
        $schema = $this->reader->readString('<xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"/>');

        self::assertEquals('http://www.example.com', $schema->getTargetNamespace());

        $schemas = $schema->getSchemas();

        self::assertNotEmpty($schemas);
    }

    public function getTypesToSearch(): array
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
    public function testNotFoundType($find): void
    {
        $schema = $this->reader->readString('<xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"/>');

        $this->expectException(TypeNotFoundException::class);
        $schema->$find('foo');
    }

    public function testBase(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="myType"></xs:complexType>
                <xs:element name="myElement" type="ex:myType"></xs:element>

                <xs:group name="myGroup">
                    <xs:sequence></xs:sequence>
                </xs:group>
                <xs:attribute name="myAttribute" type="xs:string"></xs:attribute>
                <xs:attributeGroup name="myAttributeGroup"></xs:attributeGroup>
            </xs:schema>'
        );

        self::assertCount(1, $schema->getTypes());
        self::assertInstanceOf(ComplexType::class, $schema->findType('myType', 'http://www.example.com'));
        // self::assertInstanceOf(ComplexType::class, $schema->findType('myType'));

        self::assertCount(1, $schema->getElements());
        self::assertInstanceOf(ElementDef::class, $schema->findElement('myElement', 'http://www.example.com'));

        self::assertCount(1, $schema->getGroups());
        self::assertInstanceOf(ElementGroup::class, $schema->findGroup('myGroup', 'http://www.example.com'));

        self::assertCount(1, $schema->getAttributeGroups());
        self::assertInstanceOf(AttributeGroup::class, $schema->findAttributeGroup('myAttributeGroup', 'http://www.example.com'));

        self::assertCount(1, $schema->getAttributes());
        self::assertInstanceOf(AttributeDef::class, $schema->findAttribute('myAttribute', 'http://www.example.com'));
    }

    public function testMultipleSchemasInSameFile(): void
    {
        $file = 'schema.xsd';
        $schema1 = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="myType"></xs:complexType>
                <xs:element name="myElement" type="ex:myType"></xs:element>

                <xs:group name="myGroup">
                    <xs:sequence></xs:sequence>
                </xs:group>
                <xs:attribute name="myAttribute" type="xs:string"></xs:attribute>
                <xs:attributeGroup name="myAttributeGroup"></xs:attributeGroup>
            </xs:schema>',
            $file
        );

        self::assertCount(1, $schema1->getTypes());
        self::assertInstanceOf(ComplexType::class, $schema1->findType('myType', 'http://www.example.com'));

        self::assertCount(1, $schema1->getElements());
        self::assertInstanceOf(ElementDef::class, $schema1->findElement('myElement', 'http://www.example.com'));

        // Now use a second schema which imports from the first one, and is in the SAME file
        $schema2 = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example2.com" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:ns1="http://www.example.com">
                <xs:import namespace="http://www.example.com"/>
                <xs:element name="myElement2" type="ns1:myType"></xs:element>
            </xs:schema>',
            $file
        );

        self::assertCount(0, $schema2->getTypes());

        self::assertCount(1, $schema2->getElements());
        self::assertInstanceOf(ElementDef::class, $schema2->findElement('myElement2', 'http://www.example2.com'));
    }

    public function testMultipleSchemasInSameFileWithSameTargetNamespace(): void
    {
        $file = 'schema.xsd';
        $schema1 = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="myType"></xs:complexType>
                <xs:element name="myElement" type="ex:myType"></xs:element>
            </xs:schema>',
            $file
        );

        self::assertCount(1, $schema1->getTypes());
        self::assertInstanceOf(ComplexType::class, $schema1->findType('myType', 'http://www.example.com'));

        self::assertCount(1, $schema1->getElements());
        self::assertInstanceOf(ElementDef::class, $schema1->findElement('myElement', 'http://www.example.com'));

        // Now use a second schema which uses the same targetNamespace
        $schema2 = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:import namespace="http://www.example.com"/>
                <xs:element name="myElement2" type="ex:myType"></xs:element>
            </xs:schema>',
            $file
        );

        $schema1->addSchema($schema2);

        self::assertCount(0, $schema2->getTypes());
        self::assertCount(1, $schema2->getElements());
        self::assertInstanceOf(ElementDef::class, $schema2->findElement('myElement2', 'http://www.example.com'));

        self::assertCount(1, $schema1->getTypes());
        self::assertCount(1, $schema1->getElements());
        self::assertInstanceOf(ElementDef::class, $schema1->findElement('myElement2', 'http://www.example.com'));
    }

    public function testGroupRefInType(): void
    {
        $schema1 = $this->reader->readString(
            '
        <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="myElement">
                <xs:complexType>
                    <xs:group ref="ex:myGroup"/>
                </xs:complexType>
            </xs:element>
            <xs:group name="myGroup">
                <xs:choice>
                    <xs:element name="groupElement" type="xs:string"/>
                </xs:choice>
            </xs:group>
        </xs:schema>');

        self::assertCount(1, $schema1->getGroups());
        $group = $schema1->findGroup('myGroup', 'http://www.example.com');

        self::assertCount(1, $schema1->getElements());

        $element = $schema1->findElement('myElement', 'http://www.example.com');

        /** @var ComplexType $type */
        $type = $element->getType();
        self::assertCount(1, $type->getElements());

        self::assertInstanceOf(GroupRef::class, $type->getElements()[0]);
        self::assertEquals($group->getElements(), $type->getElements()[0]->getElements());
    }

    public function testDependentReferencingSchemes(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML(
            '
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
        '
        );
        $schema = $this->reader->readNodes(iterator_to_array($dom->documentElement->childNodes), 'file.xsd');

        self::assertInstanceOf(ElementDef::class, $schema->findElement('CategoryList', 'http://tempuri.org/2'));
        self::assertInstanceOf(ComplexType::class, $schema->findType('Categories', 'http://tempuri.org/1'));
    }

    public static function getDependentReferences(): array
    {
        $holder = static fn (Schema $schema) => $schema->findType('Holder', 'http://tempuri.org/2');

        return [
            'element ref' => [
                '<xs:complexType name="Holder"><xs:sequence><xs:element ref="q1:Item"/></xs:sequence></xs:complexType>',
                static fn (Schema $schema) => $holder($schema)->getElements()[0]->getReferencedElement(),
            ],
            'group ref' => [
                '<xs:complexType name="Holder"><xs:sequence><xs:group ref="q1:ItemGroup"/></xs:sequence></xs:complexType>',
                static fn (Schema $schema) => $holder($schema)->getElements()[0],
            ],
            'attribute ref' => [
                '<xs:complexType name="Holder"><xs:attribute ref="q1:itemAttribute"/></xs:complexType>',
                static fn (Schema $schema) => $holder($schema)->getAttributes()[0]->getReferencedAttribute(),
            ],
            'attributeGroup ref' => [
                '<xs:complexType name="Holder"><xs:attributeGroup ref="q1:ItemAttributeGroup"/></xs:complexType>',
                static fn (Schema $schema) => $holder($schema)->getAttributes()[0],
            ],
            'element type' => [
                '<xs:element name="Holder" type="q1:ItemType"/>',
                static fn (Schema $schema) => $schema->findElement('Holder', 'http://tempuri.org/2')->getType(),
            ],
            'attribute type' => [
                '<xs:complexType name="Holder"><xs:attribute name="code" type="q1:ItemCode"/></xs:complexType>',
                static fn (Schema $schema) => $holder($schema)->getAttributes()[0]->getType(),
            ],
            'extension base' => [
                '<xs:complexType name="Holder"><xs:complexContent><xs:extension base="q1:ItemType"/></xs:complexContent></xs:complexType>',
                static fn (Schema $schema) => $holder($schema)->getExtension()->getBase(),
            ],
            'restriction base' => [
                '<xs:simpleType name="Holder"><xs:restriction base="q1:ItemCode"/></xs:simpleType>',
                static fn (Schema $schema) => $holder($schema)->getRestriction()->getBase(),
            ],
            'list itemType' => [
                '<xs:simpleType name="Holder"><xs:list itemType="q1:ItemCode"/></xs:simpleType>',
                static fn (Schema $schema) => $holder($schema)->getList(),
            ],
            'union memberTypes' => [
                '<xs:simpleType name="Holder"><xs:union memberTypes="q1:ItemCode"/></xs:simpleType>',
                static fn (Schema $schema) => $holder($schema)->getUnions()[0],
            ],
            'substitutionGroup' => [
                '<xs:element name="Holder" type="xs:string" substitutionGroup="q1:Item"/>',
                static function (Schema $schema) {
                    $head = $schema->findElement('Item', 'http://tempuri.org/1');
                    self::assertTrue($head->hasSubstitutionCandidates());

                    return $head;
                },
            ],
        ];
    }

    /**
     * Inline schemas of a WSDL often refer to each other without an import.
     * The references should resolve against the sibling schemas, the same way for every kind of reference.
     *
     * @dataProvider getDependentReferences
     */
    public function testDependentReferencingSchemesForAllReferences(string $content, callable $resolveReference): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('
        <types xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:schema targetNamespace="http://tempuri.org/1">
                <xs:element name="Item" type="xs:string" />
                <xs:group name="ItemGroup">
                    <xs:sequence>
                        <xs:element name="Item" type="xs:string" />
                    </xs:sequence>
                </xs:group>
                <xs:attribute name="itemAttribute" type="xs:string" />
                <xs:attributeGroup name="ItemAttributeGroup">
                    <xs:attribute name="itemAttribute" type="xs:string" />
                </xs:attributeGroup>
                <xs:complexType name="ItemType">
                    <xs:sequence>
                        <xs:element name="Item" type="xs:string" />
                    </xs:sequence>
                </xs:complexType>
                <xs:simpleType name="ItemCode">
                    <xs:restriction base="xs:string" />
                </xs:simpleType>
            </xs:schema>
            <xs:schema targetNamespace="http://tempuri.org/2" xmlns:q1="http://tempuri.org/1">' . $content . '</xs:schema>
        </types>
        ');
        $schema = $this->reader->readNodes(iterator_to_array($dom->documentElement->childNodes), 'file.xsd');

        $referenced = $resolveReference($schema);
        self::assertSame('http://tempuri.org/1', $referenced->getSchema()->getTargetNamespace());
    }

    public function testDependentReferencingSchemesPreferTheCurrentSchema(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('
        <types xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:schema targetNamespace="http://tempuri.org/1">
                <xs:element name="Item" type="xs:string" />
            </xs:schema>
            <xs:schema targetNamespace="http://tempuri.org/1" xmlns:q1="http://tempuri.org/1">
                <xs:element name="Item" type="xs:int" />
                <xs:complexType name="Holder">
                    <xs:sequence>
                        <xs:element ref="q1:Item" />
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
        </types>
        ');
        $schema = $this->reader->readNodes(iterator_to_array($dom->documentElement->childNodes), 'file.xsd');

        $type = $schema->findType('Holder', 'http://tempuri.org/1');
        self::assertSame('int', $type->getElements()[0]->getReferencedElement()->getType()->getName());
    }

    public function testDependentReferencingSchemesStillFailOnUnknownReferences(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('
        <types xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:schema targetNamespace="http://tempuri.org/1">
                <xs:element name="Item" type="xs:string" />
            </xs:schema>
            <xs:schema targetNamespace="http://tempuri.org/2" xmlns:q1="http://tempuri.org/1">
                <xs:complexType name="Holder">
                    <xs:sequence>
                        <xs:element ref="q1:Unknown" />
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
        </types>
        ');

        try {
            $this->reader->readNodes(iterator_to_array($dom->documentElement->childNodes), 'file.xsd');
            self::fail('Expected the unknown reference to fail.');
        } catch (TypeException $e) {
            self::assertStringContainsString("Can't find element named {http://tempuri.org/1}#Unknown", $e->getMessage());
            self::assertInstanceOf(TypeNotFoundException::class, $e->getPrevious());
        }
    }

    public function testDefaultNamespaceForSchema(): void
    {
        // Default namespace is provided. Validation does not fail.
        $schema1 = $this->reader->readString(
            '
        <schema targetNamespace="http://www.example.com" xmlns="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
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
        </schema>
        '
        );

        // Default namespace is provided but does not provide the myGroup component. Validation fails.
        $this->expectException(TypeException::class);
        $this->expectExceptionMessage("Can't find group named {http://www.example2.com}#myGroup, at line 4 in schema.xsd ");
        $schema1 = $this->reader->readString(
            '
        <schema targetNamespace="http://www.example.com" xmlns="http://www.example2.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
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
        </schema>
        '
        );
    }

    public function testUnknownNamespaceForSchema(): void
    {
        $this->expectException(TypeException::class);
        $this->expectExceptionMessage("Can't find namespace for prefix 'ex2', at line 4 in schema.xsd ");
        $schema1 = $this->reader->readString(
            '
        <schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="myElement">
                <xs:complexType>
                    <xs:group ref="ex2:myGroup"/>
                </xs:complexType>
            </xs:element>
            <xs:group name="myGroup">
                <xs:choice>
                    <xs:element name="groupElement" type="xs:string"/>
                </xs:choice>
            </xs:group>
        </schema>
        '
        );
    }
}
