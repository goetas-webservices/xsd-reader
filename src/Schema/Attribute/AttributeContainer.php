<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;

interface AttributeContainer extends SchemaItem
{
    public function addAttribute(AttributeItem $attribute): void;

    /**
     * @return AttributeItem[]
     */
    public function getAttributes(): array;
}
