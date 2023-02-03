<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

trait NamedItemTrait
{
    protected string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
