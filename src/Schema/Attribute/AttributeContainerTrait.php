<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

trait AttributeContainerTrait
{
    /**
     * @var AttributeItem[]
     */
    protected $attributes = array();

    public function addAttribute(AttributeItem $attribute)
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
