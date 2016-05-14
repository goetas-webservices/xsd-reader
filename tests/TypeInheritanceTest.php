<?php
namespace GoetasWebservices\XML\XSDReader\Tests;

class TypeInheritanceTest extends BaseTest
{

    public function testInheritanceWithExtension()
    {
        $schema = $this->reader->readString('
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
            ');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType', $type1 = $schema->findType('complexType-1', 'http://www.example.com'));
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType', $type2 = $schema->findType('complexType-2', 'http://www.example.com'));

        $this->assertSame($type1, $type2->getExtension()->getBase());

        $elements = $type2->getElements();
        $attributes = $type2->getAttributes();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\Element', $elements[0]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute', $attributes[0]);

        $this->assertEquals('complexType-2-el1', $elements[0]->getName());
        $this->assertEquals('complexType-2-att1', $attributes[0]->getName());


    }

    public function testBase()
    {
        $schema = $this->reader->readString('
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:simpleType name="mySimple">
                    <xs:restriction base="xs:string"></xs:restriction>
                </xs:simpleType>

                <xs:simpleType name="mySimpleWithRestr">
                    <xs:restriction base="mySimple"></xs:restriction>
                </xs:simpleType>

                <xs:complexType name="myComplex">
                    <xs:simpleContent>
                        <xs:extension base="mySimpleWithRestr"></xs:extension>
                    </xs:simpleContent>
                </xs:complexType>

                <xs:simpleType name="mySimpleWithUnion">
                    <xs:union memberTypes="xs:string mySimpleWithRestr"></xs:union>
                </xs:simpleType>

            </xs:schema>');

        $this->assertCount(4, $schema->getTypes());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $type = $schema->findType('mySimple', 'http://www.example.com'));
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $type2 = $schema->findType('mySimpleWithRestr', 'http://www.example.com'));
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent', $type3 = $schema->findType('myComplex', 'http://www.example.com'));
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $type4 = $schema->findType('mySimpleWithUnion', 'http://www.example.com'));

        $restriction1 = $type->getRestriction();
        $base1 = $restriction1->getBase();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base1);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema', $base1->getSchema()->getTargetNamespace());
        $this->assertEquals('string', $base1->getName());

        $restriction2 = $type2->getRestriction();
        $base2 = $restriction2->getBase();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base2);
        $this->assertEquals('http://www.example.com', $base2->getSchema()->getTargetNamespace());
        $this->assertEquals('mySimple', $base2->getName());

        $extension = $type3->getExtension();
        $base3 = $extension->getBase();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base3);
        $this->assertEquals('http://www.example.com', $base3->getSchema()->getTargetNamespace());
        $this->assertEquals('mySimpleWithRestr', $base3->getName());

        $unions = $type4->getUnions();
        $this->assertCount(2, $unions);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $unions[0]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $unions[1]);

        $this->assertEquals('http://www.w3.org/2001/XMLSchema', $unions[0]->getSchema()->getTargetNamespace());
        $this->assertEquals('string', $unions[0]->getName());

        $this->assertEquals('http://www.example.com', $unions[1]->getSchema()->getTargetNamespace());
        $this->assertEquals('mySimpleWithRestr', $unions[1]->getName());
    }

    public function testAnonyeExtension()
    {
        $schema = $this->reader->readString('
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:simpleType name="myType">
                    <xs:restriction>
                        <xs:simpleType>
                            <xs:restriction base="xs:string"></xs:restriction>
                        </xs:simpleType>
                    </xs:restriction>
                </xs:simpleType>

            </xs:schema>');

        $this->assertCount(1, $schema->getTypes());
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $type = $schema->findType('myType', 'http://www.example.com'));

        $restriction = $type->getRestriction();
        $base = $restriction->getBase();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base);
        $this->assertEquals('http://www.example.com', $base->getSchema()->getTargetNamespace());
        $this->assertTrue(!$base->getName());


        $restriction2 = $base->getRestriction();
        $base2 = $restriction2->getBase();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base2);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema', $base2->getSchema()->getTargetNamespace());
        $this->assertEquals('string', $base2->getName());

    }

    public function testAnonymUnion()
    {
        $schema = $this->reader->readString('
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

            </xs:schema>');


        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $type = $schema->findType('myAnonUnion', 'http://www.example.com'));


        $unions = $type->getUnions();

        $this->assertCount(2, $unions);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $unions[0]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $unions[1]);

        $this->assertEquals('http://www.example.com', $unions[0]->getSchema()->getTargetNamespace());
        $this->assertTrue(!$unions[0]->getName());

        $this->assertEquals('http://www.example.com', $unions[1]->getSchema()->getTargetNamespace());
        $this->assertTrue(!$unions[1]->getName());

        $restriction1 = $unions[0]->getRestriction();
        $base1 = $restriction1->getBase();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base1);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema', $base1->getSchema()->getTargetNamespace());
        $this->assertEquals('string', $base1->getName());

        $restriction2 = $unions[1]->getRestriction();
        $base2 = $restriction2->getBase();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base2);
        $this->assertEquals('http://www.example.com', $base2->getSchema()->getTargetNamespace());
        $this->assertEquals('myType', $base2->getName());

    }
}