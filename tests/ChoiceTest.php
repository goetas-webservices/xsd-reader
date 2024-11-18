<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Element\Choice;
use GoetasWebservices\XML\XSDReader\Schema\Element\Sequence;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;

class ChoiceTest extends BaseTest
{
    public function testChoiceSimple(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="Languages">
                    <xs:complexType>
                        <xs:choice>
                            <xs:element ref="ex:german"/>
                            <xs:element ref="ex:english"/>
                            <xs:element ref="ex:spanish"/>
                        </xs:choice>
                    </xs:complexType>
                </xs:element>
                <xs:element name="german" />
                <xs:element name="english" />
                <xs:element name="spanish" />

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element ref="ex:Languages"/>
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
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="Languages">
                    <xs:complexType>
                        <xs:choice minOccurs="0">
                            <xs:element ref="ex:german"/>
                            <xs:element ref="ex:english"/>
                            <xs:element ref="ex:spanish"/>
                        </xs:choice>
                    </xs:complexType>
                </xs:element>
                <xs:element name="german" />
                <xs:element name="english" />
                <xs:element name="spanish" />

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element ref="ex:Languages"/>
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
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="Languages">
                    <xs:complexType>
                        <xs:choice>
                            <xs:element ref="ex:german"/>
                            <xs:element ref="ex:english"/>
                            <xs:element ref="ex:spanish" minOccurs="0"/>
                        </xs:choice>
                    </xs:complexType>
                </xs:element>
                <xs:element name="german" />
                <xs:element name="english" />
                <xs:element name="spanish" />

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element ref="ex:Languages"/>
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
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="Languages">
                    <xs:complexType>
                        <xs:choice maxOccurs="unbounded">
                            <xs:element ref="ex:german"/>
                            <xs:element ref="ex:english"/>
                            <xs:element ref="ex:spanish"/>
                        </xs:choice>
                    </xs:complexType>
                </xs:element>
                <xs:element name="german" />
                <xs:element name="english" />
                <xs:element name="spanish" />

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element ref="ex:Languages"/>
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
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="LunchMenu">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:choice minOccurs="0">
                                <xs:element ref="ex:Soup1"/>
                                <xs:choice>
                                    <xs:element ref="ex:Soup2"/>
                                    <xs:element ref="ex:Soup3"/>
                                </xs:choice>
                            </xs:choice>
                            <xs:choice>
                                <xs:element ref="ex:MainMenu1"/>
                                <xs:choice>
                                    <xs:element ref="ex:MainMenu2"/>
                                    <xs:element ref="ex:MainMenu3"/>
                                </xs:choice>
                            </xs:choice>
                            <xs:choice minOccurs="0" maxOccurs="unbounded">
                                <xs:element ref="ex:Dessert1"/>
                                <xs:element ref="ex:Dessert2"/>
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
                            <xs:element ref="ex:LunchMenu"/>
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

    public function testChoiceWithSequences(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="root">
                    <xs:complexType>
                      <xs:sequence>
                        <xs:element minOccurs="1" name="Intro" type="xs:string"/>
                        <xs:choice>
                          <xs:sequence>
                            <xs:choice>
                              <xs:element name="Red" type="xs:string"/>
                              <xs:element name="Green" type="xs:string"/>
                              <xs:element name="Blue" type="xs:string"/>
                            </xs:choice>
                            <xs:element name="Outro1" type="xs:string"/>
                          </xs:sequence>
                          <xs:sequence>
                            <xs:element name="AlternateElement"/>
                            <xs:element name="Outro2" type="xs:string"/>
                          </xs:sequence>
                        </xs:choice>
                      </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>
        ');

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);

        $elements = $rootType->getElements();
        self::assertCount(2, $elements);

        self::assertEquals('Intro', $elements[0]->getName());
        self::assertInstanceOf(SimpleType::class, $elements[0]->getType());

        $choice = $elements[1];
        self::assertInstanceOf(Choice::class, $choice);
        self::assertEquals(1, $choice->getMin());
        self::assertEquals(1, $choice->getMax());

        $choiceElements = $choice->getElements();
        self::assertCount(2, $choiceElements);

        $sequence1 = $choiceElements[0];
        self::assertInstanceOf(Sequence::class, $sequence1);
        $sequence2 = $choiceElements[1];
        self::assertInstanceOf(Sequence::class, $sequence2);

        $sequence1Elements = $sequence1->getElements();
        $innerChoice = $sequence1Elements[0];
        self::assertInstanceOf(Choice::class, $innerChoice);
        $innerChoiceElements = $innerChoice->getElements();
        self::assertCount(3, $innerChoiceElements);
        self::assertEquals('Red', $innerChoiceElements[0]->getName());
        self::assertEquals('Green', $innerChoiceElements[1]->getName());
        self::assertEquals('Blue', $innerChoiceElements[2]->getName());
        self::assertEquals('Outro1', $sequence1Elements[1]->getName());

        $sequence2Elements = $sequence2->getElements();
        self::assertCount(2, $sequence2Elements);
        self::assertEquals('AlternateElement', $sequence2Elements[0]->getName());
        self::assertEquals('Outro2', $sequence2Elements[1]->getName());
    }
}
