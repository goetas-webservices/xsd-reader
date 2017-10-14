<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

interface ElementSingle extends ElementItem
{

    /**
     * @return \GoetasWebservices\XML\XSDReader\Schema\Type\Type
     */
    public function getType();

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

    /**
     *
     * @return bool
     */
    public function isQualified();

    /**
     *
     * @param boolean $qualified
     */
    public function setQualified($qualified);

    /**
     *
     * @return bool
     */
    public function isNil();

    /**
     *
     * @param boolean $nil
     */
    public function setNil($nil);
}
