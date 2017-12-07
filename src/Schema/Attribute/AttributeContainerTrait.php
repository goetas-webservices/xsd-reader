<?php

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
    public function getAttributes()
    {
        return $this->attributes;
    }
}
