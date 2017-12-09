<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

trait SchemaItemTrait
{
    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var string
     */
    protected $doc = '';

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getDoc(): string
    {
        return $this->doc;
    }

    /**
     * @return $this
     */
    public function setDoc(string $doc)
    {
        $this->doc = $doc;

        return $this;
    }
}
