<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\AbstractNamedGroupItem;

class Group extends AbstractNamedGroupItem implements AttributeItem, AttributeContainer
{
    use AttributeContainerTrait;
}
