<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

class Restriction extends Base
{
    /**
     * @var mixed[][]
     */
    protected $checks = [];

    /**
     * @param mixed[] $value
     */
    public function addCheck(string $type, array $value): void
    {
        $this->checks[$type][] = $value;
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
    public function getChecksByType(string $type): array
    {
        return $this->checks[$type] ?? [];
    }
}
