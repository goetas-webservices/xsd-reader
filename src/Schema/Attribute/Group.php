<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\AbstractNamedGroupItem;
use GoetasWebservices\XML\XSDReader\Schema\Schema;

class Group extends AbstractNamedGroupItem implements AttributeItem, AttributeContainer
{
    use AttributeContainerTrait;
}
