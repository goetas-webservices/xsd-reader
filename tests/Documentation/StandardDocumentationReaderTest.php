<?php

namespace GoetasWebservices\XML\XSDReader\Tests\Documentation;

use GoetasWebservices\XML\XSDReader\Documentation\StandardDocumentationReader;
use PHPUnit\Framework\TestCase;

class StandardDocumentationReaderTest extends TestCase
{
    /**
     * @var StandardDocumentationReader
     */
    private $reader;

    public function setUp()
    {
        $this->reader = new StandardDocumentationReader();
    }

    public function testItReturnsAString()
    {
        $element = $this->getSampleElement('Some sample text');

        $result = $this->reader->get($element);

        $this->assertInternalType('string', $result);
    }

    public function testItReturnsTheTrimmedAnnotationDocumentationText()
    {
        $text = 'Some sample text';
        $element = $this->getSampleElement($text);

        $result = $this->reader->get($element);

        $this->assertSame($text, $result);
    }

    public function testItReplacesTabsToSpacesInDocumentationText()
    {
        $text = "Some\t\tsample text";
        $element = $this->getSampleElement($text);

        $result = $this->reader->get($element);

        $this->assertSame('Some sample text', $result);
    }

    public function testItReplacesMultipleSpacesToSingleSpacesInDocumentationText()
    {
        $text = 'Some    sample text';
        $element = $this->getSampleElement($text);

        $result = $this->reader->get($element);

        $this->assertSame('Some sample text', $result);
    }

    public function testItReturnsAnEmptyStringWhenDocumentationIsNotFoundInElement()
    {
        $element = $this->getSampleElementWithoutDocumentation();

        $result = $this->reader->get($element);

        $this->assertSame('', $result);
    }

    /**
     * @return \DOMElement
     */
    private function getSampleElement($text)
    {
        $xml = <<<XML
<xs:schema targetNamespace="http://www.w3.org/2001/XMLSchema" xmlns:xs="http://www.w3.org/2001/XMLSchema">
<xs:simpleType name="fooBar">
    <xs:annotation>
        <xs:documentation>
            $text
        </xs:documentation>
    </xs:annotation>
</xs:simpleType>
</xs:schema>
XML;
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadXML($xml);
        $element = $doc->getElementsByTagName('simpleType');

        return $element->item(0);
    }

    private function getSampleElementWithoutDocumentation()
    {
        $xml = <<<XML
<xs:schema targetNamespace="http://www.w3.org/2001/XMLSchema" xmlns:xs="http://www.w3.org/2001/XMLSchema">
<xs:simpleType name="fooBar">
    <xs:annotation>
    </xs:annotation>
</xs:simpleType>
</xs:schema>
XML;
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadXML($xml);
        $element = $doc->getElementsByTagName('simpleType');

        return $element->item(0);
    }
}
