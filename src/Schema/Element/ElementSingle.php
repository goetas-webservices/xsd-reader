<?php
namespace Goetas\XML\XSDReader\Schema\Element;

interface ElementSingle extends ElementItem
{

    public function getType();

    public function getMin();

    public function setMin($min);

    public function getMax();

    public function setMax($max);

    public function isQualified();

    public function setQualified($qualified);

    public function isNil();

    public function setNil($nil);

}
