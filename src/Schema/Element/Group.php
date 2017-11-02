<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\AbstractNamedGroupItem;
use GoetasWebservices\XML\XSDReader\Schema\Schema;

class Group extends AbstractNamedGroupItem implements ElementItem, ElementContainer
{
    use ElementContainerTrait;
}
