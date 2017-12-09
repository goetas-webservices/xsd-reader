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

    /**
     * @return string|null
     */
    public function getDoc()
    {
        return $this->doc;
    }

    /**
     * @return $this
     */
    public function setDoc(string $doc) : AbstractNamedGroupItem
    {
        $this->doc = $doc;

        return $this;
    }

    public function getSchema() : Schema
    {
        return $this->schema;
    }
}
