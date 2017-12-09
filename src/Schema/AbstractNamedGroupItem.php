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

    /**
     * @param string $name
     */
    public function __construct(Schema $schema, $name)
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
     * @param string $doc
     *
     * @return $this
     */
    public function setDoc($doc)
    {
        $this->doc = $doc;

        return $this;
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }
}
