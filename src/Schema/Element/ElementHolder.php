<?php
namespace Goetas\XML\XSDReader\Schema\Element;

use Goetas\XML\XSDReader\Schema\SchemaItem;
interface ElementHolder extends SchemaItem
{
    public function addElement(Element $element);
    public function getElements();
}
