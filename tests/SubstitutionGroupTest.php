<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;

class SubstitutionGroupTest extends BaseTest
{
    public function testSubstitutionGroup(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="language" abstract="true"/>
                <xs:element name="german"  substitutionGroup="ex:language"/>
                <xs:element name="english" substitutionGroup="ex:language"/>
                <xs:element name="spanish" substitutionGroup="ex:language"/>
                <xs:complexType name="Languages">
                    <xs:sequence>
                        <xs:element ref="ex:language" minOccurs="1" maxOccurs="unbounded"/>
                    </xs:sequence>
                </xs:complexType>

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="myLanguages" type="ex:Languages"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('myLanguages', $element->getName());
        $elementType = $element->getType();
        self::assertEquals('Languages', $elementType->getName());

        self::assertCount(1, $elementType->getElements());
        $element = $elementType->getElements()[0];
        self::assertEquals(1, $element->getMin());
        self::assertEquals(-1, $element->getMax());
        $element = $element->getReferencedElement();
        self::assertTrue($element->isAbstract());
        self::assertCount(3, $element->getSubstitutionCandidates());
        self::assertEquals('german', $element->getSubstitutionCandidates()[0]->getName());
        self::assertEquals('english', $element->getSubstitutionCandidates()[1]->getName());
        self::assertEquals('spanish', $element->getSubstitutionCandidates()[2]->getName());
    }

    public function testSubstitutionGroupChangedOrder(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="german"  substitutionGroup="ex:language"/>
                <xs:element name="english" substitutionGroup="ex:language"/>
                <xs:element name="spanish" substitutionGroup="ex:language"/>
                <xs:element name="language" abstract="true"/>
                <xs:complexType name="Languages">
                    <xs:sequence>
                        <xs:element ref="ex:language" minOccurs="1" maxOccurs="unbounded"/>
                    </xs:sequence>
                </xs:complexType>

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="myLanguages" type="ex:Languages"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('myLanguages', $element->getName());
        $elementType = $element->getType();
        self::assertEquals('Languages', $elementType->getName());

        self::assertCount(1, $elementType->getElements());
        $element = $elementType->getElements()[0];
        self::assertEquals(1, $element->getMin());
        self::assertEquals(-1, $element->getMax());
        $element = $element->getReferencedElement();
        self::assertTrue($element->isAbstract());
        self::assertCount(3, $element->getSubstitutionCandidates());
        self::assertEquals('german', $element->getSubstitutionCandidates()[0]->getName());
        self::assertEquals('english', $element->getSubstitutionCandidates()[1]->getName());
        self::assertEquals('spanish', $element->getSubstitutionCandidates()[2]->getName());
    }

    public function testSubstitutionGroup2(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="name" type="xs:string"/>
                <xs:element name="navn" substitutionGroup="ex:name"/>

                <xs:complexType name="custinfo">
                <xs:sequence>
                    <xs:element ref="ex:customer"/>
                    <xs:element ref="ex:name"/>
                </xs:sequence>
                </xs:complexType>

                <xs:element name="customer" type="xs:string"/>
                <xs:element name="kunde" substitutionGroup="ex:customer"/>

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="customerInfo" type="ex:custinfo"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('customerInfo', $element->getName());
        $elementType = $element->getType();
        self::assertEquals('custinfo', $elementType->getName());

        self::assertCount(2, $elementType->getElements());
        $elementOne = $elementType->getElements()[0]->getReferencedElement();
        self::assertCount(1, $elementOne->getSubstitutionCandidates());
        self::assertEquals('kunde', $elementOne->getSubstitutionCandidates()[0]->getName());

        $elementTwo = $elementType->getElements()[1]->getReferencedElement();
        self::assertCount(1, $elementTwo->getSubstitutionCandidates());
        self::assertEquals('navn', $elementTwo->getSubstitutionCandidates()[0]->getName());
    }
}
