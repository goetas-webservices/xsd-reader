<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

class Restriction extends Base
{
    /**
     * @var mixed[][]
     */
    protected $checks = array();

    /**
     * @param string  $type
     * @param mixed[] $value
     *
     * @return $this
     */
    public function addCheck(string $type, array $value)
    {
        $this->checks[$type][] = $value;

        return $this;
    }

    /**
     * @return mixed[][]
     */
    public function getChecks() : array
    {
        return $this->checks;
    }

    /**
     * @param string $type
     *
     * @return mixed[]
     */
    public function getChecksByType(string $type) : array
    {
        return isset($this->checks[$type]) ? $this->checks[$type] : array();
    }
}
