<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

class AttributeRef extends AbstractAttributeItem
{
    protected AttributeDef $wrapped;

    public function __construct(AttributeDef $attribute)
    {
        parent::__construct($attribute->getSchema(), $attribute->getName());
        $this->wrapped = $attribute;
    }

    public function getName(): string
    {
        return $this->wrapped->getName();
    }

    public function getReferencedAttribute(): AttributeDef
    {
        return $this->wrapped;
    }

    public function getType(): ?Type
    {
        return $this->wrapped->getType();
    }
}
