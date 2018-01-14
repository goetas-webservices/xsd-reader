<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

class Attribute extends AbstractAttributeItem implements AttributeSingle
{
    /**
     * @var bool
     */
    protected $qualified = true;

    /**
     * @var bool
     */
    protected $nil = false;

    /**
     * @var string
     */
    protected $use = self::USE_OPTIONAL;

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

    public function getUse(): ?string
    {
        return $this->use;
    }

    public function setUse(string $use): void
    {
        $this->use = $use;
    }
}
