<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

abstract class Base
{
    protected ?Type $base;

    public function getBase(): ?Type
    {
        return $this->base;
    }

    public function setBase(Type $base): self
    {
        $this->base = $base;

        return $this;
    }
}
