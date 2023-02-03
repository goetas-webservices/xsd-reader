<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\AbstractNamedGroupItem;

class Choice extends AbstractNamedGroupItem implements ElementItem, ElementContainer, InterfaceSetMinMax
{
    use ElementContainerTrait;

    protected int $min = 1;

    protected int $max = 1;

    public function getMin(): int
    {
        return $this->min;
    }

    public function setMin(int $min): void
    {
        $this->min = $min;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function setMax(int $max): void
    {
        $this->max = $max;
    }
}
