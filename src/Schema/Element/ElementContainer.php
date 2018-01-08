<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;

interface ElementContainer extends SchemaItem
{
    public function addElement(ElementItem $element): void;

    /**
     * @return ElementItem[]
     */
    public function getElements(): array;
}
