<?php
namespace Goetas\XML\XSDReader\Tests;

class AttributesTest extends BaseTest
{


    public function testBase()
    {



        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:attribute name="myAttribute" type="xs:string"></xs:attribute>
                <xs:attribute name="myAttributeOptions" type="xs:string" use="reuqired" nil="true"></xs:attribute>

                <xs:attributeGroup name="myAttributeGroup">
                    <xs:attribute name="alone" type="xs:string"></xs:attribute>
                    <xs:attribute ref="ex:myAttribute"></xs:attribute>
                    <xs:attributeGroup ref="ex:myAttributeGroup2"></xs:attributeGroup>
                </xs:attributeGroup>

                <xs:attributeGroup name="myAttributeGroup2">
                    <xs:attribute name="alone2" type="xs:string"></xs:attribute>
                </xs:attributeGroup>
            </xs:schema>');

        $myAttribute = $schema->findAttribute('myAttribute', 'http://www.example.com');
        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Attribute\AttributeReal', $myAttribute);
        //$this->assertEquals('http://www.example.com', $myAttribute->getSchema()->getTargetNamespace());
        $this->assertEquals('myAttribute', $myAttribute->getName());
        $this->assertFalse($myAttribute->isAnonymousType());

        $base1 = $myAttribute->getType();
        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Type\SimpleType', $base1);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema', $base1->getSchema()->getTargetNamespace());
        $this->assertEquals('string', $base1->getName());

        $myAttributeGroup = $schema->findAttributeGroup('myAttributeGroup', 'http://www.example.com');
        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Attribute\AttributeGroup', $myAttributeGroup);
        //$this->assertEquals('http://www.example.com', $myAttribute->getSchema()->getTargetNamespace());
        $this->assertEquals('myAttributeGroup', $myAttributeGroup->getName());
        $attributesInGroup = $myAttributeGroup->getAttributes();
        $this->assertCount(3, $attributesInGroup);

        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Attribute\AttributeReal', $attributesInGroup[0]);
        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Attribute\AttributeReal', $attributesInGroup[1]);
        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Attribute\AttributeGroup', $attributesInGroup[2]);



        $myAttribute = $schema->findAttribute('myAttributeOptions', 'http://www.example.com');
        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Attribute\AttributeReal', $myAttribute);
        //$this->assertEquals('http://www.example.com', $myAttribute->getSchema()->getTargetNamespace());
        $this->assertEquals('myAttributeOptions', $myAttribute->getName());
        $this->assertFalse($myAttribute->isAnonymousType());

    }
    public function testAnonym()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:attribute name="myAttributeAnonType">
                    <xs:simpleType>
                        <xs:restriction base="xs:string"></xs:restriction>
                    </xs:simpleType>
                </xs:attribute>

            </xs:schema>');


        $myAttributeAnon = $schema->findAttribute('myAttributeAnonType', 'http://www.example.com');
        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Attribute\AttributeReal', $myAttributeAnon);
        //$this->assertEquals('http://www.example.com', $myAttribute->getSchema()->getTargetNamespace());
        $this->assertEquals('myAttributeAnonType', $myAttributeAnon->getName());
        $this->assertTrue($myAttributeAnon->isAnonymousType());

        $base2 = $myAttributeAnon->getType();
        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Type\SimpleType', $base2);
        $this->assertEquals('http://www.example.com', $base2->getSchema()->getTargetNamespace());
        $this->assertTrue(!$base2->getName());


        $restriction1 = $base2->getRestriction();
        $base3 = $restriction1->getBase();
        $this->assertInstanceOf('Goetas\XML\XSDReader\Schema\Type\SimpleType', $base3);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema', $base3->getSchema()->getTargetNamespace());
        $this->assertEquals('string', $base3->getName());
    }
}