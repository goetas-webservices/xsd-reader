<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\Item;

class Element extends Item implements ElementItem, ElementSingle
{

    protected $min = 1;

    protected $max = 1;

    protected $qualified = true;

    protected $nil = false;

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
