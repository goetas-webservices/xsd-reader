<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

trait CustomAttributeContainerTrait
{
    /**
     * @var list<CustomAttribute>
     */
    protected array $customAttributes = [];

    /**
     * @return list<CustomAttribute>
     */
    public function getCustomAttributes(): array
    {
        return $this->customAttributes;
    }

    /**
     * @param list<CustomAttribute> $customAttributes
     */
    public function setCustomAttributes(array $customAttributes): void
    {
        $this->customAttributes = $customAttributes;
    }
}
