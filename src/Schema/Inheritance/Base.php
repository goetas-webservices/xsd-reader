<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

abstract class Base
{
    /**
     * @var Type|null
     */
    protected $base;

    public function getBase(): ?Type
    {
        return $this->base;
    }

    /**
     * @return $this
     */
    public function setBase(Type $base): self
    {
        $this->base = $base;

        return $this;
    }
}
