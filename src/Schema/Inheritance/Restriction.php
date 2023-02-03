<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

class Restriction extends Base
{
    protected array $checks = [];

    public function addCheck(RestrictionType $type, array $value): void
    {
        $this->checks[$type->value][] = $value;
    }

    public function getChecks(): array
    {
        return $this->checks;
    }

    public function getChecksByType(RestrictionType $type): array
    {
        return $this->checks[$type->value] ?? [];
    }
}
