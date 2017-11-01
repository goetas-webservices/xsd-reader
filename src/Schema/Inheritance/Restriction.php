<?php

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
    public function addCheck($type, $value)
    {
        $this->checks[$type][] = $value;

        return $this;
    }

    /**
     * @return mixed[][]
     */
    public function getChecks()
    {
        return $this->checks;
    }

    /**
     * @param string $type
     *
     * @return mixed[]
     */
    public function getChecksByType($type)
    {
        return isset($this->checks[$type]) ? $this->checks[$type] : array();
    }
}
