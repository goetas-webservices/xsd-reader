<?php
namespace Goetas\XML\XSDReader\Schema\Element;

use Goetas\XML\XSDReader\Schema\Type\Type;

class Group implements Element, ElementHolder
{

    protected $doc;

    protected $name;

    protected $elements=array();

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
