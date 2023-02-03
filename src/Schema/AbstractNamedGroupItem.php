<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

abstract class AbstractNamedGroupItem
{
    use NamedItemTrait;

    protected Schema $schema;

    protected ?string $doc;

    public function __construct(Schema $schema, string $name)
    {
        $this->schema = $schema;
        $this->name = $name;
    }

    public function getDoc(): ?string
    {
        return $this->doc;
    }

    public function setDoc(string $doc): void
    {
        $this->doc = $doc;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }
}
