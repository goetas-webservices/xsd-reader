<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

abstract class AbstractNamedGroupItem
{
    use NamedItemTrait;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var string|null
     */
    protected $doc;

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
