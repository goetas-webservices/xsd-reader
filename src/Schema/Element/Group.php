<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\AbstractNamedGroupItem;

class Group extends AbstractNamedGroupItem implements ElementItem, ElementContainer
{
    use ElementContainerTrait;
}
