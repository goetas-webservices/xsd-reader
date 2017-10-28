<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainerTrait;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;

class ComplexType extends BaseComplexType implements ElementContainer
{
    use ElementContainerTrait;
}
