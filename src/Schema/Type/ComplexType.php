<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainerTrait;

class ComplexType extends BaseComplexType implements ElementContainer
{
    use ElementContainerTrait;

    private bool $mixed = false;

    public function isMixed(): bool
    {
        return $this->mixed;
    }

    public function setMixed(bool $mixed): self
    {
        $this->mixed = $mixed;

        return $this;
    }
}
