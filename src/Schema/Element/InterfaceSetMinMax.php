<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

interface InterfaceSetMinMax
{
    /**
     *
     * @return int
     */
    public function getMin();

    /**
     *
     * @param int $min
     */
    public function setMin($min);

    /**
     *
     * @return int
     */
    public function getMax();

    /**
     *
     * @param int $max
     */
    public function setMax($max);
}
