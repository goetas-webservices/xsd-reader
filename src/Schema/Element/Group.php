<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItemTrait;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Group implements ElementItem, ElementContainer
{
    use AttributeItemTrait;
    use ElementContainerTrait;

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
