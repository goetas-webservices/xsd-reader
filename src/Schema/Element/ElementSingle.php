<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

interface ElementSingle extends ElementItem, InterfaceSetMinMax
{
    public function getType(): ? Type;

    public function isQualified(): bool;

    /**
     * @param bool $qualified
     */
    public function setQualified(bool $qualified): void;

    public function isNil(): bool;

    public function setNil(bool $nil): void;
}
