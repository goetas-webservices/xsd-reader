<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

interface InterfaceSetDefault
{
    public function getDefault();

    public function setDefault($default): void;
}
