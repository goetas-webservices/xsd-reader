<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

abstract class Item implements SchemaItem
{
    use NamedItemTrait;
    use SchemaItemTrait;

    protected ?Type $type;

    public function __construct(Schema $schema, string $name)
    {
        $this->schema = $schema;
        $this->name = $name;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(Type $type): void
    {
        $this->type = $type;
    }
}
