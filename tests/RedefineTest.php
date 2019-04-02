<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Schema;

class RedefineTest extends BaseTest
{
    public function testBase()
    {
        $remoteSchema = $this->reader->readString(
            '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="personName">
                  <xs:sequence>
                   <xs:element name="title" minOccurs="0"/>
                   <xs:element name="forename" minOccurs="0" maxOccurs="unbounded"/>
                  </xs:sequence>
                 </xs:complexType>

                <xs:element name="addressee" type="personName"/>
            </xs:schema>', 'http://www.example.com/xsd.xsd');

        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.user.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:redefine schemaLocation="http://www.example.com/xsd.xsd">
                  <xs:complexType name="personName">
                   <xs:complexContent>
                    <xs:extension base="personName">
                     <xs:sequence>
                      <xs:element name="generation" minOccurs="0"/>
                     </xs:sequence>
                    </xs:extension>
                   </xs:complexContent>
                  </xs:complexType>
                 </xs:redefine>

                 <xs:element name="author" type="personName"/>
            </xs:schema>');

        // check if schema is not included
        // we don't want to redefine original schema
        $this->assertNotContains($remoteSchema, $schema->getSchemas(), '', true);

        /* @var $localAttr \GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef */

        // it should inherit namespace of main schema
        $localAttr = $schema->findElement('addressee', 'http://www.user.com');
        $this->assertNotNull($localAttr);

        // find author element
        $localAttr = $schema->findElement('author', 'http://www.user.com');
        $this->assertNotNull($localAttr);

        /* @var $type \GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType */
        $type = $localAttr->getType();

        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType::class, $type);

        $children = array();
        foreach ($type->getElements() as $element) {
            $children[] = $element->getName();
        }

        $this->assertContains('generation', $children);
    }

    public function testReadSchemaLocation()
    {
        $schema = $this->reader->readFile(__DIR__.'/schema/extend-components.xsd');
        $this->assertInstanceOf(Schema::class, $schema);

        $this->assertEquals('spec:example:xsd:CommonBasicComponents-1.0', $schema->getTargetNamespace());

        // defined in /schema/base-components.xsd
        $dateElement = $schema->findElement('Date', 'spec:example:xsd:CommonBasicComponents-1.0');
        $this->assertNotNull($dateElement);
        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef::class, $dateElement);
        $type = $dateElement->getType();
        $this->assertEquals('DateType', $type->getName());
        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType::class, $type);

        $dateType = $schema->findType('DateType', 'spec:example:xsd:CommonBasicComponents-1.0');
        $this->assertNotNull($dateType);
        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType::class, $dateType);

        // defined in /schema/extend-components.xsd
        $deliveryDateElement = $schema->findElement('DeliveryDate', 'spec:example:xsd:CommonBasicComponents-1.0');
        $this->assertNotNull($deliveryDateElement);
        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef::class, $deliveryDateElement);
        $type = $deliveryDateElement->getType();
        $this->assertEquals('DateType', $type->getName());
        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType::class, $type);
    }
}
