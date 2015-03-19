<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;

class ComplexType extends BaseComplexType implements ElementContainer
{
    protected $elements = array();

    public function getElements()
    {
        return $this->elements;
    }

    public function addElement(ElementItem $element)
    {
        $this->elements[] = $element;
    }
}

