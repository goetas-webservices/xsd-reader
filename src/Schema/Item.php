<?php

declare(strict_types=1);

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

    public function __construct(Schema $schema, string $name)
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
    public function setType(Type $type): self
    {
        $this->type = $type;

        return $this;
    }
}
