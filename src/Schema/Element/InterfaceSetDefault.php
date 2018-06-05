<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

interface InterfaceSetDefault
{
    public function getDefault(): ?string;

    public function setDefault(string $default): void;
}
