<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

abstract class Base
{
    /**
     * @var Type|null
     */
    protected $base;

    /**
     * @return Type|null
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @return $this
     */
    public function setBase(Type $base)
    {
        $this->base = $base;

        return $this;
    }
}
