<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\Type\TypeNodeChild;

class AttributeGroup implements Attribute, AttributeHolder
{

    protected $doc;

    protected $name;

    protected $attributes = array();

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[] = $attribute;
    }
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getDoc()
    {
        return $this->doc;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;
        return $this;
    }

}
