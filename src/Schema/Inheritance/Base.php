<?php
namespace Goetas\XML\XSDReader\Schema\Inheritance;

use Goetas\XML\XSDReader\Schema\Type\Type;

abstract class Base
{

    /**
     *
     * @var Type
     */
    protected $base;

    public function getBase()
    {
        return $this->base;
    }

    public function setBase(Type $base)
    {
        $this->base = $base;
        return $this;
    }
}