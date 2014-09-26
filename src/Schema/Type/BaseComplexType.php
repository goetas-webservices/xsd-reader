<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Attribute\Attribute;
use Goetas\XML\XSDReader\Schema\Inheritance\Extension;
use Goetas\XML\XSDReader\Schema\Inheritance\Restriction;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeBase;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeItem;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeContainer;

abstract class BaseComplexType extends Type implements AttributeContainer
{

    /**
     *
     * @var Restriction
     */
    protected $restriction;

    /**
     *
     * @var Extension
     */
    protected $extension;

    protected $attributes = array();

    /**
     *
     * @return \Goetas\XML\XSDReader\Schema\Inheritance\Base
     */
    public function getParent()
    {
        return $this->restriction ?  : $this->extension;
    }

    public function addAttribute(AttributeItem $attribute)
    {
        $this->attributes[] = $attribute;
    }

    public function getRestriction()
    {
        return $this->restriction;
    }

    public function setRestriction(Restriction $restriction)
    {
        $this->restriction = $restriction;
        return $this;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function setExtension(Extension $extension)
    {
        $this->extension = $extension;
        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}
