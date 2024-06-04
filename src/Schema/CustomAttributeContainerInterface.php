<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

interface CustomAttributeContainerInterface
{
    /**
     * @param list<CustomAttribute> $customAttributes
     */
    public function setCustomAttributes(array $customAttributes): void;

    /**
     * @return list<CustomAttribute>
     */
    public function getCustomAttributes(): array;
}
