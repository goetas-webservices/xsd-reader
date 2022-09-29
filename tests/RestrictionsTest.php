<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

/**
 * @group Restriction
 */
class RestrictionsTest extends BaseTest
{
    /**
     * Test the correct detection an Enumeration-restriction.
     */
    public function testRestriction1()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

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
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a pattern-restriction.
     */
    public function testRestriction2()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

        $expectedChecks = [
            'pattern' => [
                [
                    'value' => '[a-zA-Z0-9]',
                    'doc' => '',
                ],
            ],
        ];
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a length-restriction.
     */
    public function testRestriction3()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

        $expectedChecks = [
            'length' => [
                [
                    'value' => '10',
                    'doc' => '',
                ],
            ],
        ];
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a minLength- and maxLength-restriction.
     */
    public function testRestriction4()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

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
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a minInclusive- and maxInclusive-restriction.
     */
    public function testRestriction5()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

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
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a minExclusive- and maxExclusive-restriction.
     */
    public function testRestriction6()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

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
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a fractionDigits-restriction.
     */
    public function testRestriction7()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

        $expectedChecks = [
            'fractionDigits' => [
                [
                    'value' => '2',
                    'doc' => '',
                ],
            ],
        ];
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a totalDigits-restriction.
     */
    public function testRestriction8()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

        $expectedChecks = [
            'totalDigits' => [
                [
                    'value' => '4',
                    'doc' => '',
                ],
            ],
        ];
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a totalDigits- and fractionDigits-restriction.
     */
    public function testRestriction9()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

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
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a whiteSpace-restriction.
     */
    public function testRestriction10()
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
        $this->assertInstanceOf('GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction', $restriction);

        $expectedChecks = [
            'whiteSpace' => [
                [
                    'value' => 'replace',
                    'doc' => '',
                ],
            ],
        ];
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }
}
