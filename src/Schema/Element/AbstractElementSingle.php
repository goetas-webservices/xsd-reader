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

    public function isQualified(): bool
    {
        return $this->qualified;
    }

    /**
     * @return $this
     */
    public function setQualified(bool $qualified): self
    {
        $this->qualified = is_bool($qualified) ? $qualified : (bool) $qualified;

        return $this;
    }

    public function isNil(): bool
    {
        return $this->nil;
    }

    /**
     * @return $this
     */
    public function setNil(bool $nil): self
    {
        $this->nil = is_bool($nil) ? $nil : (bool) $nil;

        return $this;
    }

    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * @return $this
     */
    public function setMin(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    /**
     * @return $this
     */
    public function setMax(int $max): self
    {
        $this->max = $max;

        return $this;
    }
}
