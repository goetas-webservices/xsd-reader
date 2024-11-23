<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeSingle;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;

/**
 * @group Restriction
 */
class RestrictionsTest extends BaseTest
{
    /**
     * Test the correct detection an Enumeration-restriction.
     */
    public function testRestriction1(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="enumeration">
                    <xs:simpleType>
                        <xs:restriction base="xs:string">
                            <xs:enumeration value="foo"/>
                            <xs:enumeration value="bar"/>
                        </xs:restriction>
                  </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('enumeration', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'enumeration' => [
                [
                    'value' => 'foo',
                    'doc' => '',
                ],
                [
                    'value' => 'bar',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a pattern-restriction.
     */
    public function testRestriction2(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="pattern">
                    <xs:simpleType>
                        <xs:restriction base="xs:string">
                            <xs:pattern value="[a-zA-Z0-9]"/>
                        </xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('pattern', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'pattern' => [
                [
                    'value' => '[a-zA-Z0-9]',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a length-restriction.
     */
    public function testRestriction3(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="length">
                    <xs:simpleType>
                        <xs:restriction base="xs:string">
                            <xs:length value="10"/>
                        </xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('length', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'length' => [
                [
                    'value' => '10',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a minLength- and maxLength-restriction.
     */
    public function testRestriction4(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="minMaxLength">
                    <xs:simpleType>
                        <xs:restriction base="xs:string">
                            <xs:minLength value="5"/>
                            <xs:maxLength value="8"/>
                        </xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('minMaxLength', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'minLength' => [
                [
                    'value' => '5',
                    'doc' => '',
                ],
            ],
            'maxLength' => [
                [
                    'value' => '8',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a minInclusive- and maxInclusive-restriction.
     */
    public function testRestriction5(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="minMaxInclusive">
                    <xs:simpleType>
                        <xs:restriction base="xs:integer">
                            <xs:minInclusive value="1"/>
                            <xs:maxInclusive value="10"/>
                        </xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('minMaxInclusive', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'minInclusive' => [
                [
                    'value' => '1',
                    'doc' => '',
                ],
            ],
            'maxInclusive' => [
                [
                    'value' => '10',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a minExclusive- and maxExclusive-restriction.
     */
    public function testRestriction6(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="minMaxExclusive">
                    <xs:simpleType>
                        <xs:restriction base="xs:integer">
                            <xs:minExclusive value="1"/>
                            <xs:maxExclusive value="10"/>
                        </xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('minMaxExclusive', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'minExclusive' => [
                [
                    'value' => '1',
                    'doc' => '',
                ],
            ],
            'maxExclusive' => [
                [
                    'value' => '10',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a fractionDigits-restriction.
     */
    public function testRestriction7(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="fractionDigits">
                    <xs:simpleType>
                        <xs:restriction base="xs:decimal">
                            <xs:fractionDigits value="2"/>
                        </xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('fractionDigits', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'fractionDigits' => [
                [
                    'value' => '2',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a totalDigits-restriction.
     */
    public function testRestriction8(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="totalDigits">
                    <xs:simpleType>
                        <xs:restriction base="xs:decimal">
                            <xs:totalDigits value="4"/>
                        </xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('totalDigits', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'totalDigits' => [
                [
                    'value' => '4',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a totalDigits- and fractionDigits-restriction.
     */
    public function testRestriction9(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="totalFractionDigits">
                    <xs:simpleType>
                        <xs:restriction base="xs:decimal">
                            <xs:totalDigits value="4"/>
                            <xs:fractionDigits value="2"/>
                        </xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('totalFractionDigits', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'totalDigits' => [
                [
                    'value' => '4',
                    'doc' => '',
                ],
            ],
            'fractionDigits' => [
                [
                    'value' => '2',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a whiteSpace-restriction.
     */
    public function testRestriction10(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:element name="whiteSpace">
                    <xs:simpleType>
                        <xs:restriction base="xs:string">
                            <xs:whiteSpace value="replace"/>
                        </xs:restriction>
                    </xs:simpleType>
                </xs:element>

            </xs:schema>');

        $element = $schema->findElement('whiteSpace', 'http://www.example.com');
        $simpleType = $element->getType();
        $restriction = $simpleType->getRestriction();
        self::assertInstanceOf(Restriction::class, $restriction);

        $expectedChecks = [
            'whiteSpace' => [
                [
                    'value' => 'replace',
                    'doc' => '',
                ],
            ],
        ];
        self::assertEquals($expectedChecks, $restriction->getChecks());
    }

    public function testRestrictionOverridingAttribute(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:complexType name="BaseAmountType">
                  <xs:simpleContent>
                     <xs:extension base="xs:decimal">
                        <xs:attribute name="currencyID" type="xs:string" use="optional">
                        </xs:attribute>
                        <xs:attribute name="currencyCodeListVersionID" type="xs:normalizedString" use="optional">
                        </xs:attribute>
                     </xs:extension>
                  </xs:simpleContent>
                </xs:complexType>

                <xs:complexType name="AmountType">
                  <xs:simpleContent>
                      <xs:restriction base="ex:BaseAmountType">
                        <xs:attribute name="currencyID" type="xs:string" use="required">
                        </xs:attribute>
                      </xs:restriction>
                  </xs:simpleContent>
                </xs:complexType>

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                          <xs:element name="myAmount" type="ex:AmountType"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('myAmount', $element->getName());
        $elementType = $element->getType();
        self::assertEquals('AmountType', $elementType->getName());
        self::assertInstanceOf(BaseComplexType::class, $elementType);

        $baseType = $elementType->getRestriction()->getBase();
        self::assertEquals('BaseAmountType', $baseType->getName());
        self::assertInstanceOf(BaseComplexType::class, $baseType);

        self::assertCount(1, $elementType->getAttributes());
        $attribute = $elementType->getAttributes()[0];
        self::assertInstanceOf(AttributeSingle::class, $attribute);
        self::assertEquals('currencyID', $attribute->getName());
        self::assertEquals('string', $attribute->getType()->getName());
        self::assertEquals(AttributeSingle::USE_REQUIRED, $attribute->getUse());
    }

    public function testRestrictionInRestrictionOverridingAttribute(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:complexType name="BaseAmountType">
                  <xs:simpleContent>
                     <xs:extension base="xs:decimal">
                        <xs:attribute name="currencyID" type="xs:string" use="optional">
                        </xs:attribute>
                        <xs:attribute name="currencyCodeListVersionID" type="xs:normalizedString" use="optional">
                        </xs:attribute>
                     </xs:extension>
                  </xs:simpleContent>
                </xs:complexType>

                <xs:complexType name="AmountType">
                  <xs:simpleContent>
                      <xs:restriction base="ex:BaseAmountType">
                        <xs:attribute name="currencyID" type="xs:string" use="required">
                        </xs:attribute>
                      </xs:restriction>
                    </xs:simpleContent>
                  </xs:complexType>

                <xs:complexType name="MyAmountType">
                  <xs:simpleContent>
                    <xs:restriction base="ex:AmountType"/>
                  </xs:simpleContent>
                </xs:complexType>

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                          <xs:element name="myAmount" type="ex:MyAmountType"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('myAmount', $element->getName());
        $elementType = $element->getType();
        self::assertEquals('MyAmountType', $elementType->getName());
        self::assertInstanceOf(BaseComplexType::class, $elementType);

        $baseType = $elementType->getRestriction()->getBase();
        self::assertEquals('AmountType', $baseType->getName());
        self::assertInstanceOf(BaseComplexType::class, $baseType);
        $baseTypeTwo = $baseType->getRestriction()->getBase();
        self::assertEquals('BaseAmountType', $baseTypeTwo->getName());

        self::assertCount(1, $baseType->getAttributes());
        $attribute = $baseType->getAttributes()[0];
        self::assertInstanceOf(AttributeSingle::class, $attribute);
        self::assertEquals('currencyID', $attribute->getName());
        self::assertEquals('string', $attribute->getType()->getName());
        self::assertEquals(AttributeSingle::USE_REQUIRED, $attribute->getUse());
    }

    public function testAttributeInExtensionInRestriction(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:ex="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:complexType name="BaseQuantityType">
                  <xs:simpleContent>
                    <xs:extension base="xs:decimal">
                      <xs:attribute name="unitCode" type="xs:string" use="optional" />
                    </xs:extension>
                  </xs:simpleContent>
                </xs:complexType>

                <xs:complexType name="QuantityType">
                  <xs:simpleContent>
                    <xs:extension base="ex:BaseQuantityType"/>
                  </xs:simpleContent>
                </xs:complexType>

                <xs:complexType name="MyQuantityType">
                  <xs:simpleContent>
                    <xs:restriction base="ex:QuantityType"/>
                  </xs:simpleContent>
                </xs:complexType>

                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                          <xs:element name="myQuantity" type="ex:MyQuantityType"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>

            </xs:schema>'
        );

        $rootType = $schema->getElements()['root']->getType();
        self::assertInstanceOf(ComplexType::class, $rootType);
        $element = $rootType->getElements()[0];
        self::assertEquals('myQuantity', $element->getName());
        $elementType = $element->getType();
        self::assertEquals('MyQuantityType', $elementType->getName());
        self::assertInstanceOf(BaseComplexType::class, $elementType);

        $baseType = $elementType->getRestriction()->getBase();
        self::assertEquals('QuantityType', $baseType->getName());
        self::assertInstanceOf(BaseComplexType::class, $baseType);
        $baseTypeTwo = $baseType->getExtension()->getBase();
        self::assertEquals('BaseQuantityType', $baseTypeTwo->getName());
        self::assertInstanceOf(BaseComplexType::class, $baseTypeTwo);

        self::assertCount(1, $baseTypeTwo->getAttributes());
        $attribute = $baseTypeTwo->getAttributes()[0];
        self::assertInstanceOf(AttributeSingle::class, $attribute);
        self::assertEquals('unitCode', $attribute->getName());
        self::assertEquals('string', $attribute->getType()->getName());
        self::assertEquals(AttributeSingle::USE_OPTIONAL, $attribute->getUse());
    }

    public function testElementOverrideInRestriction(): void
    {
        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:tns="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <!-- SOAP 1.1 enc:Array-->
                <xs:group name="Array">
                    <xs:sequence>
                        <xs:any namespace="##any" minOccurs="0" maxOccurs="unbounded" processContents="lax"/>
                    </xs:sequence>
                </xs:group>
                <xs:element name="Array" type="tns:Array"/>
                <xs:complexType name="Array">
                </xs:complexType>

                <xs:complexType name="testType">
                    <xs:complexContent>
                        <xs:restriction base="tns:Array">
                            <xs:all>
                                <xs:element name="x_item" type="xs:int" maxOccurs="unbounded" />
                            </xs:all>
                        </xs:restriction>
                    </xs:complexContent>
                </xs:complexType>
            </xs:schema>'
        );

        $rootType = $schema->getTypes()['testType'];
        self::assertInstanceOf(ComplexType::class, $rootType);

        $element = $rootType->getElements()[0];
        self::assertEquals('x_item', $element->getName());

        $baseType = $rootType->getRestriction()->getBase();
        self::assertInstanceOf(ComplexType::class, $baseType);
        self::assertEquals('Array', $baseType->getName());
    }
}
