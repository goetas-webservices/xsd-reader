<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\MetaInformation;

abstract class AbstractAttributeItem extends Item implements AttributeSingle
{
    protected ?string $fixed = null;

    protected ?string $default = null;

    protected bool $qualified = true;

    protected bool $nil = false;

    protected string $use = self::USE_OPTIONAL;

    /**
     * @var list<MetaInformation>
     */
    protected array $meta = [];

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

    public function isQualified(): bool
    {
        return $this->qualified;
    }

    public function setQualified(bool $qualified): void
    {
        $this->qualified = $qualified;
    }

    public function isNil(): bool
    {
        return $this->nil;
    }

    public function setNil(bool $nil): void
    {
        $this->nil = $nil;
    }

    public function getUse(): string
    {
        return $this->use;
    }

    public function setUse(string $use): void
    {
        $this->use = $use;
    }

    /**
     * @return list<MetaInformation>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param list<MetaInformation> $meta
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }
}
