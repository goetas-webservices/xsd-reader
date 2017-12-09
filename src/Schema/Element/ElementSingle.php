<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

interface ElementSingle extends ElementItem, InterfaceSetMinMax
{
    /**
     * @return \GoetasWebservices\XML\XSDReader\Schema\Type\Type|null
     */
    public function getType();

    public function isQualified(): bool;

    /**
     * @param bool $qualified
     */
    public function setQualified(bool $qualified);

    public function isNil(): bool;

    public function setNil(bool $nil);
}
