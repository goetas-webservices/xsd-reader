<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

trait SchemaItemTrait
{
    protected Schema $schema;

    protected string $doc = '';

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getDoc(): string
    {
        return $this->doc;
    }

    public function setDoc(string $doc): void
    {
        $this->doc = $doc;
    }
}
