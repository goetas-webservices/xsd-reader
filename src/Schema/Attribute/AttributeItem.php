<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\SchemaItem;

interface AttributeItem extends SchemaItem
{
    public function getName();
}
