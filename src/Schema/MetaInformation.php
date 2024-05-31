<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

/**
 * Contains the information of additional attributes that are not part of the XSD specification:
 * Formats like WSDLs can allow additional attributes to be added to the schemas.
 * This class is used to store this information.
 */
class MetaInformation
{
    /**
     * Links to the schema in which this information is contained.
     * This context schema can be used to resolve an un-prefixed qname type.
     *
     * Example:
     * wsdl:arrayType="int"
     *
     * value = "int"
     * contextSchema = xsd : "http://www.w3.org/2001/XMLSchema"
     *
     * The type would be xsd:int
     */
    private Schema $contextSchema;

    private string $namespaceURI;

    private string $name;

    private string $value;

    public function __construct(
        Schema $contextSchema,
        string $namespaceURI,
        string $name,
        string $value
    ) {
        $this->contextSchema = $contextSchema;
        $this->namespaceURI = $namespaceURI;
        $this->name = $name;
        $this->value = $value;
    }

    public function getContextSchema(): Schema
    {
        return $this->contextSchema;
    }

    public function getNamespaceURI(): string
    {
        return $this->namespaceURI;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
