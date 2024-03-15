<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

class Restriction extends Base
{
    /**
     * @var mixed[][]
     */
    protected array $checks = [];

    /**
     * @param mixed[] $value
     */
    public function addCheck(RestrictionType $type, array $value): void
    {
        $this->checks[$type->value][] = $value;
    }

    /**
     * @return mixed[][]
     */
    public function getChecks(): array
    {
        return $this->checks;
    }

    /**
     * @return mixed[]
     */
    public function getChecksByType(RestrictionType $type): array
    {
        return $this->checks[$type->value] ?? [];
    }
}
