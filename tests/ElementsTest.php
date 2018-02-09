<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;

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
        $this->assertEquals('string', $myElement->getType()->getName());

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

    /**
     * @dataProvider getGroupCounts
     */
    public function testGroupOccurrences($item, $min, $max)
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="myType">
                    <xs:sequence>     
                        <xs:group ref="myGroup" minOccurs="1" />
                        <xs:group ref="myGroup" minOccurs="2" />
                        
                        <xs:group ref="myGroup" maxOccurs="1" />
                        <xs:group ref="myGroup" maxOccurs="unbounded" />
                        
                        <xs:group ref="myGroup" minOccurs="1" maxOccurs="1"/>
                        <xs:group ref="myGroup" minOccurs="2" maxOccurs="2"/>
                        
                        <xs:group ref="myGroup" minOccurs="1" maxOccurs="unbounded"/>
                        
                    </xs:sequence>
                </xs:complexType>

                <xs:group name="myGroup">
                    <xs:sequence>
                        <xs:element name="groupEl1" type="xs:string" />
                    </xs:sequence>
                </xs:group>
            </xs:schema>');

        $myType = $schema->findType('myType', 'http://www.example.com');
        $this->assertInstanceOf(ComplexType::class, $myType);

        $myGroup = $schema->findGroup('myGroup', 'http://www.example.com');
        $this->assertInstanceOf(Group::class, $myGroup);

        $myGroupRef = $myType->getElements()[$item];
        $this->assertInstanceOf(GroupRef::class, $myGroupRef);

        $wrappedEls = $myGroupRef->getElements();
        if ($max === -1 || $max > 0) {
            $this->assertEquals($max, $wrappedEls[0]->getMax());
        } else {
            $this->assertEquals(1, $wrappedEls[0]->getMax());
        }

        if ($min > 1) {
            $this->assertEquals($max, $wrappedEls[0]->getMin());
        } else {
            $this->assertEquals(1, $wrappedEls[0]->getMin());
        }

        $this->assertEquals('myGroup', $myGroupRef->getName());

        $this->assertEquals($min, $myGroupRef->getMin());
        $this->assertEquals($max, $myGroupRef->getMax());
    }

    public function getGroupCounts()
    {
        return [
            // item, min, max
            [0, 1, 1],
            [1, 2, 2], // if the min = 2, max must be at least 2
            [2, 1, 1],
            [3, 1, -1],
            [4, 1, 1],
            [5, 2, 2],
            [6, 1, -1],
        ];
    }

    public function testGroupRefOccurrences()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="myType">
                    <xs:sequence>     
                        <xs:group ref="myGroup" />                        
                    </xs:sequence>
                </xs:complexType>

                <xs:group name="myGroup" minOccurs="2" maxOccurs="5">
                    <xs:sequence>
                        <xs:element name="groupEl1" type="xs:string" />
                    </xs:sequence>
                </xs:group>
            </xs:schema>');

        $myType = $schema->findType('myType', 'http://www.example.com');
        $this->assertInstanceOf(ComplexType::class, $myType);

        $myGroup = $schema->findGroup('myGroup', 'http://www.example.com');
        $this->assertInstanceOf(Group::class, $myGroup);

        $myGroupRef = $myType->getElements()[0];
        $this->assertInstanceOf(GroupRef::class, $myGroupRef);

        $this->assertEquals('myGroup', $myGroupRef->getName());
        // @todo this is not yet really working
        //        $this->assertEquals(2, $myGroupRef->getMin());
        //        $this->assertEquals(5, $myGroupRef->getMax());
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

    public function testElementSimpleTypeDocs()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                 <xs:element name="myElementType" id="myElementType">
                    <xs:simpleType>
                        <xs:annotation>
                            <xs:documentation>Element type description</xs:documentation>
                        </xs:annotation>
                    </xs:simpleType>
                 </xs:element>
            </xs:schema>');

        $myElement = $schema->findElement('myElementType', 'http://www.example.com');
        $this->assertSame(
            'Element type description',
            $myElement->getType()->getDoc()
        );
    }

    public function testSequenceElementDocs()
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:group name="myGroup">
                    <xs:sequence>
                        <xs:element name="alone" type="xs:string">
                            <xs:annotation>
                                <xs:documentation>Alone description</xs:documentation>
                            </xs:annotation>
                        </xs:element>
                    </xs:sequence>
                </xs:group>
            </xs:schema>');

        $myGroup = $schema->findGroup('myGroup', 'http://www.example.com');
        /** @var Element $aloneElement */
        $aloneElement = $myGroup->getElements()[0];
        $this->assertSame('Alone description', $aloneElement->getDoc());
    }
}
