<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Element\Element;
use Goetas\XML\XSDReader\Schema\Element\ElementHolder;

class ComplexType extends BaseComplexType implements ElementHolder
{
    protected $elements = array();

    public function getElements()
    {
        return $this->elements;
    }

    public function addElement(Element $element)
    {
        $this->elements[] = $element;
    }
}
