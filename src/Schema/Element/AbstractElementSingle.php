<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\CustomAttributeContainerTrait;
use GoetasWebservices\XML\XSDReader\Schema\Item;

class AbstractElementSingle extends Item implements ElementSingle, InterfaceSetAbstract
{
    use CustomAttributeContainerTrait;

    protected int $min = 1;

    protected int $max = 1;

    protected bool $qualified = false;

    protected bool $local = false;

    protected bool $nil = false;

    protected ?string $fixed = null;

    protected ?string $default = null;

    protected bool $abstract = false;

    public function isQualified(): bool
    {
        return $this->qualified;
    }

    public function setQualified(bool $qualified): void
    {
        $this->qualified = $qualified;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function setLocal(bool $local): void
    {
        $this->local = $local;
    }

    public function isNil(): bool
    {
        return $this->nil;
    }

    public function setNil(bool $nil): void
    {
        $this->nil = $nil;
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function setMin(int $min): void
    {
        $this->min = $min;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function setMax(int $max): void
    {
        $this->max = $max;
    }

    public function getFixed(): ?string
    {
        return $this->fixed;
    }

    public function setFixed(string $fixed): void
    {
        $this->fixed = $fixed;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function setDefault(string $default): void
    {
        $this->default = $default;
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function setAbstract(bool $abstract): void
    {
        $this->abstract = $abstract;
    }
}
