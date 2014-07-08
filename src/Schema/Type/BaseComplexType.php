<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Attribute\Attribute;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeHolder;

abstract class BaseComplexType extends Type implements AttributeHolder
{

    /**
     *
     * @var Type
     */
    protected $restrict;

    /**
     *
     * @var Type
     */
    protected $extends;

    protected $attributes = array();

    public function getParent()
    {
        return $this->restrict ?  : $this->extends;
    }

    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[] = $attribute;
    }

    public function getRestrict()
    {
        return $this->restrict;
    }

    public function setRestrict(Type $restrict)
    {
        $this->restrict = $restrict;
        return $this;
    }

    public function getExtends()
    {
        return $this->extends;
    }

    public function setExtends(Type $extends)
    {
        $this->extends = $extends;
        return $this;
    }
}
