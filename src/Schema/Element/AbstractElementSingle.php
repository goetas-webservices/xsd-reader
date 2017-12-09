<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\Item;

class AbstractElementSingle extends Item implements ElementSingle
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
    protected $qualified = true;

    /**
     * @var bool
     */
    protected $nil = false;

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
        $this->qualified = is_bool($qualified) ? $qualified : (bool) $qualified;

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
        $this->nil = is_bool($nil) ? $nil : (bool) $nil;

        return $this;
    }

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
}
