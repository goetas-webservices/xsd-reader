<?php

namespace GoetasWebservices\XML\XSDReader\Schema;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

abstract class Item implements SchemaItem
{
    use NamedItemTrait;
    use SchemaItemTrait;

    /**
     * @var Type|null
     */
    protected $type;

    /**
     * @param string $name
     */
    public function __construct(Schema $schema, $name)
    {
        $this->schema = $schema;
        $this->name = $name;
    }

    /**
     * @return Type|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return $this
     */
    public function setType(Type $type)
    {
        $this->type = $type;

        return $this;
    }
}
