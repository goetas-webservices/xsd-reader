<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;

interface AttributeItem extends SchemaItem
{
    /**
     * @return string
     */
    public function getName();
}
