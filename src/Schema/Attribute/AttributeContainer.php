<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\SchemaItem;

interface AttributeContainer extends SchemaItem
{

    public function addAttribute(AttributeItem $attribute);

    public function getAttributes();
}
