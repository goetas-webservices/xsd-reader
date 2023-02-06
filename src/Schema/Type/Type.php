<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetAbstract;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Base;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Extension;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItemTrait;

abstract class Type implements SchemaItem, InterfaceSetAbstract
{
    use SchemaItemTrait;

    protected ?string $name;

    protected bool $abstract = false;

    protected ?Restriction $restriction = null;

    protected ?Extension $extension = null;

    public function __construct(Schema $schema, ?string $name = null)
    {
        $this->name = $name ?: null;
        $this->schema = $schema;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function setAbstract(bool $abstract): void
    {
        $this->abstract = $abstract;
    }

    /**
     * @return Restriction|Extension|null
     */
    public function getParent(): ?Base
    {
        return $this->restriction ?: $this->extension;
    }

    public function getRestriction(): ?Restriction
    {
        return $this->restriction;
    }

    public function setRestriction(Restriction $restriction): void
    {
        $this->restriction = $restriction;
    }

    public function getExtension(): ?Extension
    {
        return $this->extension;
    }

    public function setExtension(Extension $extension): void
    {
        $this->extension = $extension;
    }
}
