<?php
namespace Goetas\XML\XSDReader\Schema\Element;

use Goetas\XML\XSDReader\Schema\SchemaItem;

interface ElementContainer extends SchemaItem
{

    public function addElement(ElementItem $element);

    public function getElements();
}
