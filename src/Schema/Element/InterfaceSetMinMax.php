<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

interface InterfaceSetMinMax
{
    public function getMin(): int;

    public function setMin(int $min): void;

    public function getMax(): int;

    public function setMax(int $max): void;
}
