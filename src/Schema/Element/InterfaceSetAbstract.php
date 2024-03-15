<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

interface InterfaceSetAbstract
{
    public function isAbstract(): bool;

    public function setAbstract(bool $abstract): void;
}
