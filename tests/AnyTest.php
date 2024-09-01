<?php

declare(strict_types=1);

use GoetasWebservices\XML\XSDReader\Schema\Element\Any\Any;
use GoetasWebservices\XML\XSDReader\Schema\Element\Any\ProcessContents;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Tests\BaseTest;

class AnyTest extends BaseTest
{
    public function testBase(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="customerData">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:any />
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>'
        );

        $myElement = $schema->findElement('customerData', 'http://www.example.com');
        $myType = $myElement->getType();

        self::assertInstanceOf(ComplexType::class, $myType);

        $elements = $myType->getElements();

        self::assertCount(1, $elements);
        self::assertInstanceOf(Any::class, $any = $elements[0]);

        self::assertSame($schema, $any->getSchema());
        self::assertSame('any', $any->getName());
        self::assertNull($any->getNamespace());
        self::assertSame(ProcessContents::Strict, $any->getProcessContents());
        self::assertNull($any->getId());
        self::assertSame(1, $any->getMin());
        self::assertSame(1, $any->getMax());
        self::assertSame('', $any->getDoc());
    }

    public function testAllArgsSet(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="customerData">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:any id="hello" minOccurs="0" maxOccurs="2" processContents="lax" namespace="##any">
                                <xs:annotation>
                                    <xs:documentation>Any description</xs:documentation>
                                </xs:annotation>
                            </xs:any>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>'
        );

        $myElement = $schema->findElement('customerData', 'http://www.example.com');
        $myType = $myElement->getType();

        self::assertInstanceOf(ComplexType::class, $myType);

        $elements = $myType->getElements();

        self::assertCount(1, $elements);
        self::assertInstanceOf(Any::class, $any = $elements[0]);

        self::assertSame($schema, $any->getSchema());
        self::assertSame('any', $any->getName());
        self::assertSame('##any', $any->getNamespace());
        self::assertSame(ProcessContents::Lax, $any->getProcessContents());
        self::assertSame('hello', $any->getId());
        self::assertSame(0, $any->getMin());
        self::assertSame(2, $any->getMax());
        self::assertSame('Any description', $any->getDoc());
    }

    public function testCombinationWithOtherElements(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:element name="customerData">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="firstName" type="xs:string" />
                            <xs:element name="lastName" type="xs:string" />
                            <xs:any id="hello" minOccurs="0" maxOccurs="2" processContents="lax" namespace="##any" />
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>'
        );

        $myElement = $schema->findElement('customerData', 'http://www.example.com');
        $myType = $myElement->getType();

        self::assertInstanceOf(ComplexType::class, $myType);

        $elements = $myType->getElements();

        self::assertCount(3, $elements);
        self::assertInstanceOf(Any::class, $any = $elements[2]);
    }
}
