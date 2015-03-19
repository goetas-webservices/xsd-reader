<?php
namespace GoetasWebservices\XML\XSDReader\Tests;

class ElementsTest extends BaseTest
{

    public function testBase()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="myElement" type="xs:string"></xs:element>

                <xs:element name="myElementAnonType">
                    <xs:simpleType>
                        <xs:restriction base="xs:string"></xs:restriction>
                    </xs:simpleType>
                </xs:element>

                <xs:group name="myGroup">
                    <xs:sequence>
                        <xs:element name="alone" type="xs:string"></xs:element>
                        <xs:element ref="ex:myElement"></xs:element>
                        <xs:group ref="ex:myGroup2"></xs:group>
                    </xs:sequence>
                </xs:group>

                <xs:group name="myGroup2">
                    <xs:element name="alone2" type="xs:string"></xs:element>
                </xs:group>
            </xs:schema>');

        $myElement = $schema->findElement('myElement', 'http://www.example.com');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $myElement);
        //$this->assertEquals('http://www.example.com', $myElement->getSchema()->getTargetNamespace());
        $this->assertEquals('myElement', $myElement->getName());
        $this->assertEquals("string", $myElement->getType()->getName());

        $myGroup = $schema->findGroup('myGroup', 'http://www.example.com');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\Group', $myGroup);
        //$this->assertEquals('http://www.example.com', $myElement->getSchema()->getTargetNamespace());
        $this->assertEquals('myGroup', $myGroup->getName());
        $elementsInGroup = $myGroup->getElements();
        $this->assertCount(3, $elementsInGroup);

        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\Element', $elementsInGroup[0]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem', $elementsInGroup[1]);
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\Group', $elementsInGroup[2]);
    }

    public function testAnonym()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="myElement" type="xs:string"></xs:element>

                <xs:element name="myElementAnonType">
                    <xs:simpleType>
                        <xs:restriction base="xs:string"></xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');


        $myElementAnon = $schema->findElement('myElementAnonType', 'http://www.example.com');
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef', $myElementAnon);
        //$this->assertEquals('http://www.example.com', $myElement->getSchema()->getTargetNamespace());
        $this->assertEquals('myElementAnonType', $myElementAnon->getName());
        $this->assertNull($myElementAnon->getType()->getName());

        $base2 = $myElementAnon->getType();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base2);
        $this->assertEquals('http://www.example.com', $base2->getSchema()->getTargetNamespace());
        $this->assertTrue(!$base2->getName());


        $restriction1 = $base2->getRestriction();
        $base3 = $restriction1->getBase();
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType', $base3);
        $this->assertEquals('http://www.w3.org/2001/XMLSchema', $base3->getSchema()->getTargetNamespace());
        $this->assertEquals('string', $base3->getName());
    }

}