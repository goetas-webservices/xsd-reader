<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Tests;

use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
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

    /**
     * Ensure Semantics of <redefine> are the same as described in the XSD specification
     * @link https://www.w3.org/TR/xmlschema11-1/#modify-schema
     */
    public function testRedefineSemantics()
    {
        $schema = $this->reader->readFile(__DIR__.'/schema/extend-xsd-v2.xsd');
        $this->assertInstanceOf(Schema::class, $schema);

        // Definition from https://www.w3.org/TR/xmlschema11-1/#modify-schema

        // The schema corresponding to v2.xsd has everything specified by v1.xsd, with the personName type redefined,
        // as well as everything it specifies itself.
        // According to this schema, elements constrained by the personName type may end with a generation element.
        // This includes not only the author element, but also the addressee element.
        $author = $schema->findElement('author');
        $addressee = $schema->findElement('addressee');
        $this->assertNotNull($author);
        $this->assertNotNull($addressee);
        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef::class, $author);
        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef::class, $addressee);
        $authorType = $author->getType();
        $addresseeType = $addressee->getType();
        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType::class, $authorType);
        $this->assertInstanceOf(\GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType::class, $addresseeType);

        $this->assertEquals('personName', $authorType->getName());
        $this->assertEquals('personName', $addresseeType->getName());

        // ensure both types contain the same elements
        foreach([$authorType, $addresseeType] as $type) {
            /** @var $type \GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType */

            $elements = $type->getElements();
            $elementNames = array_map(function(Element $element) { return $element->getName(); }, $elements);
            sort($elementNames);
            $this->assertEquals(['forename', 'generation', 'title'], $elementNames);
        }


        //
        // For any document D2 pointed at by a <redefine> element in D1, it must be the case either (a) that tns(D1) = tns(D2) or else (b) that tns(D2) is ·absent·, in which case schema(D1) includes not redefine(E,schema(D2)) itself but redefine(E,schema(chameleon(tns(D1),D2))). That is, the redefinition pre-processing is applied not to the schema corresponding to D2 but instead to the schema corresponding to the schema document chameleon(tns(D1),D2), which is the result of applying chameleon pre-processing to D2 to convert it to target namespace tns(D1).

    }
}
