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
     * This context schema can be used to resolve e.g. a type.
     *
     * Example:
     * wsdl:arrayType="int"
     *
     * value = "int"
     * contextSchema = xsd : "http://www.w3.org/2001/XMLSchema"
     * schema = wsdl : "http://schemas.xmlsoap.org/wsdl/"
     *
     * The type would be xsd:int
     */
    private Schema $contextSchema;

    /**
     * Links to the schema that holds the declaration of the meta information type.
     * The meta information would be located inside the "schema-prefix:name" attribute.
     */
    private Schema $schema;

    private string $name;

    private string $value;

    public function __construct(
        Schema $schema,
        Schema $contextSchema,
        string $name,
        string $value
    ) {
        $this->schema = $schema;
        $this->contextSchema = $contextSchema;
        $this->name = $name;
        $this->value = $value;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getContextSchema(): Schema
    {
        return $this->contextSchema;
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
