<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

interface InterfaceSetFixed
{
    public function getFixed(): ?string;

    public function setFixed(string $fixed): void;
}
