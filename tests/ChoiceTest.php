<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Element\Choice;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;

class ChoiceTest extends BaseTest
{
    public function testChoiceSimple(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="Languages">
                    <xs:complexType>
                        <xs:choice>
                            <xs:element ref="german"/>
                            <xs:element ref="english"/>
                            <xs:element ref="spanish"/>
                        </xs:choice>
                    </xs:complexType>
                </xs:element>
                <xs:element name="german" />
                <xs:element name="english" />
                <xs:element name="spanish" />

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element ref="Languages"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('Languages', $element->getName());
        $elementType = $element->getType();
        self::assertInstanceOf(ComplexType::class, $elementType);

        self::assertCount(1, $elementType->getElements());
        $choice = $elementType->getElements()[0];
        self::assertInstanceOf(Choice::class, $choice);
        self::assertEquals(1, $choice->getMin());
        self::assertEquals(1, $choice->getMax());

        self::assertCount(3, $choice->getElements());
    }

    public function testChoiceOptional(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="Languages">
                    <xs:complexType>
                        <xs:choice minOccurs="0">
                            <xs:element ref="german"/>
                            <xs:element ref="english"/>
                            <xs:element ref="spanish"/>
                        </xs:choice>
                    </xs:complexType>
                </xs:element>
                <xs:element name="german" />
                <xs:element name="english" />
                <xs:element name="spanish" />

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element ref="Languages"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('Languages', $element->getName());
        $elementType = $element->getType();
        self::assertInstanceOf(ComplexType::class, $elementType);

        self::assertCount(1, $elementType->getElements());
        $choice = $elementType->getElements()[0];
        self::assertInstanceOf(Choice::class, $choice);
        self::assertEquals(0, $choice->getMin());
        self::assertEquals(1, $choice->getMax());

        self::assertCount(3, $choice->getElements());
    }

    public function testChoiceOptionalElement(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="Languages">
                    <xs:complexType>
                        <xs:choice>
                            <xs:element ref="german"/>
                            <xs:element ref="english"/>
                            <xs:element ref="spanish" minOccurs="0"/>
                        </xs:choice>
                    </xs:complexType>
                </xs:element>
                <xs:element name="german" />
                <xs:element name="english" />
                <xs:element name="spanish" />

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element ref="Languages"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('Languages', $element->getName());
        $elementType = $element->getType();
        self::assertInstanceOf(ComplexType::class, $elementType);

        self::assertCount(1, $elementType->getElements());
        $choice = $elementType->getElements()[0];
        self::assertInstanceOf(Choice::class, $choice);
        self::assertEquals(1, $choice->getMin());
        self::assertEquals(1, $choice->getMax());

        self::assertCount(3, $choice->getElements());
        self::assertEquals(0, $choice->getElements()[2]->getMin());
    }

    public function testChoiceUnbounded(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="Languages">
                    <xs:complexType>
                        <xs:choice maxOccurs="unbounded">
                            <xs:element ref="german"/>
                            <xs:element ref="english"/>
                            <xs:element ref="spanish"/>
                        </xs:choice>
                    </xs:complexType>
                </xs:element>
                <xs:element name="german" />
                <xs:element name="english" />
                <xs:element name="spanish" />

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element ref="Languages"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('Languages', $element->getName());
        $elementType = $element->getType();
        self::assertInstanceOf(ComplexType::class, $elementType);

        self::assertCount(1, $elementType->getElements());
        $choice = $elementType->getElements()[0];
        self::assertInstanceOf(Choice::class, $choice);
        self::assertEquals(1, $choice->getMin());
        self::assertEquals(-1, $choice->getMax());

        self::assertCount(3, $choice->getElements());
    }

    public function testChoiceNested(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="LunchMenu">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:choice minOccurs="0">
                                <xs:element ref="Soup1"/>
                                <xs:choice>
                                    <xs:element ref="Soup2"/>
                                    <xs:element ref="Soup3"/>
                                </xs:choice>
                            </xs:choice>
                            <xs:choice>
                                <xs:element ref="MainMenu1"/>
                                <xs:choice>
                                    <xs:element ref="MainMenu2"/>
                                    <xs:element ref="MainMenu3"/>
                                </xs:choice>
                            </xs:choice>
                            <xs:choice minOccurs="0" maxOccurs="unbounded">
                                <xs:element ref="Dessert1"/>
                                <xs:element ref="Dessert2"/>
                            </xs:choice>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
                <xs:element name="Soup1" type="xs:string"/>
                <xs:element name="Soup2" type="xs:string"/>
                <xs:element name="Soup3" type="xs:string"/>
                <xs:element name="MainMenu1" type="xs:string"/>
                <xs:element name="MainMenu2" type="xs:string"/>
                <xs:element name="MainMenu3" type="xs:string"/>
                <xs:element name="Dessert1" type="xs:string"/>
                <xs:element name="Dessert2" type="xs:string"/>

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element ref="LunchMenu"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('LunchMenu', $element->getName());
        $elementType = $element->getType();
        self::assertInstanceOf(ComplexType::class, $elementType);

        self::assertCount(3, $elementType->getElements());

        $soupChoice = $elementType->getElements()[0];
        self::assertInstanceOf(Choice::class, $soupChoice);
        self::assertEquals(0, $soupChoice->getMin());
        self::assertEquals(1, $soupChoice->getMax());

        self::assertCount(2, $soupChoice->getElements());
        $soupSubChoice = $soupChoice->getElements()[1];
        self::assertInstanceOf(Choice::class, $soupSubChoice);
        self::assertCount(2, $soupSubChoice->getElements());

        $mainDishChoice = $elementType->getElements()[1];
        self::assertInstanceOf(Choice::class, $mainDishChoice);
        self::assertEquals(1, $mainDishChoice->getMin());
        self::assertEquals(1, $mainDishChoice->getMax());

        self::assertCount(2, $mainDishChoice->getElements());
        $mainDishSubChoice = $mainDishChoice->getElements()[1];
        self::assertInstanceOf(Choice::class, $mainDishSubChoice);
        self::assertCount(2, $mainDishSubChoice->getElements());

        $dessertChoice = $elementType->getElements()[2];
        self::assertInstanceOf(Choice::class, $dessertChoice);
        self::assertEquals(0, $dessertChoice->getMin());
        self::assertEquals(-1, $dessertChoice->getMax());
    }
}
