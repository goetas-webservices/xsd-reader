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
    private Schema $schema;
    private string $name;
    private string $value;

    public function __construct(
        Schema $schema,
        string $name,
        string $value
    ) {
        $this->schema = $schema;
        $this->name = $name;
        $this->value = $value;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
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
