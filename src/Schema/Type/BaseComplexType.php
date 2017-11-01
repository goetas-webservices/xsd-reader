<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use DOMElement;
use GoetasWebservices\XML\XSDReader\SchemaReader;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainer;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainerTrait;
use GoetasWebservices\XML\XSDReader\Schema\Schema;

abstract class BaseComplexType extends Type implements AttributeContainer
{
    use AttributeContainerTrait;
}
