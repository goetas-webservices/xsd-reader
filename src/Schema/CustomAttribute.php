<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

/**
 * Contains the information of additional custom attributes that are not part of the XSD specification.
 *
 * For example:
 * <xsd:element name="myElement" dfdl:encoding="iso-8859-1" />
 */
class CustomAttribute
{
    private string $namespaceURI;

    private string $name;

    private string $value;

    public function __construct(
        string $namespaceURI,
        string $name,
        string $value,
    ) {
        $this->namespaceURI = $namespaceURI;
        $this->name = $name;
        $this->value = $value;
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
