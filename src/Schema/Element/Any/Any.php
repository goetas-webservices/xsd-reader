<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element\Any;

use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetMinMax;
use GoetasWebservices\XML\XSDReader\Schema\Element\MinMaxTrait;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItemTrait;

/**
 * Represents https://www.w3schools.com/xml/el_any.asp.
 */
class Any implements SchemaItem, ElementItem, InterfaceSetMinMax
{
    use MinMaxTrait;
    use SchemaItemTrait;

    protected ?string $namespace = null;
    protected ProcessContents $processContents = ProcessContents::Strict;
    protected ?string $id = null;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function getName(): string
    {
        return 'any';
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setNamespace(?string $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function getProcessContents(): ProcessContents
    {
        return $this->processContents;
    }

    public function setProcessContents(ProcessContents $processContents): void
    {
        $this->processContents = $processContents;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }
}
