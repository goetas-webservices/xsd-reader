<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Inheritance\Extension;
use Goetas\XML\XSDReader\Schema\Inheritance\Restriction;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeItem;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeContainer;

abstract class BaseComplexType extends Type implements AttributeContainer
{

    protected $attributes = array();

    public function addAttribute(AttributeItem $attribute)
    {
        $this->attributes[] = $attribute;
    }


    public function getAttributes()
    {
        return $this->attributes;
    }
}
