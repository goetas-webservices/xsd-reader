<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainer;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainerTrait;

abstract class BaseComplexType extends Type implements AttributeContainer
{
    use AttributeContainerTrait;
}
