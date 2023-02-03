<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

trait AttributeContainerTrait
{
    /**
     * @var AttributeItem[]
     */
    protected array $attributes = [];

    public function addAttribute(AttributeItem $attribute): void
    {
        $this->attributes[] = $attribute;
    }

    /**
     * @return AttributeItem[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
