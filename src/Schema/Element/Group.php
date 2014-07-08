<?php
namespace Goetas\XML\XSDReader\Schema\Element;

use Goetas\XML\XSDReader\Schema\Type\Type;

class Group implements Element, ElementHolder
{
    protected $name;
    protected $elements;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function addElement(Element $element)
    {
        $this->elements[] = $element;
    }

}
