<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\AbstractNamedGroupItem;

class Sequence extends AbstractNamedGroupItem implements ElementItem, ElementContainer, InterfaceSetMinMax
{
    use ElementContainerTrait;
    use MinMaxTrait;
}
