<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\Item;

class Element extends Item implements ElementItem, ElementSingle
{
    /**
    * @var int
    */
    protected $min = 1;

    /**
    * @var int
    */
    protected $max = 1;

    /**
    * @var bool
    */
    protected $qualified = false;

    /**
    * @var bool
    */
    protected $nil = false;

    /**
    * @return int
    */
    public function getMin()
    {
        return $this->min;
    }

    /**
    * @param int $min
    *
    * @return $this
    */
    public function setMin($min)
    {
        $this->min = $min;
        return $this;
    }

    /**
    * @return int
    */
    public function getMax()
    {
        return $this->max;
    }

    /**
    * @param int $max
    *
    * @return $this
    */
    public function setMax($max)
    {
        $this->max = $max;
        return $this;
    }

    /**
    * @return bool
    */
    public function isQualified()
    {
        return $this->qualified;
    }

    /**
    * @param bool $qualified
    *
    * @return $this
    */
    public function setQualified($qualified)
    {
        $this->qualified = is_bool($qualified) ? $qualified : (boolean) $qualified;
        return $this;
    }

    /**
    * @return bool
    */
    public function isNil()
    {
        return $this->nil;
    }

    /**
    * @param bool $nil
    *
    * @return $this
    */
    public function setNil($nil)
    {
        $this->nil = is_bool($nil) ? $nil : (boolean) $nil;
        return $this;
    }
}
