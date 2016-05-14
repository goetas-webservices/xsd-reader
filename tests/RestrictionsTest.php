<?php
namespace GoetasWebservices\XML\XSDReader\Tests;

/**
 * @group Restriction
 */
class RestrictionsTest extends BaseTest
{
    /**
     * Test the correct detection an Enumeration-restriction.
     */
    public function testRestriction_1()
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

        $expectedChecks = array(
            'enumeration' => array(
                array(
                    'value' => 'foo',
                    'doc' => '',
                ),
                array(
                    'value' => 'bar',
                    'doc' => '',
                ),
            ),
        );
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a pattern-restriction.
     */
    public function testRestriction_2()
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

        $expectedChecks = array(
            'pattern' => array(
                array(
                    'value' => '[a-zA-Z0-9]',
                    'doc' => '',
                ),
            ),
        );
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a length-restriction.
     */
    public function testRestriction_3()
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

        $expectedChecks = array(
            'length' => array(
                array(
                    'value' => '10',
                    'doc' => '',
                ),
            ),
        );
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a minLength- and maxLength-restriction.
     */
    public function testRestriction_4()
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

        $expectedChecks = array(
            'minLength' => array(
                array(
                    'value' => '5',
                    'doc' => '',
                ),
            ),
            'maxLength' => array(
                array(
                    'value' => '8',
                    'doc' => '',
                ),
            ),
        );
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a minInclusive- and maxInclusive-restriction.
     */
    public function testRestriction_5()
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

        $expectedChecks = array(
            'minInclusive' => array(
                array(
                    'value' => '1',
                    'doc' => '',
                ),
            ),
            'maxInclusive' => array(
                array(
                    'value' => '10',
                    'doc' => '',
                ),
            ),
        );
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }

    /**
     * Test the correct detection a minExclusive- and maxExclusive-restriction.
     */
    public function testRestriction_6()
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

        $expectedChecks = array(
            'minExclusive' => array(
                array(
                    'value' => '1',
                    'doc' => '',
                ),
            ),
            'maxExclusive' => array(
                array(
                    'value' => '10',
                    'doc' => '',
                ),
            ),
        );
        $this->assertEquals($expectedChecks, $restriction->getChecks());
    }
}
