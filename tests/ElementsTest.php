<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;

class ElementsTest extends BaseTest
{
    public function testBase(): void
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
            </xs:schema>'
        );

        $myElement = $schema->findElement('myElement', 'http://www.example.com');
        self::assertInstanceOf(Element::class, $myElement);
        //self::assertEquals('http://www.example.com', $myElement->getSchema()->getTargetNamespace());
        self::assertEquals('myElement', $myElement->getName());
        self::assertEquals('string', $myElement->getType()->getName());

        $myGroup = $schema->findGroup('myGroup', 'http://www.example.com');
        self::assertInstanceOf(Group::class, $myGroup);
        //self::assertEquals('http://www.example.com', $myElement->getSchema()->getTargetNamespace());
        self::assertEquals('myGroup', $myGroup->getName());
        $elementsInGroup = $myGroup->getElements();
        self::assertCount(3, $elementsInGroup);

        self::assertInstanceOf(Element::class, $elementsInGroup[0]);
        self::assertInstanceOf(ElementItem::class, $elementsInGroup[1]);
        self::assertInstanceOf(Group::class, $elementsInGroup[2]);
    }

    /**
     * @dataProvider getGroupCounts
     */
    public function testGroupOccurrences($item, $min, $max): void
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
        self::assertInstanceOf(ComplexType::class, $myType);

        $myGroup = $schema->findGroup('myGroup', 'http://www.example.com');
        self::assertInstanceOf(Group::class, $myGroup);

        $myGroupRef = $myType->getElements()[$item];
        self::assertInstanceOf(GroupRef::class, $myGroupRef);

        $wrappedEls = $myGroupRef->getElements();
        if ($max === -1 || $max > 0) {
            self::assertEquals($max, $wrappedEls[0]->getMax());
        } else {
            self::assertEquals(1, $wrappedEls[0]->getMax());
        }

        if ($min > 1) {
            self::assertEquals($max, $wrappedEls[0]->getMin());
        } else {
            self::assertEquals(1, $wrappedEls[0]->getMin());
        }

        self::assertEquals('myGroup', $myGroupRef->getName());

        self::assertEquals($min, $myGroupRef->getMin());
        self::assertEquals($max, $myGroupRef->getMax());
    }

    public function getGroupCounts(): array
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

    public function testNotQualifiedTargetQualifiedElement(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema version="1.0" targetNamespace="http://www.example.com"
                xmlns:xs="http://www.w3.org/2001/XMLSchema" elementFormDefault="unqualified">
                <xs:complexType name="root">
                    <xs:sequence>
                        <xs:element name="child" type="xs:string"/>
                        <xs:element name="childRoot" type="xs:string" form="qualified"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>
            '
        );

        $myType = $schema->findType('root', 'http://www.example.com');
        self::assertInstanceOf(ComplexType::class, $myType);
        self::assertFalse($schema->getElementsQualification());

        /**
         * @var $element ElementSingle
         */
        $element = $myType->getElements()[0];
        self::assertFalse($element->isQualified());

        $element = $myType->getElements()[1];
        self::assertTrue($element->isQualified());
    }

    public function testGroupRefOccurrences(): void
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
        self::assertInstanceOf(ComplexType::class, $myType);

        $myGroup = $schema->findGroup('myGroup', 'http://www.example.com');
        self::assertInstanceOf(Group::class, $myGroup);

        $myGroupRef = $myType->getElements()[0];
        self::assertInstanceOf(GroupRef::class, $myGroupRef);

        self::assertEquals('myGroup', $myGroupRef->getName());
        // @todo this is not yet really working
        //        self::assertEquals(2, $myGroupRef->getMin());
        //        self::assertEquals(5, $myGroupRef->getMax());
    }

    public function testAnonym(): void
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

            </xs:schema>'
        );

        $myElementAnon = $schema->findElement('myElementAnonType', 'http://www.example.com');
        self::assertInstanceOf(Element::class, $myElementAnon);
        //self::assertEquals('http://www.example.com', $myElement->getSchema()->getTargetNamespace());
        self::assertEquals('myElementAnonType', $myElementAnon->getName());
        self::assertNull($myElementAnon->getType()->getName());

        $base2 = $myElementAnon->getType();
        self::assertInstanceOf(SimpleType::class, $base2);
        self::assertEquals('http://www.example.com', $base2->getSchema()->getTargetNamespace());
        self::assertTrue(!$base2->getName());

        $restriction1 = $base2->getRestriction();
        $base3 = $restriction1->getBase();
        self::assertInstanceOf(SimpleType::class, $base3);
        self::assertEquals('http://www.w3.org/2001/XMLSchema', $base3->getSchema()->getTargetNamespace());
        self::assertEquals('string', $base3->getName());
    }

    public function testElementSimpleTypeDocs(): void
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
        self::assertSame(
            'Element type description',
            $myElement->getType()->getDoc()
        );
    }

    public function testSequenceElementDocs(): void
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
        self::assertSame('Alone description', $aloneElement->getDoc());
    }
}
