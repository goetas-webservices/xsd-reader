<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

class Restriction extends Base
{

    protected $checks = array();

    public function addCheck($type, $value)
    {
        $this->checks[$type][] = $value;
        return $this;
    }

    public function getChecks()
    {
        return $this->checks;
    }
    public function getChecksByType($type)
    {
        return isset($this->checks[$type])?$this->checks[$type]:array();
    }
}