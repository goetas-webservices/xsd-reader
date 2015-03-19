<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;

interface AttributeContainer extends SchemaItem
{

    public function addAttribute(AttributeItem $attribute);

    public function getAttributes();
}
