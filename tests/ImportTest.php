<?php
namespace GoetasWebservices\XML\XSDReader\Tests;

class ImportTest extends BaseTest
{


    public function testBase()
    {
        $remoteSchema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:attribute name="myAttribute" type="xs:string"></xs:attribute>
            </xs:schema>', 'http://www.example.com/xsd.xsd');


        $schema = $this->reader->readString(
            '
            <xs:schema targetNamespace="http://www.user.com" xmlns:xs="http://www.w3.org/2001/XMLSchema"  xmlns:ex="http://www.example.com">
                <xs:import schemaLocation="http://www.example.com/xsd.xsd" namespace="http://www.example.com"></xs:import>

                <xs:attributeGroup name="myAttributeGroup">
                    <xs:attribute ref="ex:myAttribute"></xs:attribute>
                </xs:attributeGroup>

            </xs:schema>');

        $this->assertContains($remoteSchema, $schema->getSchemas(), false, true);

        $remoteAttr = $remoteSchema->findAttribute("myAttribute", "http://www.example.com");
        $localAttr = $schema->findAttribute("myAttribute", "http://www.example.com");

        $this->assertSame($remoteAttr, $localAttr);

        $localAttrGroup = $schema->findAttributeGroup("myAttributeGroup", "http://www.user.com");
        $localAttrs = $localAttrGroup->getAttributes();

        $this->assertSame($remoteAttr, $localAttrs[0]);
    }
}