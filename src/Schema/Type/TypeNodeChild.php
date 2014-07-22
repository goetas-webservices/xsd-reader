<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Type\Type;

abstract class TypeNodeChild
{

    protected $name;

    protected $type;

    protected $isAnonymousType = false;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(Type $type)
    {
        $this->type = $type;
        return $this;
    }

    public function isAnonymousType()
    {
        return $this->isAnonymousType;
    }

    public function setIsAnonymousType($isAnonymousType)
    {
        $this->isAnonymousType = !!$isAnonymousType;
        return $this;
    }
}
