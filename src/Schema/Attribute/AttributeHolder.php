<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\SchemaItem;
interface AttributeHolder extends SchemaItem
{
    public function addAttribute(Attribute $attribute);
    public function getAttributes();
}

