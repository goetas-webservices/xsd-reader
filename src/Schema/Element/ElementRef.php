<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\Item;

class ElementRef extends Item implements ElementSingle
{

    protected $wrapped;

    protected $min = 1;

    protected $max = 1;

    protected $qualified = true;

    protected $nil = false;

    public function __construct(ElementDef $element)
    {
        parent::__construct($element->getSchema(), $element->getName());
        $this->wrapped = $element;
    }

    /**
     *
     * @return ElementDef
     */
    public function getReferencedElement()
    {
        return $this->wrapped;
    }

    public function getType()
    {
        return $this->wrapped->getType();
    }

    public function getMin()
    {
        return $this->min;
    }

    public function setMin($min)
    {
        $this->min = $min;
        return $this;
    }

    public function getMax()
    {
        return $this->max;
    }

    public function setMax($max)
    {
        $this->max = $max;
        return $this;
    }

    public function isQualified()
    {
        return $this->qualified;
    }

    public function setQualified($qualified)
    {
        $this->qualified = (boolean) $qualified;
        return $this;
    }

    public function isNil()
    {
        return $this->nil;
    }

    public function setNil($nil)
    {
        $this->nil = (boolean) $nil;
        return $this;
    }
}
